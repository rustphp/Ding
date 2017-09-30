<?php
/**
 * This driver will search for @Value
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

use Ding\Annotation\Collection;
use Ding\Bean\BeanConstructorArgumentDefinition;
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
class AnnotationValueDriver implements IAfterDefinitionListener, IContainerAware, IReflectionFactoryAware {
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
     */
    public function setReflectionFactory(IReflectionFactory $reflectionFactory) : void {
        $this->reflectionFactory=$reflectionFactory;
    }

    /**
     * @param IContainer $container
     */
    public function setContainer(IContainer $container) : void {
        $this->container=$container;
    }

    /**
     * @param Collection     $annotations
     * @param BeanDefinition $bean
     */
    private function applyToConstructor(Collection $annotations, BeanDefinition $bean) : void {
        $constructorArguments=$bean->getArguments();
        if ($annotations->contains('value')) {
            foreach ($annotations->getAnnotations('value') as $annotation) {
                if ($annotation->hasOption('value')) {
                    foreach ($annotation->getOptionValues('value') as $value) {
                        $argName=false;
                        if ($annotation->hasOption('name')) {
                            $argName=$annotation->getOptionSingleValue('name');
                        }
                        $constructorArguments[]=new BeanConstructorArgumentDefinition(BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_VALUE, $value, $argName);
                    }
                }
            }
        }
        $bean->setArguments($constructorArguments);
    }

    /**
     * @param BeanDefinition $bean
     *
     * @return BeanDefinition
     */
    public function afterDefinition(BeanDefinition $bean) : BeanDefinition {
        $properties=$bean->getProperties();
        $class=$bean->getClass();
        $rClass=$this->reflectionFactory->getClass($class);
        foreach ($rClass->getProperties() as $rProperty) {
            $propertyName=$rProperty->getName();
            $annotations=$this->reflectionFactory->getPropertyAnnotations($class, $propertyName);
            if ($annotations->contains('value')) {
                $annotation=$annotations->getSingleAnnotation('value');
                if ($annotation->hasOption('value')) {
                    $value=$annotation->getOptionSingleValue('value');
                    $properties[$propertyName]=new BeanPropertyDefinition($propertyName, BeanPropertyDefinition::PROPERTY_SIMPLE, $value);
                }
            }
        }
        $bean->setProperties($properties);
        if ($bean->isCreatedWithFactoryBean()) {
            $factoryMethod=$bean->getFactoryMethod();
            $factoryBean=$bean->getFactoryBean();
            $def=$this->container->getBeanDefinition($factoryBean);
            $annotations=$this->reflectionFactory->getMethodAnnotations($def->getClass(), $factoryMethod);
            $this->applyToConstructor($annotations, $bean);
        } else if ($bean->isCreatedByConstructor()) {
            $class=$bean->getClass();
            $rClass=$this->reflectionFactory->getClass($bean->getClass());
            $rMethod=$rClass->getConstructor();
            if ($rMethod) {
                $annotations=$this->reflectionFactory->getMethodAnnotations($class, $rMethod->getName());
                $this->applyToConstructor($annotations, $bean);
            }
        }
        return $bean;
    }
}