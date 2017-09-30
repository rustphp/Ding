<?php
/**
 * YAML bean factory.
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

use Ding\Aspect\AspectDefinition;
use Ding\Aspect\AspectManager;
use Ding\Aspect\IAspectManagerAware;
use Ding\Aspect\IAspectProvider;
use Ding\Aspect\IPointcutProvider;
use Ding\Aspect\PointcutDefinition;
use Ding\Bean\BeanConstructorArgumentDefinition;
use Ding\Bean\BeanDefinition;
use Ding\Bean\BeanPropertyDefinition;
use Ding\Bean\Factory\Exception\BeanFactoryException;
use Ding\Bean\IBeanDefinitionProvider;
use Ding\Bean\Lifecycle\IAfterConfigListener;
use Ding\Container\IContainer;
use Ding\Container\IContainerAware;
use Ding\Logger\ILoggerAware;
use Ding\Logger\Logger;
use Ding\Reflection\IReflectionFactory;
use Ding\Reflection\IReflectionFactoryAware;

/**
 * YAML bean factory.
 *
 * @package Ding\Bean\Provider
 */
class Yaml implements IAfterConfigListener, IAspectProvider, IPointcutProvider, IBeanDefinitionProvider, IAspectManagerAware, IContainerAware, ILoggerAware, IReflectionFactoryAware {
    /**
     * @var IContainer $container
     */
    private $container;
    /**
     * log4php logger or our own.
     *
     * @var Logger
     */
    private $logger;
    /**
     * beans.yaml file path.
     *
     * @var string
     */
    private $yamlFileName;
    /**
     * Yaml contents.
     *
     * @var null|array
     */
    private $yamlFiles;
    /**
     * Bean definition template to clone.
     *
     * @var BeanDefinition
     */
    private $templateBeanDef;
    /**
     * Bean property definition template to clone.
     *
     * @var BeanPropertyDefinition
     */
    private $templatePropDef;
    /**
     * Bean constructor argument definition template to clone.
     *
     * @var BeanConstructorArgumentDefinition
     */
    private $templateArgDef;
    /**
     * Aspect definition template to clone.
     *
     * @var AspectDefinition
     */
    private $templateAspectDef;
    /**
     * Pointcut definition template to clone.
     *
     * @var PointcutDefinition
     */
    private $templatePointcutDef;
    /**
     * The aspect manager.
     *
     * @var AspectManager
     */
    private $aspectManager=false;
    /**
     * Optional directories to search for bean files.
     *
     * @var string[]
     */
    private $directories=false;
    /**
     * Bean aliases, pre-scanned
     *
     * @var string[]
     */
    private $beanAliases=[];
    /**
     * @var array
     */
    private $beanDefs=[];
    /**
     * Maps beans from their classes.
     *
     * @var string[]
     */
    private $knownBeansByClass =[];
    private $knownBeansPerEvent=[];
    /**
     * @var IReflectionFactory
     */
    private $reflectionFactory;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(array $options) {
        $this->beanDefs=[];
        $this->yamlFileName=$options['filename'];
        $this->directories=isset($options['directories']) ? $options['directories'] : ['.'];
        $this->yamlFiles=null;
        $this->templateBeanDef=new BeanDefinition('');
        $this->templatePropDef=new BeanPropertyDefinition('', 0, null);
        $this->templateArgDef=new BeanConstructorArgumentDefinition(0, null);
        $this->templateAspectDef=new AspectDefinition('', [], 0, '', '');
        $this->templatePointcutDef=new PointcutDefinition('', '', '');
    }

    /**
     * Serialization.
     *
     * @return array
     */
    public function __sleep() {
        return [];
    }

    public function afterConfig() : void {
        $this->load();
        if (!$this->yamlFiles || !is_array($this->yamlFiles)) {
            return;
        }
        foreach ($this->yamlFiles as $yamlFilename=>$yaml) {
            if (isset($yaml['alias'])) {
                foreach ($yaml['alias'] as $beanName=>$aliases) {
                    $aliases=explode(',', $aliases);
                    foreach ($aliases as $alias) {
                        $alias=trim($alias);
                        $this->beanAliases[$alias]=$beanName;
                    }
                }
            }
            if (isset($yaml['beans'])) {
                foreach ($yaml['beans'] as $beanName=>$beanDef) {
                    if (isset($beanDef['class'])) {
                        $class=$beanDef['class'];
                        if (isset($beanDef['factory-method'])) {
                            // Skip beans that specify class as their factory class
                            if (isset($beanDef['factory-bean'])) {
                                $this->addBeanToKnownByClass($class, $beanName);
                            }
                        } else {
                            $this->addBeanToKnownByClass($class, $beanName);
                        }
                    }
                    if (isset($beanDef['name'])) {
                        $aliases=explode(',', $beanDef['name']);
                        foreach ($aliases as $alias) {
                            $alias=trim($alias);
                            $this->beanAliases[$alias]=$beanName;
                        }
                    }
                    if (isset($beanDef['listens-on'])) {
                        $events=$beanDef['listens-on'];
                        foreach (explode(',', $events) as $eventName) {
                            $eventName=trim($eventName);
                            if (!isset($this->knownBeansPerEvent[$eventName])) {
                                $this->knownBeansPerEvent[$eventName]=[];
                            }
                            $this->knownBeansPerEvent[$eventName][]=$beanName;
                        }
                    }
                }
            }
        }
    }

    /**
     * @return AspectDefinition[]
     */
    public function getAspects() : array {
        $aspectDefinitions=[];
        $this->load();
        foreach ($this->yamlFiles as $yamlFilename=>$yaml) {
            $aspects=$yaml['aspects'] ?? [];
            if ($aspects) {
                foreach ($aspects as $aspect) {
                    $aspectDefinitions[]=$this->loadAspect($aspect);
                }
            }
        }
        return $aspectDefinitions;
    }

    /**
     * @param string $beanName
     *
     * @return BeanDefinition|null
     * @throws BeanFactoryException
     * @see \Ding\Aspect\IBeanDefinitionProvider::getBeanDefinition()
     */
    public function getBeanDefinition(string $beanName) :?BeanDefinition {
        $beanDef=null;
        if (isset($this->beanAliases[$beanName])) {
            return $this->getBeanDefinition($this->beanAliases[$beanName]);
        }
        if (!$this->yamlFiles || !is_array($this->yamlFiles)) {
            return null;
        }
        foreach ($this->yamlFiles as $yamlFilename=>$yaml) {
            if (isset($yaml['beans'][$beanName])) {
                $beanDef=$yaml['beans'][$beanName];
                break;
            }
        }
        if (null === $beanDef) {
            return null;
        }
        $yamlFilename=empty($yamlFilename) ? '' : $yamlFilename;
        $bMethods=$bProps=$bAspects=$constructorArgs=[];
        if (isset($beanDef['parent'])) {
            $bean=$this->container->getBeanDefinition($beanDef['parent']);
            $bean=$bean->makeChildBean($beanName);
            $bProps=$bean->getProperties();
            $constructorArgs=$bean->getArguments();
            $bAspects=$bean->getAspects();
            $bMethods=$bean->getMethodInjections();
        } else {
            $bean=clone $this->templateBeanDef;
        }
        $bean->setName($beanName);
        if (isset($beanDef['class'])) {
            $bean->setClass($beanDef['class']);
        }
        if (isset($beanDef['scope'])) {
            if ($beanDef['scope'] == 'prototype') {
                $bean->setScope(BeanDefinition::BEAN_PROTOTYPE);
            } else if ($beanDef['scope'] == 'singleton') {
                $bean->setScope(BeanDefinition::BEAN_SINGLETON);
            } else {
                throw new BeanFactoryException('Invalid bean scope: ' . $beanDef['scope']);
            }
        }
        if (isset($beanDef['primary'])) {
            $primary=$beanDef['primary'];
            if ($primary == 'true') {
                $bean->markAsPrimaryCandidate();
            }
        }
        if (isset($beanDef['factory-method'])) {
            $bean->setFactoryMethod($beanDef['factory-method']);
        }
        if (isset($beanDef['depends-on'])) {
            $bean->setDependsOn(explode(',', $beanDef['depends-on']));
        }
        if (isset($beanDef['abstract'])) {
            if ($beanDef['abstract'] == 'true') {
                $bean->makeAbstract();
            }
        }
        if (isset($beanDef['factory-bean'])) {
            $bean->setFactoryBean($beanDef['factory-bean']);
        }
        if (isset($beanDef['init-method'])) {
            $bean->setInitMethod($beanDef['init-method']);
        }
        if (isset($beanDef['destroy-method'])) {
            $bean->setDestroyMethod($beanDef['destroy-method']);
        }
        if (isset($beanDef['properties'])) {
            foreach ($beanDef['properties'] as $name=>$value) {
                $bProp=$this->loadProperty($name, $value, $yamlFilename);
                $bProps[$name]=$bProp;
            }
        }
        if (isset($beanDef['constructor-args'])) {
            foreach ($beanDef['constructor-args'] as $name=>$arg) {
                $constructorArgs[]=$this->loadConstructorArg($name, $arg, $yamlFilename);
            }
        }
        if (isset($beanDef['aspects'])) {
            foreach ($beanDef['aspects'] as $name=>$aspect) {
                $aspect['id']=$name;
                $aspectDefinition=$this->loadAspect($aspect);
                $bAspects[]=$aspectDefinition;
            }
        }
        if (isset($beanDef['lookup-methods'])) {
            foreach ($beanDef['lookup-methods'] as $name=>$beanName) {
                $bMethods[]=[$name, $beanName];
            }
        }
        if (!empty($bProps)) {
            $bean->setProperties($bProps);
        }
        if (!empty($bAspects)) {
            $bean->setAspects($bAspects);
        }
        if (!empty($constructorArgs)) {
            $bean->setArguments($constructorArgs);
        }
        if (!empty($bMethods)) {
            $bean->setMethodInjections($bMethods);
        }
        return $bean;
    }

    /**
     * @param string $class
     *
     * @return array
     * @see \Ding\Aspect\IBeanDefinitionProvider::getBeanDefinitionByClass()
     */
    public function getBeansByClass(string $class) : array {
        $beans=$this->knownBeansByClass[$class] ?? [];
        return (is_array($beans) ? $beans : []);
    }

    /**
     * @param AspectManager $aspectManager
     *
     * @see \Ding\Aspect\IAspectManagerAware::setAspectManager()
     */
    public function setAspectManager(AspectManager $aspectManager) : void {
        $this->aspectManager=$aspectManager;
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
     * @param Logger $logger
     *
     * @see \Ding\Logger\ILoggerAware::setLogger()
     */
    public function setLogger(Logger $logger) : void {
        $this->logger=$logger;
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
     * @param string $name
     *
     * @return null|PointcutDefinition
     */
    public function getPointcut(string $name) :?PointcutDefinition {
        foreach ($this->yamlFiles as $yamlFilename=>$yaml) {
            if (isset($yaml['pointcuts'][$name])) {
                $pointcutDef=clone $this->templatePointcutDef;
                $pointcutDef->setName($name);
                $pointcutDef->setExpression($yaml['pointcuts'][$name]['expression']);
                $pointcutDef->setMethod($yaml['pointcuts'][$name]['method']);
                return $pointcutDef;
            }
        }
        return null;
    }

    /**
     * @param string $eventName
     *
     * @return array
     * @see \Ding\Bean\IBeanDefinitionProvider::getBeansListeningOn()
     */
    public function getBeansListeningOn(string $eventName) : array {
        $beans=$this->knownBeansPerEvent[$eventName] ?? [];
        return (is_array($beans) ? $beans : []);
    }

    /**
     * Initializes yaml contents.
     *
     * @param string|array $filename
     *
     * @throws BeanFactoryException
     * @return mixed
     */
    private function loadYaml($filename) {
        if (!function_exists('yaml_parse')) {
            throw new BeanFactoryException('yaml extension not found! ');
        }
        $result=[];
        if (is_array($filename)) {
            foreach ($filename as $file) {
                foreach ($this->loadYaml($file) as $name=>$yaml) {
                    $result[$name]=$yaml;
                }
            }
            return $result;
        }
        $contents=false;
        foreach ($this->directories as $directory) {
            $fullName=$directory . DIRECTORY_SEPARATOR . $filename;
            if (!file_exists($fullName)) {
                continue;
            }
            $contents=file_get_contents($fullName);
        }
        if ($contents === false) {
            throw new BeanFactoryException($filename . ' not found in ' . print_r($this->directories, true));
        }
        $ret=yaml_parse($contents);
        if ($ret === false) {
            return $ret;
        }
        $result[$filename]=$ret;
        if (isset($ret['import'])) {
            foreach ($ret['import'] as $imported) {
                foreach ($this->loadYaml($imported) as $name=>$yaml) {
                    $result[$name]=$yaml;
                }
            }
        }
        return $result;
    }

    /**
     * Returns an aspect definition.
     *
     * @param mixed[] $aspect Aspect data.
     *
     * @throws BeanFactoryException
     * @return AspectDefinition
     */
    private function loadAspect(array $aspect) : AspectDefinition {
        $name=$aspect['id'] ?? BeanDefinition::generateName('AspectYAML');
        $expression=$aspect['expression'] ?? '';
        $aspectBean=$aspect['ref'];
        $type=$aspect['type'];
        if ($type == 'method') {
            $type=AspectDefinition::ASPECT_METHOD;
        } else if ($type == 'exception') {
            $type=AspectDefinition::ASPECT_EXCEPTION;
        } else {
            throw new BeanFactoryException('Invalid aspect type');
        }
        $pointcuts=[];
        foreach ($aspect['pointcuts'] as $pointcut) {
            if (isset($pointcut['id'])) {
                $pointcutName=$pointcut['id'];
            } else {
                $pointcutName=BeanDefinition::generateName('PointcutYAML');
            }
            if (isset($pointcut['expression'])) {
                $pointcutDef=clone $this->templatePointcutDef;
                $pointcutDef->setName($pointcutName);
                $pointcutDef->setExpression($pointcut['expression']);
                $pointcutDef->setMethod($pointcut['method']);
                $this->aspectManager->setPointcut($pointcutDef);
                $pointcuts[]=$pointcutName;
            } else if (isset($pointcut['pointcut-ref'])) {
                $pointcuts[]=$pointcut['pointcut-ref'];
            }
        }
        return new AspectDefinition($name, $pointcuts, $type, $aspectBean, $expression);
    }

    /**
     * Returns a property definition.
     *
     * @param string $name         Property name.
     * @param mixed  $value        Property YAML structure value.
     * @param string $yamlFilename Filename for yaml file.
     *
     * @throws BeanFactoryException
     * @return BeanPropertyDefinition
     */
    private function loadProperty(string $name, $value, string $yamlFilename) : BeanPropertyDefinition {
        $propType=BeanPropertyDefinition::PROPERTY_SIMPLE;
        $propValue=$value['value'];
        if (isset($value['ref'])) {
            $propType=BeanPropertyDefinition::PROPERTY_BEAN;
            $propValue=$value['ref'];
        } else if (isset($value['eval'])) {
            $propType=BeanPropertyDefinition::PROPERTY_CODE;
            $propValue=$value['eval'];
        } else if (isset($value['bean'])) {
            $propType=BeanPropertyDefinition::PROPERTY_BEAN;
            $innerBean=BeanDefinition::generateName('Bean');
            $this->yamlFiles[$yamlFilename]['beans'][$innerBean]=$value['bean'];
            $propValue=$innerBean;
        } else if (is_array($value['value'])) {
            $propType=BeanPropertyDefinition::PROPERTY_ARRAY;
            $propValue=[];
            foreach ($value['value'] as $key=>$inValue) {
                $propValue[$key]=$this->loadProperty($key, $inValue, $yamlFilename);
            }
        }
        return new BeanPropertyDefinition($name, $propType, $propValue);
    }

    /**
     * Returns a constructor argument definition.
     *
     * @param        $name
     * @param mixed  $value        Constructor arg YAML structure value.
     * @param string $yamlFilename Filename for yaml file.
     *
     * @return BeanConstructorArgumentDefinition
     */
    private function loadConstructorArg($name, $value,
        string $yamlFilename) : BeanConstructorArgumentDefinition {
        $argType=BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_VALUE;
        $argValue=$value;
        $argName=(is_string($name) ? $name : null);
        if (is_array($value)) {
            if (isset($value['ref'])) {
                $argType=BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_BEAN;
                $argValue=$value['ref'];
            } else if (isset($value['eval'])) {
                $argType=BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_CODE;
                $argValue=$value['eval'];
            } else if (isset($value['bean'])) {
                $argType=BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_BEAN;
                $innerBean=BeanDefinition::generateName('Bean');
                $this->yamlFiles[$yamlFilename]['beans'][$innerBean]=$value['bean'];
                $argValue=$innerBean;
            } else {
                $argType=BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_ARRAY;
                $argValue=[];
                foreach ($value as $key=>$inValue) {
                    $argValue[$key]=$this->loadConstructorArg(false, $inValue, $yamlFilename);
                }
            }
        }
        return new BeanConstructorArgumentDefinition($argType, $argValue, $argName);
    }

    /**
     * Initialize YAML contents.
     *
     * @throws BeanFactoryException
     */
    private function load() : void {
        if ($this->yamlFiles !== false) {
            return;
        }
        $this->yamlFiles=$this->loadYaml($this->yamlFileName);
        if (empty($this->yamlFiles)) {
            throw new BeanFactoryException('Could not parse: ' . $this->yamlFileName);
        }
    }

    /**
     * @param string $class
     * @param string $name
     */
    private function addBeanToKnownByClass(string $class, string $name) : void {
        if (strpos($class, "\${") !== false) {
            return;
        }
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
}
