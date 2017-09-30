<?php
/**
 * This driver will wire by type.
 *
 * @category   Ding
 * @package    Bean
 * @subpackage Factory.Driver
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
namespace Ding\Bean\Factory\Driver;

use Ding\Annotation\Annotation;
use Ding\Annotation\Collection;
use Ding\Bean\BeanConstructorArgumentDefinition;
use Ding\Bean\BeanDefinition;
use Ding\Bean\BeanPropertyDefinition;
use Ding\Bean\Factory\Exception\InjectByTypeException;
use Ding\Bean\Lifecycle\IAfterDefinitionListener;
use Ding\Container\IContainer;
use Ding\Container\IContainerAware;
use Ding\Reflection\IReflectionFactory;
use Ding\Reflection\IReflectionFactoryAware;
use ReflectionMethod;

/**
 * This driver will wire by type.
 *
 * PHP Version 5
 *
 * @category   Ding
 * @package    Bean
 * @subpackage Factory.Driver
 * @author     Marcelo Gornstein <marcelog@gmail.com>
 * @license    http://marcelog.github.com/ Apache License 2.0
 * @link       http://marcelog.github.com/
 */
class AnnotationInjectDriver implements IAfterDefinitionListener, IReflectionFactoryAware, IContainerAware {
    /**
     * A ReflectionFactory implementation.
     *
     * @var IReflectionFactory
     */
    private $reflectionFactory;
    /**
     * @var IContainer
     */
    private $container;

    /**
     * @param BeanDefinition $bean
     *
     * @return BeanDefinition
     */
    public function afterDefinition(BeanDefinition $bean) : BeanDefinition {
        $this->injectProperties($bean);
        $this->injectMethods($bean);
        $this->injectConstructorArguments($bean);
        return $bean;
    }

    /**
     * @param IContainer $container
     */
    public function setContainer(IContainer $container) : void {
        $this->container=$container;
    }

    /**
     * @param IReflectionFactory $reflectionFactory
     */
    public function setReflectionFactory(IReflectionFactory $reflectionFactory) : void {
        $this->reflectionFactory=$reflectionFactory;
    }

    /**
     * @param string          $name
     * @param Annotation      $annotation
     * @param null            $class
     * @param Annotation|null $named
     *
     * @return array|bool|mixed
     * @throws InjectByTypeException
     */
    private function inject(string $name, Annotation $annotation, $class=null, Annotation $named=null) {
        $ret=false;
        $required=true;
        if ($annotation->hasOption('required')) {
            $required=$annotation->getOptionSingleValue('required') == 'true';
        }
        if (!$annotation->hasOption('type')) {
            if ($class === null) {
                throw new InjectByTypeException($name, 'Unknown', "Missing type= specification");
            }
        } else {
            $class=$annotation->getOptionSingleValue('type');
        }
        $isArray=strpos(substr($class, -2), "[]") !== false;
        if ($isArray) {
            $class=substr($class, 0, -2);
            $ret=[];
        }
        $candidates=$this->container->getBeansByClass($class);
        if (empty($candidates)) {
            if ($required) {
                throw new InjectByTypeException($name, $class, "Did not find any candidates for injecting by type");
            }
            return [];
        }
        if (!$isArray && count($candidates) > 1) {
            $preferredName=null;
            if ($named !== null) {
                if (!$named->hasOption('name')) {
                    throw new InjectByTypeException($name, 'Unknown', "@Named needs the name= specification");
                }
                $preferredName=$named->getOptionSingleValue('name');
            }
            if ($preferredName !== null) {
                if (in_array($preferredName, $candidates)) {
                    $candidates=[$preferredName];
                } else {
                    throw new InjectByTypeException($name, 'Unknown', "Specified bean name in @Named not found");
                }
            } else {
                $foundPrimary=false;
                $beans=$candidates;
                foreach ($beans as $beanName) {
                    $beanCandidateDef=$this->container->getBeanDefinition($beanName);
                    if ($beanCandidateDef->isPrimaryCandidate()) {
                        if ($foundPrimary) {
                            throw new InjectByTypeException($name, $class, "Too many (primary) candidates for injecting by type");
                        }
                        $foundPrimary=true;
                        $candidates=[$beanName];
                    }
                }
            }
            if (count($candidates) > 1) {
                throw new InjectByTypeException($name, $class, "Too many candidates for injecting by type");
            }
        }
        if ($isArray) {
            //$propertyValue=[];
            foreach ($candidates as $value) {
                $ret[]=$value;
            }
        } else {
            $ret=array_shift($candidates);
        }
        return $ret;
    }

    /**
     * @param string $name
     * @param        $beanNames
     *
     * @return array
     */
    private function arrayToConstructorArguments(string $name, $beanNames) : array {
        $ret=[];
        $type=BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_BEAN;
        $value=$beanNames;
        if (is_array($beanNames)) {
            $value=[];
            $type=BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_ARRAY;
            foreach ($beanNames as $arg) {
                $value[]=new BeanConstructorArgumentDefinition(BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_BEAN, $arg, $name);
            }
        }
        $ret[$name]=new BeanConstructorArgumentDefinition($type, $value, $name);
        return $ret;
    }

    /**
     * @param string $name
     * @param        $beanNames
     *
     * @return array
     */
    private function arrayToBeanProperties(string $name, $beanNames) : array {
        $ret=[];
        $propertyName=$name;
        $propertyValue=$beanNames;
        $propertyType=BeanPropertyDefinition::PROPERTY_BEAN;
        if (is_array($beanNames)) {
            $propertyType=BeanPropertyDefinition::PROPERTY_ARRAY;
            $propertyValue=[];
            foreach ($beanNames as $value) {
                $propertyValue[]=new BeanPropertyDefinition($value, BeanPropertyDefinition::PROPERTY_BEAN, $value);
            }
        }
        $ret[$propertyName]=new BeanPropertyDefinition($propertyName, $propertyType, $propertyValue);
        return $ret;
    }

    /**
     * @param BeanDefinition $bean
     */
    private function injectProperties(BeanDefinition $bean) : void {
        $class=$bean->getClass();
        $rClass=$this->reflectionFactory->getClass($class);
        $properties=$bean->getProperties();
        foreach ($rClass->getProperties() as $property) {
            $propertyName=$property->getName();
            $annotations=$this->reflectionFactory->getPropertyAnnotations($class, $propertyName);
            if (!$annotations->contains('inject')) {
                continue;
            }
            $namedAnnotation=null;
            if ($annotations->contains('named')) {
                $namedAnnotation=$annotations->getSingleAnnotation('named');
            }
            $annotation=$annotations->getSingleAnnotation('inject');
            $newProperties=$this->arrayToBeanProperties($propertyName, $this->inject($propertyName, $annotation, null, $namedAnnotation));
            $properties=array_merge($properties, $newProperties);
        }
        $bean->setProperties($properties);
    }

    /**
     * @param BeanDefinition $bean
     *
     * @throws InjectByTypeException
     */
    private function injectMethods(BeanDefinition $bean) : void {
        $class=$bean->getClass();
        $rClass=$this->reflectionFactory->getClass($class);
        $properties=$bean->getProperties();
        foreach ($rClass->getMethods() as $method) {
            $methodName=$method->getName();
            $annotations=$this->reflectionFactory->getMethodAnnotations($class, $methodName);
            if (!$annotations->contains('inject') || $annotations->contains('bean') ||
                $method->isConstructor()) {
                continue;
            }
            $annotation=$annotations->getSingleAnnotation('inject');
            $namedAnnotation=null;
            if ($annotations->contains('named')) {
                $namedAnnotation=$annotations->getSingleAnnotation('named');
            }
            // Just 1 arg now. Multiple arguments need support in the container side.
            $parameters=$method->getParameters();
            if (empty($parameters)) {
                throw new InjectByTypeException($methodName, $methodName, 'Nothing to inject (no arguments in method)');
            }
            if (count($parameters) > 1) {
                throw new InjectByTypeException($methodName, $methodName, 'Multiple arguments are not yet supported');
            }
            $type=array_shift($parameters);
            $type=$type->getClass();
            if ($type !== null) {
                $type=$type->getName();
            }
            $newProperties=$this->arrayToBeanProperties($methodName, $this->inject($methodName, $annotation, $type, $namedAnnotation));
            $properties=array_merge($properties, $newProperties);
        }
        $bean->setProperties($properties);
    }

    /**
     * @param ReflectionMethod $rMethod
     * @param Collection       $beanAnnotations
     * @param BeanDefinition   $bean
     *
     * @throws InjectByTypeException
     */
    private function applyToConstructor(ReflectionMethod $rMethod, Collection $beanAnnotations,
        BeanDefinition $bean) : void {
        $constructorArguments=$bean->getArguments();
        if (!$beanAnnotations->contains('inject')) {
            return;
        }
        $annotations=$beanAnnotations->getAnnotations('inject');
        foreach ($annotations as $annotation) {
            if ($annotation->hasOption('type')) {
                if (!$annotation->hasOption('name')) {
                    throw new InjectByTypeException('constructor', 'Unknown', 'Cant specify type without name');
                }
            }
            if ($annotation->hasOption('name')) {
                if (!$annotation->hasOption('type')) {
                    throw new InjectByTypeException('constructor', 'Unknown', 'Cant specify name without type');
                }
                $name=$annotation->getOptionSingleValue('name');
                $type=$annotation->getOptionSingleValue('type');
                $namedAnnotation=null;
                if ($beanAnnotations->contains('named')) {
                    foreach ($beanAnnotations->getAnnotations('named') as $namedAnnotationCandidate) {
                        if ($namedAnnotationCandidate->hasOption('arg')) {
                            $target=$namedAnnotationCandidate->getOptionSingleValue('arg');
                            if ($target == $name) {
                                $namedAnnotation=$namedAnnotationCandidate;
                            }
                        }
                    }
                }
                $newArgs=$this->inject($name, $annotation, $type, $namedAnnotation);
                $constructorArguments=array_merge($constructorArguments, $this->arrayToConstructorArguments($name, $newArgs));
            } else {
                foreach ($rMethod->getParameters() as $parameter) {
                    $parameterName=$parameter->getName();
                    $type=$parameter->getClass();
                    if ($type === null) {
                        continue;
                    }
                    $type=$type->getName();
                    $namedAnnotation=null;
                    if ($beanAnnotations->contains('named')) {
                        foreach ($beanAnnotations->getAnnotations('named') as $namedAnnotationCandidate) {
                            if ($namedAnnotationCandidate->hasOption('arg')) {
                                $target=$namedAnnotationCandidate->getOptionSingleValue('arg');
                                if ($target == $parameterName) {
                                    $namedAnnotation=$namedAnnotationCandidate;
                                }
                            }
                        }
                    }
                    $newArgs=$this->inject($parameterName, $annotation, $type, $namedAnnotation);
                    $constructorArguments=array_merge($constructorArguments, $this->arrayToConstructorArguments($parameterName, $newArgs));
                }
            }
        }
        $bean->setArguments($constructorArguments);
    }

    /**
     * @param BeanDefinition $bean
     */
    private function injectConstructorArguments(BeanDefinition $bean) : void {
        if ($bean->isCreatedWithFactoryBean()) {
            $factoryMethod=$bean->getFactoryMethod();
            $factoryBean=$bean->getFactoryBean();
            $def=$this->container->getBeanDefinition($factoryBean);
            $class=$def->getClass();
            $rMethod=$this->reflectionFactory->getMethod($class, $factoryMethod);
            $annotations=$this->reflectionFactory->getMethodAnnotations($class, $factoryMethod);
            $this->applyToConstructor($rMethod, $annotations, $bean);
            return;
        }
        if ($bean->isCreatedByConstructor()) {
            $class=$bean->getClass();
            $rClass=$this->reflectionFactory->getClass($class);
            $rMethod=$rClass->getConstructor();
            if ($rMethod) {
                $annotations=$this->reflectionFactory->getMethodAnnotations($class, $rMethod->getName());
                $this->applyToConstructor($rMethod, $annotations, $bean);
            }
        }
    }
}
