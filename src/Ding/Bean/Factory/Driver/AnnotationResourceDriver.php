<?php
/**
 * This driver will search for @Resource setter methods.
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

use Ding\Bean\BeanDefinition;
use Ding\Bean\BeanPropertyDefinition;
use Ding\Bean\Lifecycle\IAfterDefinitionListener;
use Ding\Container\IContainer;
use Ding\Container\IContainerAware;
use Ding\Reflection\IReflectionFactory;
use Ding\Reflection\IReflectionFactoryAware;

/**
 * This driver will search for @Resource setter methods.
 *
 * @package Ding\Bean\Factory\Driver
 */
class AnnotationResourceDriver implements IAfterDefinitionListener, IContainerAware, IReflectionFactoryAware {
    /**
     * Container.
     *
     * @var IContainer
     */
    private $container;
    /**
     * A ReflectionFactory implementation.
     *
     * @var IReflectionFactory
     */
    protected $reflectionFactory;

    /**
     * @param IReflectionFactory $reflectionFactory
     * @see \Ding\Reflection\IReflectionFactoryAware::setReflectionFactory()
     */
    public function setReflectionFactory(IReflectionFactory $reflectionFactory) : void {
        $this->reflectionFactory=$reflectionFactory;
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
     * @param BeanDefinition $bean
     *
     * @return BeanDefinition
     * @see \Ding\Bean\Lifecycle\IAfterDefinitionListener::afterDefinition()
     */
    public function afterDefinition(BeanDefinition $bean) : BeanDefinition {
        $class=$bean->getClass();
        $rClass=$this->reflectionFactory->getClass($class);
        $properties=$bean->getProperties();
        foreach ($rClass->getMethods() as $method) {
            $methodName=$method->getName();
            if (strpos($methodName, 'set') !== 0) {
                continue;
            }
            $annotations=$this->reflectionFactory->getMethodAnnotations($class, $methodName);
            if (!$annotations->contains('resource')) {
                continue;
            }
            $propName=lcfirst(substr($methodName, 3));
            $name=$propName;
            $annotation=$annotations->getSingleAnnotation('resource');
            if ($annotation->hasOption('name')) {
                $name=$annotation->getOptionSingleValue('name');
            }
            $properties[$propName]=new BeanPropertyDefinition($propName, BeanPropertyDefinition::PROPERTY_BEAN, $name);
        }
        foreach ($rClass->getProperties() as $property) {
            $propertyName=$property->getName();
            $annotations=$this->reflectionFactory->getPropertyAnnotations($class, $propertyName);
            if (!$annotations->contains('resource')) {
                continue;
            }
            $annotation=$annotations->getSingleAnnotation('resource');
            $name=$propertyName;
            if ($annotation->hasOption('name')) {
                $name=$annotation->getOptionSingleValue('name');
            }
            $properties[$propertyName]=new BeanPropertyDefinition($propertyName, BeanPropertyDefinition::PROPERTY_BEAN, $name);
        }
        $bean->setProperties($properties);
        return $bean;
    }
}