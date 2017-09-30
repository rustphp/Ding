<?php
/**
 * This is a bean definition provider used by the container when it's
 * bootstrapping. Will provide all the basic needed beans.
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

use Ding\Bean\BeanConstructorArgumentDefinition;
use Ding\Bean\BeanDefinition;
use Ding\Bean\BeanPropertyDefinition;
use Ding\Bean\IBeanDefinitionProvider;

/**
 * This is a bean definition provider used by the container when it's
 * bootstrapping. Will provide all the basic needed beans.
 *
 * @package Ding\Bean\Provider
 */
class Core implements IBeanDefinitionProvider {
    /**
     * Container options.
     *
     * @var string[]
     */
    protected $options;

    /**
     * Core constructor.
     *
     * @param array $options
     */
    public function __construct(array $options=[]) {
        $this->options=$options;
    }

    /**
     * @param string $name
     *
     * @return BeanDefinition|null
     * @see \Ding\Bean\IBeanDefinitionProvider::get()
     */
    public function getBeanDefinition(string $name) :?BeanDefinition {
        $bean=null;
        switch ($name) {
        case 'dingAnnotationParser':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Annotation\Parser');
            break;
        case 'dingAnnotationsCache':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Cache\Locator\CacheLocator');
            $bean->setFactoryMethod('getAnnotationsCacheInstance');
            break;
        case 'dingBeanCache':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Cache\Locator\CacheLocator');
            $bean->setFactoryMethod('getBeansCacheInstance');
            break;
        case 'dingDefinitionsCache':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Cache\Locator\CacheLocator');
            $bean->setFactoryMethod('getDefinitionsCacheInstance');
            break;
        case 'dingProxyCache':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Cache\Locator\CacheLocator');
            $bean->setFactoryMethod('getProxyCacheInstance');
            break;
        case 'dingAspectCache':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Cache\Locator\CacheLocator');
            $bean->setFactoryMethod('getAspectCacheInstance');
            break;
        case 'dingAspectManager':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Aspect\AspectManager');
            $bean->setProperties([
                new BeanPropertyDefinition('cache', BeanPropertyDefinition::PROPERTY_BEAN, 'dingAspectCache')
            ]);
            break;
        case 'dingXmlBeanDefinitionProvider':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Provider\Xml');
            $bean->setArguments([
                new BeanConstructorArgumentDefinition(BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_VALUE, $this->options['bdef']['xml'])
            ]);
            break;
        case 'dingAnnotationBeanDefinitionProvider':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Provider\Annotation');
            $bean->setArguments([
                new BeanConstructorArgumentDefinition(BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_VALUE, $this->options['bdef']['annotation'])
            ]);
            $bean->setInitMethod('init');
            $bean->setProperties([
                new BeanPropertyDefinition('cache', BeanPropertyDefinition::PROPERTY_BEAN, 'dingAnnotationsCache')
            ]);
            break;
        case 'dingYamlBeanDefinitionProvider':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Provider\Yaml');
            $bean->setArguments([
                new BeanConstructorArgumentDefinition(BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_VALUE, $this->options['bdef']['yaml'])
            ]);
            break;
        case 'dingAspectCallDispatcher':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Aspect\Interceptor\DispatcherImpl');
            break;
        case 'dingAnnotationInitDestroyMethodDriver':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Factory\Driver\AnnotationInitDestroyMethodDriver');
            break;
        case 'dingAnnotationValueDriver':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Factory\Driver\AnnotationValueDriver');
            break;
        case 'dingMvcAnnotationDriver':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Factory\Driver\MvcAnnotationDriver');
            break;
        case 'dingAnnotationRequiredDriver':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Factory\Driver\AnnotationRequiredDriver');
            break;
        case 'dingAnnotationDiscovererDriver':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Factory\Driver\AnnotationDiscovererDriver');
            $bean->setProperties([
                new BeanPropertyDefinition('cache', BeanPropertyDefinition::PROPERTY_BEAN, 'dingAnnotationsCache')
            ]);
            $bean->setInitMethod('parse');
            $bean->setArguments([
                new BeanConstructorArgumentDefinition(BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_VALUE, $this->options['bdef']['annotation']['scanDir'])
            ]);
            break;
        case 'dingAnnotationResourceDriver':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Factory\Driver\AnnotationResourceDriver');
            break;
        case 'dingAnnotationInjectDriver':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Factory\Driver\AnnotationInjectDriver');
            break;
        case 'dingPropertiesDriver':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Factory\Driver\PropertiesDriver');
            break;
        case 'dingLifecycleManager':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Lifecycle\BeanLifecycleManager');
            break;
        case 'dingMethodInjectionDriver':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Factory\Driver\MethodInjectionDriver');
            break;
        case 'dingMessageSourceDriver':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Bean\Factory\Driver\MessageSourceDriver');
            break;
        case 'dingReflectionFactory':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Reflection\ReflectionFactory');
            $bean->setArguments([
                new BeanConstructorArgumentDefinition(BeanConstructorArgumentDefinition::BEAN_CONSTRUCTOR_VALUE, isset($this->options['bdef']['annotation']))
            ]);
            $bean->setProperties([
                new BeanPropertyDefinition('cache', BeanPropertyDefinition::PROPERTY_BEAN, 'dingAnnotationsCache'),
                new BeanPropertyDefinition('annotationParser', BeanPropertyDefinition::PROPERTY_BEAN, 'dingAnnotationParser')
            ]);
            break;
        case 'dingProxyFactory':
            $bean=new BeanDefinition($name);
            $bean->setClass('\Ding\Aspect\Proxy');
            $bean->setProperties([
                new BeanPropertyDefinition('cache', BeanPropertyDefinition::PROPERTY_BEAN, 'dingProxyCache')
            ]);
            break;
        default:
            break;
        }
        return $bean;
    }

    /**
     * @param string $eventName
     *
     * @return array
     * @see \Ding\Bean\IBeanDefinitionProvider::getBeansListeningOn()
     */
    public function getBeansListeningOn(string $eventName) : array {
        return [];
    }

    /**
     * @param string $class
     *
     * @return array
     * @see \Ding\Bean\IBeanDefinitionProvider::getByClass()
     */
    public function getBeansByClass(string $class) : array {
        return [];
    }
}