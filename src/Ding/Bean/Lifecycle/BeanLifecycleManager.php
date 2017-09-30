<?php
/**
 * The listeners manager for the container.
 *
 * @category   Ding
 * @package    Bean
 * @subpackage Lifecycle
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
namespace Ding\Bean\Lifecycle;

use Ding\Bean\BeanDefinition;

/**
 * The listeners manager for the container.
 *
 * @package Ding\Bean\Lifecycle
 */
class BeanLifecycleManager {
    /**
     * Lifecycle handlers for beans.
     *
     * @var BeanLifecycle[]
     */
    private $listeners;

    /**
     * Constructor
     */
    public function __construct() {
        $this->listeners=[
            BeanLifecycle::AfterConfig    =>[],
            BeanLifecycle::AfterDefinition=>[],
            BeanLifecycle::BeforeCreate   =>[],
            BeanLifecycle::AfterCreate    =>[],
            BeanLifecycle::BeforeAssemble =>[],
            BeanLifecycle::AfterAssemble  =>[]
        ];
    }

    /**
     * Serialization
     *
     * @return array
     */
    public function __sleep() {
        return ['listeners'];
    }

    /**
     * Adds a listener to the AfterConfig point.
     *
     * @param IAfterConfigListener $listener Listener to add
     *
     * @return void
     */
    public function addAfterConfigListener(IAfterConfigListener $listener) {
        $this->listeners[BeanLifecycle::AfterConfig][]=$listener;
    }

    /**
     * Adds a listenersr to the AfterDefinition point.
     *
     * @param IAfterDefinitionListener $listener Listener to add
     *
     * @return void
     */
    public function addAfterDefinitionListener(IAfterDefinitionListener $listener) {
        $this->listeners[BeanLifecycle::AfterDefinition][]=$listener;
    }

    /**
     * Adds a listenersr to the BeforeCreate point.
     *
     * @param IBeforeCreateListener $listener Listener to add
     *
     * @return void
     */
    public function addBeforeCreateListener(IBeforeCreateListener $listener) {
        $this->listeners[BeanLifecycle::BeforeCreate][]=$listener;
    }

    /**
     * Adds a listenersr to the AfterCreate point.
     *
     * @param IAfterCreateListener $listener Listener to add
     *
     * @return void
     */
    public function addAfterCreateListener(IAfterCreateListener $listener) {
        $this->listeners[BeanLifecycle::AfterCreate][]=$listener;
    }

    /**
     * Adds a listenersr to the BeforeAssemble point.
     *
     * @param IBeforeAssembleListener $listener Listener to add
     *
     * @return void
     */
    public function addBeforeAssembleListener(IBeforeAssembleListener $listener) {
        $this->listeners[BeanLifecycle::BeforeAssemble][]=$listener;
    }

    /**
     * Adds a listenersr to the AfterAssemble point.
     *
     * @param IAfterAssembleListener $listener Listener to add
     *
     * @return void
     */
    public function addAfterAssembleListener(IAfterAssembleListener $listener) {
        $this->listeners[BeanLifecycle::AfterAssemble][]=$listener;
    }

    /**
     * Runs the AfterDefinition point of the listeners.
     *
     * @param BeanDefinition $bean Actual definition.
     *
     * @return BeanDefinition
     */
    public function afterDefinition(BeanDefinition $bean) : BeanDefinition {
        $return=$bean;
        foreach ($this->listeners[BeanLifecycle::AfterDefinition] as $listenersListener) {
            if ($listenersListener instanceof IAfterDefinitionListener) {
                $return=$listenersListener->afterDefinition($return);
            }
        }
        return $return;
    }

    /**
     * Runs the BeforeCreate point of the listeners.
     *
     * @param BeanDefinition $beanDefinition Actual definition.
     *
     * @return void
     */
    public function beforeCreate(BeanDefinition $beanDefinition) : void {
        foreach ($this->listeners[BeanLifecycle::BeforeCreate] as $listenersListener) {
            if ($listenersListener instanceof IBeforeCreateListener) {
                $listenersListener->beforeCreate($beanDefinition);
            }
        }
    }

    /**
     * Runs the AfterCreate point of the listeners.
     *
     * @param                $bean
     * @param BeanDefinition $beanDefinition Actual definition.
     */
    public function afterCreate($bean, BeanDefinition $beanDefinition) : void {
        foreach ($this->listeners[BeanLifecycle::AfterCreate] as $listenersListener) {
            if ($listenersListener instanceof IAfterCreateListener) {
                $listenersListener->afterCreate($bean, $beanDefinition);
            }
        }
    }

    /**
     * Runs the BeforeAssemble point of the listeners.
     *
     * @param                $bean
     * @param BeanDefinition $beanDefinition Actual definition.
     */
    public function beforeAssemble($bean, BeanDefinition $beanDefinition) : void {
        foreach ($this->listeners[BeanLifecycle::BeforeAssemble] as $listenersListener) {
            if ($listenersListener instanceof IBeforeAssembleListener) {
                $listenersListener->beforeAssemble($bean, $beanDefinition);
            }
        }
    }

    /**
     * Runs the AfterAssemble point of the listeners.
     *
     * @param                $bean
     * @param BeanDefinition $beanDefinition Actual definition.
     */
    public function afterAssemble($bean, BeanDefinition $beanDefinition) : void {
        foreach ($this->listeners[BeanLifecycle::AfterAssemble] as $listenersListener) {
            if ($listenersListener instanceof IAfterAssembleListener) {
                $listenersListener->afterAssemble($bean, $beanDefinition);
            }
        }
    }

    /**
     * Runs the AfterConfig point of the listeners.
     */
    public function afterConfig() : void {
        foreach ($this->listeners[BeanLifecycle::AfterConfig] as $listenersListener) {
            if ($listenersListener instanceof IAfterConfigListener) {
                $listenersListener->afterConfig();
            }
        }
    }
}
