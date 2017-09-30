<?php
/**
 * This driver will look up all annotations for the class and each method of
 * the class (of the bean, of course).
 *
 * @category   Ding
 * @package    Bean
 * @subpackage Provider
 * @author     Marcelo Gornstein <marcelog@gmail.com>
 * @license    http://marcelog.github.com/ Apache License 2.0
 * @link       http://marcelog.github.com/
 *
 * Copyright 2011 Marcelo Gornstein <marcelog@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
namespace Ding\Bean\Provider;

use Ding\Annotation\Annotation as AnnotationDefinition;
use Ding\Annotation\Collection;
use Ding\Aspect\AspectDefinition;
use Ding\Aspect\AspectManager;
use Ding\Aspect\IAspectManagerAware;
use Ding\Aspect\IAspectProvider;
use Ding\Aspect\PointcutDefinition;
use Ding\Bean\BeanDefinition;
use Ding\Bean\Factory\Exception\BeanFactoryException;
use Ding\Bean\IBeanDefinitionProvider;
use Ding\Cache\ICache;
use Ding\Container\IContainer;
use Ding\Container\IContainerAware;
use Ding\Reflection\IReflectionFactory;
use Ding\Reflection\IReflectionFactoryAware;

/**
 * This driver will look up all annotations for the class and each method of
 * the class (of the bean, of course).
 *
 * @package Ding\Bean\Provider
 */
class Annotation implements IBeanDefinitionProvider, IContainerAware, IReflectionFactoryAware, IAspectManagerAware, IAspectProvider {
    protected $parentContainer;
    /**
     * Target directories to scan for annotated classes.
     *
     * @var string[]
     */
    private $scanDirs;
    /**
     * @Configuration annotated classes.
     * @var string[]
     */
    private $configClasses=false;
    /**
     * @Configuration beans (coming from @Configuration annotated classes).
     * @var object[]
     */
    private $configBeans=false;
    /**
     * Our cache.
     *
     * @var ICache
     */
    private $cache=false;
    /**
     * Definitions for config beans.
     *
     * @var BeanDefinition[]
     */
    private $beanDefinitions=[];
    /**
     * Container.
     *
     * @var IContainer $container
     */
    private $container;
    /**
     * A ReflectionFactory implementation.
     *
     * @var IReflectionFactory
     */
    protected $reflectionFactory;
    /**
     * All known beans, indexed by name.
     *
     * @var string[]
     */
    private $knownBeans=[];
    /**
     * Maps beans from their classes.
     *
     * @var string[]
     */
    private $knownBeansByClass=[];
    /**
     * This one helps map a bean with a its parent class bean definition.
     *
     * @var string[]
     */
    private $parentClasses=[];
    /**
     * All beans (names) listening for events will be here.
     *
     * @var string[]
     */
    private $knownBeansPerEvent=[];
    /**
     * Valid bean annotations.
     *
     * @var string[]
     */
    private $validBeanAnnotations=[
        'controller',
        'bean',
        'component',
        'configuration',
        'aspect',
        'named'
    ];
    /**
     * @var AspectManager $aspectManager
     */
    private $aspectManager;

    /**
     * Annotation constructor.
     *
     * @param array $options Optional options.
     */
    public function __construct(array $options) {
        $this->scanDirs=$options['scanDir'];
        $this->configClasses=[];
        $this->beanDefinitions=[];
        $this->configBeans=[];
    }

    /**
     * @param AspectManager $aspectManager
     */
    public function setAspectManager(AspectManager $aspectManager) : void {
        $this->aspectManager=$aspectManager;
    }

    /**
     * @param IReflectionFactory $reflectionFactory
     *
     * @see \Ding\Reflection\IReflectionFactoryAware::setReflectionFactory()
     */
    public function setReflectionFactory(IReflectionFactory $reflectionFactory) : void {
        $this->reflectionFactory=$reflectionFactory;
    }

    /**
     * @param string $eventName
     *
     * @see \Ding\Bean\IBeanDefinitionProvider::getBeansListeningOn()
     *
     * @return string[]
     */
    public function getBeansListeningOn(string $eventName) : array {
        $beans=$this->knownBeansPerEvent[$eventName] ?? [];
        return (is_array($beans) ? $beans : []);
    }

    /**
     * @param string $name
     *
     * @see \Ding\Aspect\IBeanDefinitionProvider::getBeanDefinition()
     * @return BeanDefinition|null
     */
    public function getBeanDefinition(string $name) :?BeanDefinition {
        foreach ($this->knownBeans as $leadName=>$data) {
            $names=$data[0];
            $class=$data[1];
            //$key=$data[2];
            $annotations=$data[3];
            $fBean=$data[4];
            $fMethod=$data[5];
            if (false !== in_array($name, $names)) {
                return $this->makeBeanDefinition($name, $class, $annotations, $fBean, $fMethod);
            }
        }
        return null;
    }

    /**
     * @param string $class
     *
     * @return array
     */
    public function getBeansByClass(string $class) : array {
        $beans=$this->knownBeansByClass[$class] ?? [];
        return (is_array($beans) ? $beans : []);
    }

    /**
     * @param IContainer $container
     *
     * @see \Ding\Container\IContainerAware::setContainer()
     */
    public function setContainer(IContainer $container) : void {
        $this->container=$container;
    }

    /**
     * @param ICache $cache
     */
    public function setCache(ICache $cache) : void {
        $this->cache=$cache;
    }

    public function init() : void {
        foreach ($this->validBeanAnnotations as $beanAnnotationName) {
            $this->traverseConfigClasses($beanAnnotationName, $this->reflectionFactory->getClassesByAnnotation($beanAnnotationName));
        }
        foreach ($this->knownBeans as $leadName=>$data) {
            //$names=$data[0];
            $class=$data[1];
            //$key=$data[2];
            $annotations=$data[3];
            $this->registerEventsFor($annotations, $leadName, $class);
        }
    }

    /**
     * @return AspectDefinition[]
     */
    public function getAspects() : array {
        $ret=[];
        $aspectClasses=$this->reflectionFactory->getClassesByAnnotation('aspect');
        foreach ($aspectClasses as $aspectClass) {
            foreach ($this->knownBeansByClass[$aspectClass] as $beanName) {
                $rClass=$this->reflectionFactory->getClass($aspectClass);
                foreach ($rClass->getMethods() as $rMethod) {
                    $methodName=$rMethod->getName();
                    $annotations=$this->reflectionFactory->getMethodAnnotations($aspectClass, $methodName);
                    if ($annotations->contains('methodinterceptor')) {
                        foreach ($annotations->getAnnotations('methodinterceptor') as $annotation) {
                            $classExpression=$annotation->getOptionSingleValue('class-expression');
                            $expression=$annotation->getOptionSingleValue('expression');
                            $ret[]=$this->createAspect($beanName, $classExpression, $expression, $methodName, AspectDefinition::ASPECT_METHOD);
                        }
                    }
                    if ($annotations->contains('exceptioninterceptor')) {
                        foreach ($annotations->getAnnotations('exceptioninterceptor') as $annotation) {
                            $classExpression=$annotation->getOptionSingleValue('class-expression');
                            $expression=$annotation->getOptionSingleValue('expression');
                            $ret[]=$this->createAspect($beanName, $classExpression, $expression, $methodName, AspectDefinition::ASPECT_EXCEPTION);
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Looks for @ListensOn and register the bean as an event listener. Since
     * this is an "early" discovery of a bean, a BeanDefinition is generated.
     *
     * @param Collection $annotations Bean Annotations (for classes or methods)
     * @param string     $beanName    The target bean name.
     * @param string     $class       The bean class
     *
     * @return void
     */
    protected function registerEventsFor(Collection $annotations, string $beanName, string $class) : void {
        $rClass=$this->reflectionFactory->getClass($class);
        if (!$rClass->isAbstract()) {
            $this->registerEventsForBeanName($annotations, $beanName);
            while ($rClass=$this->reflectionFactory->getClass($class)->getParentClass()) {
                $class=$rClass->getName();
                $annotations=$this->reflectionFactory->getClassAnnotations($rClass->getName());
                $this->registerEventsForBeanName($annotations, $beanName);
            }
        }
    }

    /**
     * Returns the bean definition for a parent class of a class (if found). If
     * the parent class has a valid bean annotation (see $knownBeans) it will
     * be returned.
     *
     * @param string $class
     *
     * @return BeanDefinition|null
     */
    private function getParentBeanDefinition(string $class) :?BeanDefinition {
        $def=null;
        while ($parentRefClass=$this->reflectionFactory->getClass($class)->getParentClass()) {
            $class=$parentRefClass->getName();
            // Does this class has a valid bean annotation?
            if (isset($this->parentClasses[$class])) {
                $parentNameBean=$this->parentClasses[$class];
                return $this->container->getBeanDefinition($parentNameBean);
            }
        }
        return $def;
    }

    /**
     * Creates a bean definition from the given annotations.
     *
     * @param string     $name        Bean name.
     * @param string     $class       Bean class.
     * @param Collection $annotations Annotations with data.
     * @param bool       $fBean
     * @param bool       $fMethod
     *
     * @return BeanDefinition|null
     * @throws BeanFactoryException
     */
    private function makeBeanDefinition(string $name, string $class, Collection $annotations, $fBean=false,
        $fMethod=false) : BeanDefinition {
        $def=$this->getParentBeanDefinition($class);
        if ($def === null) {
            $def=new BeanDefinition($name);
        } else {
            $def=$def->makeChildBean($name);
        }
        if ($fBean) {
            $def->setFactoryBean($fBean);
            $def->setFactoryMethod($fMethod);
        }
        $rClass=$this->reflectionFactory->getClass($class);
        if ($rClass->isAbstract()) {
            $def->makeAbstract();
        } else {
            $def->makeConcrete();
        }
        $def->setClass($class);
        $beanAnnotation=null;
        foreach ($this->validBeanAnnotations as $beanAnnotationName) {
            if ($annotations->contains($beanAnnotationName)) {
                $beanAnnotation=$annotations->getSingleAnnotation($beanAnnotationName);
                break;
            }
        }
        if (null !== $beanAnnotation) {
            if ($beanAnnotation->hasOption('class')) {
                $def->setClass($beanAnnotation->getOptionSingleValue('class'));
            }
            if ($beanAnnotation->hasOption('name')) {
                $names=$beanAnnotation->getOptionValues('name');
                foreach ($names as $alias) {
                    $def->addAlias($alias);
                }
            }
        }
        $def->setName($name);
        if ($annotations->contains('scope')) {
            $annotation=$annotations->getSingleAnnotation('scope');
            if ($annotation->hasOption('value')) {
                $scope=$annotation->getOptionSingleValue('value');
                if ($scope == 'singleton') {
                    $def->setScope(BeanDefinition::BEAN_SINGLETON);
                } else if ($scope == 'prototype') {
                    $def->setScope(BeanDefinition::BEAN_PROTOTYPE);
                } else {
                    throw new BeanFactoryException("Invalid bean scope: $scope");
                }
            }
        } else if ($annotations->contains('singleton')) {
            $def->setScope(BeanDefinition::BEAN_SINGLETON);
        } else if ($annotations->contains('prototype')) {
            $def->setScope(BeanDefinition::BEAN_PROTOTYPE);
        }
        $isPrimary=$annotations->contains('primary');
        if (!$isPrimary) {
            if ($beanAnnotation->hasOption('primary')) {
                $isPrimary=$beanAnnotation->getOptionSingleValue('primary') == 'true';
            }
        }
        if ($isPrimary) {
            $def->markAsPrimaryCandidate();
        }
        if ($annotations->contains('initmethod')) {
            $annotation=$annotations->getSingleAnnotation('initmethod');
            if ($annotation->hasOption('method')) {
                $def->setInitMethod($annotation->getOptionSingleValue('method'));
            }
        }
        if ($annotations->contains('destroymethod')) {
            $annotation=$annotations->getSingleAnnotation('destroymethod');
            if ($annotation->hasOption('method')) {
                $def->setDestroyMethod($annotation->getOptionSingleValue('method'));
            }
        }
        return $def;
    }

    /**
     * Returns all possible names for a bean. If none are found in the bean
     * annotation, the optional $overrideWithName will be chosen. If not, one
     * will be generated.
     *
     * @param AnnotationDefinition $beanAnnotation
     * @param null|string          $overrideWithName
     *
     * @return string[]
     */
    private function getAllNames(AnnotationDefinition $beanAnnotation,
        ?string $overrideWithName=null) : array {
        if ($beanAnnotation->hasOption('name')) {
            return $beanAnnotation->getOptionValues('name');
        }
        if (null !== $overrideWithName) {
            return [$overrideWithName];
        }
        return [BeanDefinition::generateName('Bean')];
    }

    /**
     * Adds a bean to $knownBeans.
     *
     * @param string     $class       The class for this bean
     * @param string     $key         Where this bean has been chosen from (i.e: component, configuration, bean, etc)
     * @param Collection $annotations Annotations for this bean
     * @param array      $options
     *                                overrideWithName Override this bean name with this one
     *                                factoryBean            An optional factory bean
     *                                factoryMethod          An optional factory method
     *
     * @return string The name of the bean recently added
     */
    private function createBean(string $class, string $key, Collection $annotations,
        array $options=[]) : string {
        $options=array_merge([
            'overrideWithName'=>null,
            'factoryBean'     =>null,
            'factoryMethod'   =>null
        ], $options);
        $fBean=$options['factoryBean'];
        $annotation=$annotations->getSingleAnnotation($key);
        $names=$this->getAllNames($annotation, $options['overrideWithName']);
        $leadName=$names[0];
        $this->createBeanToKnownByClass($class, $leadName);
        $this->knownBeans[$leadName]=[$names, $class, $key, $annotations, $fBean, $options['factoryMethod']];
        if (!$fBean) {
            $this->parentClasses[$class]=$leadName;
        }
        return $leadName;
    }

    /**
     * @param string $class
     * @param string $name
     */
    private function createBeanToKnownByClass(string $class, string $name) : void {
        if (!isset($this->knownBeansByClass[$class])) {
            $this->knownBeansByClass[$class]=[];
        }
        $this->knownBeansByClass[$class][]=$name;
        // Load any parent classes
        $rClass=$this->reflectionFactory->getClass($class);
        $parentClass=$rClass->getParentClass();
        while ($parentClass) {
            $parentClassName=$parentClass->getName();
            $this->knownBeansByClass[$parentClassName][]=$name;
            $parentClass=$parentClass->getParentClass();
        }
        // Load any interfaces
        foreach ($rClass->getInterfaces() as $interfaceName=>$rInterface) {
            $this->knownBeansByClass[$interfaceName][]=$name;
        }
    }

    /**
     * @param string $aspectBean
     * @param string $classExpression
     * @param string $expression
     * @param string $method
     * @param int    $type
     *
     * @return AspectDefinition
     */
    private function createAspect(string $aspectBean, string $classExpression, string $expression,
        string $method, int $type) : AspectDefinition {
        $pointcutName=BeanDefinition::generateName('PointcutAnnotationAspectDriver');
        $pointcutDef=new PointcutDefinition($pointcutName, $expression, $method);
        $aspectName=BeanDefinition::generateName('AnnotationAspected');
        $aspectDef=new AspectDefinition($aspectName, [$pointcutName], $type, $aspectBean, $classExpression);
        $this->aspectManager->setPointcut($pointcutDef);
        return $aspectDef;
    }

    /**
     * @param Collection $annotations
     * @param string     $beanName
     */
    private function registerEventsForBeanName(Collection $annotations, string $beanName) : void {
        if ($annotations->contains('listenson')) {
            $annotation=$annotations->getSingleAnnotation('listenson');
            foreach ($annotation->getOptionValues('value') as $eventCsv) {
                foreach (explode(',', $eventCsv) as $eventName) {
                    if (!isset($this->knownBeansPerEvent[$eventName])) {
                        $this->knownBeansPerEvent[$eventName]=[];
                    }
                    $this->knownBeansPerEvent[$eventName][]=$beanName;
                }
            }
        }
    }

    /**
     * @param string $key
     * @param array  $configClasses
     */
    private function traverseConfigClasses(string $key, array $configClasses) : void {
        foreach ($configClasses as $class) {
            $rClass=$this->reflectionFactory->getClass($class);
            $annotations=$this->reflectionFactory->getClassAnnotations($class);
            $factoryBean=$this->createBean($class, $key, $annotations);
            foreach ($rClass->getMethods() as $method) {
                $methodBeanName=$method->getName();
                $methodBeanAnnotations=$this->reflectionFactory->getMethodAnnotations($class, $methodBeanName);
                if ($methodBeanAnnotations->contains('bean')) {
                    $beanClass='stdClass';
                    $beanAnnotation=$methodBeanAnnotations->getSingleAnnotation('bean');
                    if ($beanAnnotation->hasOption('class')) {
                        $beanClass=$beanAnnotation->getOptionSingleValue('class');
                    }
                    $this->createBean($beanClass, 'bean', $methodBeanAnnotations, [
                        'overrideWithName'=>$methodBeanName,
                        'factoryBean'     =>$factoryBean,
                        'factoryMethod'   =>$methodBeanName
                    ]);
                }
            }
        }
    }
}
