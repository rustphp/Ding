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

use Ding\Aspect\AspectDefinition;
use Ding\Aspect\AspectManager;
use Ding\Aspect\IAspectManagerAware;
use Ding\Aspect\PointcutDefinition;
use Ding\Bean\BeanDefinition;
use Ding\Bean\BeanPropertyDefinition;
use Ding\Bean\IBeanDefinitionProvider;
use Ding\Bean\Lifecycle\IAfterDefinitionListener;
use Ding\Container\IContainer;
use Ding\Container\IContainerAware;

/**
 * This driver will take care of the method injection.
 *
 * @package Ding\Bean\Factory\Driver
 */
class MethodInjectionDriver implements IAfterDefinitionListener, IAspectManagerAware, IContainerAware, IBeanDefinitionProvider {
    /**
     * @var AspectManager
     */
    private $aspectManager;
    private $beans=[];
    /**
     * Container.
     *
     * @var IContainer
     */
    private $container;

    /**
     * @param IContainer $container
     */
    public function setContainer(IContainer $container) {
        $this->container=$container;
    }

    /**
     * @param string $name
     *
     * @return BeanDefinition|null
     */
    public function getBeanDefinition(string $name) :?BeanDefinition {
        return ($this->beans[$name] ?? null);
    }

    /**
     * @param string $class
     *
     * @return array
     */
    public function getBeansByClass(string $class) : array {
        return [];
    }

    /**
     * @param string $eventName
     *
     * @return array
     */
    public function getBeansListeningOn(string $eventName) : array {
        return [];
    }

    /**
     * @param BeanDefinition $bean
     *
     * @return BeanDefinition
     */
    public function afterDefinition(BeanDefinition $bean) : BeanDefinition {
        foreach ($bean->getMethodInjections() as $method) {
            $aspectBeanName=BeanDefinition::generateName('MethodInjectionAspect');
            $aspectBean=new BeanDefinition($aspectBeanName);
            $aspectBean->setClass('\\Ding\\Bean\\Factory\\Driver\\MethodInjectionAspect');
            $aspectBean->setProperties([
                new BeanPropertyDefinition('beanName', BeanPropertyDefinition::PROPERTY_SIMPLE, $method[1])
            ]);
            $this->beans[$aspectBeanName]=$aspectBean;
            $aspectName=BeanDefinition::generateName('MethodInjectionAspect');
            $pointcutName=BeanDefinition::generateName('MethodInjectionPointcut');
            $pointcut=new PointcutDefinition($pointcutName, $method[0], 'invoke');
            $this->aspectManager->setPointcut($pointcut);
            $aspect=new AspectDefinition($aspectName, [$pointcutName], AspectDefinition::ASPECT_METHOD, $aspectBeanName, '');
            $aspects=$bean->getAspects();
            $aspects[]=$aspect;
            $bean->setAspects($aspects);
        }
        return $bean;
    }

    /**
     * @param AspectManager $aspectManager
     */
    public function setAspectManager(AspectManager $aspectManager) : void {
        $this->aspectManager=$aspectManager;
    }
}