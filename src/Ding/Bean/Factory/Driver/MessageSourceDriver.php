<?php
/**
 * This driver will inject the MessageSource to the beans that implement
 * IMessageSourceAware
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
use Ding\Bean\Lifecycle\IAfterConfigListener;
use Ding\Bean\Lifecycle\IAfterCreateListener;
use Ding\Container\IContainer;
use Ding\Container\IContainerAware;
use Ding\Container\Impl\ContainerImpl;
use Ding\MessageSource\IMessageSource;
use Ding\MessageSource\IMessageSourceAware;
use Ding\Reflection\IReflectionFactory;
use Ding\Reflection\IReflectionFactoryAware;

/**
 * This driver will inject the MessageSource to the beans that implement
 * IMessageSourceAware
 *
 * @package Ding\Bean\Factory\Driver
 */
class MessageSourceDriver implements IAfterConfigListener, IAfterCreateListener, IContainerAware, IReflectionFactoryAware {
    /**
     * Container.
     *
     * @var ContainerImpl
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

    public function afterConfig() : void {
        $bean=$this->container->getBean('messageSource');
        if ($bean instanceof IMessageSource) {
            $this->container->setMessageSource($bean);
        }
    }

    /**
     * @param                $bean
     * @param BeanDefinition $beanDefinition
     */
    public function afterCreate($bean, BeanDefinition $beanDefinition) : void {
        //$rClass=$this->reflectionFactory->getClass(get_class($bean));
        //if ($rClass->implementsInterface('Ding\MessageSource\IMessageSourceAware')) {
        if ($bean instanceof IMessageSourceAware) {
            $bean->setMessageSource($this->container);
        }
        //}
        //return $bean;
    }
}
