<?php
/**
 * This driver will apply all filters to property values.
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
use Ding\Bean\IBeanDefinitionProvider;
use Ding\Bean\Lifecycle\IAfterConfigListener;
use Ding\Container\IContainer;
use Ding\Container\IContainerAware;
use Ding\Resource\IResource;
use Ding\Resource\IResourceLoader;
use Ding\Resource\IResourceLoaderAware;

/**
 * This driver will apply all filters to property values.
 *
 *
 * @package Ding\Bean\Factory\Driver
 */
class PropertiesDriver implements IContainerAware, IBeanDefinitionProvider, IResourceLoaderAware, IAfterConfigListener {
    /**
     * Container.
     *
     * @var IContainer
     */
    private $container;
    /**
     * Injected resource loader.
     *
     * @var IResourceLoader
     */
    private $resourceLoader;

    /**
     * @param IContainer $container
     */
    public function setContainer(IContainer $container) : void {
        $this->container=$container;
    }

    /**
     * @param IResourceLoader $resourceLoader
     */
    public function setResourceLoader(IResourceLoader $resourceLoader) : void {
        $this->resourceLoader=$resourceLoader;
    }

    public function afterConfig() : void {
        $holder=$this->container->getBean('PropertiesHolder');
        foreach ($holder->getLocations() as $location) {
            if (is_string($location)) {
                $resource=$this->resourceLoader->getResource(trim($location));
            } else {
                $resource=$location;
            }
            if ($resource instanceof IResource) {
                $contents=stream_get_contents($resource->getStream());
                $this->container->registerProperties(parse_ini_string($contents, false));
            }
        }
    }

    /**
     * @param string $name
     *
     * @return BeanDefinition
     */
    public function getBeanDefinition(string $name) : BeanDefinition {
        if ($name == 'PropertiesHolder') {
            $bDef=new BeanDefinition('PropertiesHolder');
            $bDef->setClass('Ding\\Helpers\\Properties\\PropertiesHelper');
            return $bDef;
        }
        return null;
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
     * @param string $class
     *
     * @return array
     */
    public function getBeansByClass(string $class) : array {
        return [];
    }
}