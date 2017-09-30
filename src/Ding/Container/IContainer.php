<?php
/**
 * Interface for a container.
 *
 * @category Ding
 * @package  Container
 * @author   Marcelo Gornstein <marcelog@gmail.com>
 * @license  http://marcelog.github.com/ Apache License 2.0
 * @link     http://marcelog.github.com/
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
namespace Ding\Container;

use Ding\Bean\IBeanDefinitionProvider;
use Ding\MessageSource\IMessageSource;
use Ding\Resource\IResourceLoader;

/**
 * Interface for a container.
 *
 * @package Ding\Container
 */
interface IContainer extends IResourceLoader, IMessageSource, IBeanDefinitionProvider {
    /**
     * Register a shutdown (destroy-method) method for a bean.
     *
     * @param object $bean   Bean to call.
     * @param string $method Method to call.
     */
    public function registerShutdownMethod($bean, $method) : void;

    /**
     * Dispatch an event to all listeners.
     *
     * @param string $eventName The event name.
     * @param mixed  $data      The associated data to the event.
     */
    public function eventDispatch(string $eventName, $data=null) : void;

    /**
     * Register a new listener to an event. The callback must implement a
     * method named "onEventName($data)".
     *
     * @param string $eventName The event name.
     * @param string $beanName  The event handler.
     */
    public function eventListen(string $eventName, string $beanName) : void;

    /**
     * Registers a new bean definition provider in the container.
     *
     * @param IBeanDefinitionProvider $provider New bean definition provider.
     */
    public function registerBeanDefinitionProvider(IBeanDefinitionProvider $provider) : void;

    /**
     * Register new properties in the container, that will replace the value
     * for bean's constructor arguments, properties, etc. Will also set any
     * php. properties with ini_set().
     *
     * @param string[] $properties
     */
    public function registerProperties(array $properties) : void;

    /**
     * Returns a bean.
     *
     * @param string $name Bean name.
     */
    public function getBean(string $name);
}
