<?php
/**
 * This driver will search for annotations.
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

use Ding\Cache\ICache;
use Ding\Reflection\IReflectionFactory;
use Ding\Reflection\IReflectionFactoryAware;

/**
 * This driver will search for annotations.
 *
 * @package Ding\Bean\Factory\Driver
 */
class AnnotationDiscovererDriver implements IReflectionFactoryAware {
    /**
     * A ReflectionFactory implementation.
     *
     * @var IReflectionFactory
     */
    private $reflectionFactory;
    /**
     * Annotations cache.
     *
     * @var ICache
     */
    private $cache;
    /**
     * @var array
     */
    private $directories;

    /**
     * AnnotationDiscovererDriver constructor.
     *
     * @param array $directories
     */
    public function __construct(array $directories) {
        $this->directories=$directories;
    }

    public function parse() : void {
        foreach ($this->directories as $dir) {
            $classesPerFile=$this->getClassesFromDirectory($dir);
            foreach ($classesPerFile as $file=>$classes) {
                foreach ($classes as $class) {
                    $this->reflectionFactory->getClassAnnotations($class);
                }
            }
        }
    }

    /**
     * Sets annotations cache.
     *
     * @param ICache $cache
     */
    public function setCache(ICache $cache) : void {
        $this->cache=$cache;
    }

    /**
     * @param IReflectionFactory $reflectionFactory
     *
     * @see \Ding\Reflection\IReflectionFactoryAware::setReflectionFactory()
     */
    public function setReflectionFactory(IReflectionFactory $reflectionFactory) : void {
        $this->reflectionFactory=$reflectionFactory;
    }

    /**
     * Returns all files elegible for scanning for classes.
     *
     * @param string $path Absolute path to a directory or filename.
     *
     * @return string[]
     */
    private function getCandidateFiles(string $path) : array {
        $cacheKey="$path.candidateFiles";
        $result=false;
        $files=$this->cache->fetch($cacheKey, $result);
        if ($result === true) {
            return $files;
        }
        $files=[];
        if (is_dir($path)) {
            foreach (scandir($path) as $entry) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $entry="$path/$entry";
                foreach ($this->getCandidateFiles($entry) as $file) {
                    $files[]=$file;
                }
            }
        } else if ($this->isScannable($path)) {
            $files[]=realpath($path);
        }
        $this->cache->store($cacheKey, $files);
        return $files;
    }

    /**
     * @param string $file
     *
     * @return array
     */
    private function getClassesFromFile(string $file) : array {
        $cacheKey="$file.classesInFile";
        $result=false;
        $classes=$this->cache->fetch($cacheKey, $result);
        if ($result === true) {
            return $classes;
        }
        $classes=array_merge(get_declared_classes(), get_declared_interfaces());
        require_once $file;
        $newClasses=array_diff(array_merge(get_declared_classes(), get_declared_interfaces()), $classes);
        if (empty($newClasses)) {
            foreach ($classes as $class) {
                $rClass=$this->reflectionFactory->getClass($class);
                if ($rClass->getFileName() == $file) {
                    $newClasses[]=$rClass->getName();
                }
            }
        }
        $this->cache->store($cacheKey, $newClasses);
        return $newClasses;
    }

    /**
     * @param string $dir
     *
     * @return array
     */
    private function getClassesFromDirectory(string $dir) : array {
        $cacheKey="$dir.classesInDir";
        $result=false;
        $classes=$this->cache->fetch($cacheKey, $result);
        if ($result === true) {
            return $classes;
        }
        $classes=[];
        foreach ($this->getCandidateFiles($dir) as $file) {
            $classes[$file]=$this->getClassesFromFile($file);
        }
        $this->cache->store($cacheKey, $classes);
        return $classes;
    }

    /**
     * Returns true if the given filesystem entry is interesting to scan.
     *
     * @param string $path Filesystem entry.
     *
     * @return bool
     */
    private function isScannable(string $path) : bool {
        $extensionPos=strrpos($path, '.');
        if ($extensionPos === false) {
            return false;
        }
        if (substr($path, $extensionPos, 4) != '.php') {
            return false;
        }
        return true;
    }
}
