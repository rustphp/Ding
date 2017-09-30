<?php
/**
 * This driver will search for @Required setter methods.
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
use Ding\Bean\Factory\Exception\BeanFactoryException;
use Ding\Bean\Lifecycle\IAfterDefinitionListener;
use Ding\Reflection\IReflectionFactory;
use Ding\Reflection\IReflectionFactoryAware;

/**
 * This driver will search for @Required setter methods.
 *
 * @package Ding\Bean\Factory\Driver
 */
class AnnotationRequiredDriver implements IAfterDefinitionListener, IReflectionFactoryAware {
    /**
     * A ReflectionFactory implementation.
     *
     * @var IReflectionFactory
     */
    protected $reflectionFactory;

    /**
     * @param IReflectionFactory $reflectionFactory
     */
    public function setReflectionFactory(IReflectionFactory $reflectionFactory):void {
        $this->reflectionFactory=$reflectionFactory;
    }

    /**
     * @param BeanDefinition $bean
     *
     * @return BeanDefinition
     * @throws BeanFactoryException
     */
    public function afterDefinition(BeanDefinition $bean) : BeanDefinition {
        $class=$bean->getClass();
        $rClass=$this->reflectionFactory->getClass($class);
        //$annotations=$this->reflectionFactory->getClassAnnotations($class);
        $props=$bean->getProperties();
        foreach ($rClass->getMethods() as $rMethod) {
            $methodName=$rMethod->getName();
            if (strpos($methodName, 'set') !== 0) {
                continue;
            }
            $annotations=$this->reflectionFactory->getMethodAnnotations($class, $methodName);
            if (!$annotations->contains('required')) {
                continue;
            }
            $propName=lcfirst(substr($methodName, 3));
            if (!isset($props[$propName])) {
                throw new BeanFactoryException('Missing @Required property: ' . $methodName);
            }
        }
        return $bean;
    }
}