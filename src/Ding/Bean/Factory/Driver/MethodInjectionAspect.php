<?php
/**
 * This driver will take care of the method injection.
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

use Ding\Aspect\MethodInvocation;
use Ding\Container\IContainer;
use Ding\Container\IContainerAware;

/**
 * An "inner" class. This is the aspect that runs when the method is called.
 * Enter description here ...
 *
 * @package Ding\Bean\Factory\Driver
 */
class MethodInjectionAspect implements IContainerAware {
    /**
     * Container.
     *
     * @var IContainer
     */
    private $container;
    /**
     * Bean to generate.
     *
     * @var string
     */
    private $beanName;

    /**
     * Setter injection for bean name.
     *
     * @param string $beanName Bean name.
     */
    public function setBeanName(string $beanName):void {
        $this->beanName=$beanName;
    }

    /**
     * @param IContainer $container
     */
    public function setContainer(IContainer $container):void {
        $this->container=$container;
    }

    /**
     * Creates a new bean (prototypes).
     *
     * @param MethodInvocation $invocation The call.
     *
     * @return object
     */
    public function invoke(MethodInvocation $invocation) {
        return $this->container->getBean($this->beanName);
    }
}