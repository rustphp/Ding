<?php
/**
 * A definition for an annotation.
 *
 * @category Ding
 * @package  Annotation
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
namespace Ding\Annotation;

use Ding\Annotation\Exception\AnnotationException;

/**
 * A definition for an annotation.
 *
 * @package Ding\Annotation
 */
class Annotation {
    /**
     * Annotation name.
     *
     * @var string
     */
    private $name;
    /**
     * Annotation options.
     *
     * @var array
     */
    private $options;

    /**
     * Constructor.
     *
     * @param string $name Annotation name.
     */
    public function __construct(string $name) {
        $this->name=strtolower($name);
        $this->options=[];
    }

    /**
     * @return array
     */
    public function __sleep() : array {
        return ['name', 'options'];
    }

    /**
     * Returns annotation name.
     *
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws AnnotationException
     */
    public function getOptionValues(string $name) {
        if (!$this->hasOption($name)) {
            throw new AnnotationException("Unknown option: $name");
        }
        return $this->options[$name];
    }

    /**
     * @return array
     */
    public function getOptions() : array {
        return $this->options;
    }

    /**
     * @param string $name
     * @param        $value
     */
    public function addOption(string $name, $value) : void {
        $name=strtolower($name);
        if (!$this->hasOption($name)) {
            $this->options[$name]=[];
        }
        $this->options[$name][]=$value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption(string $name) : bool {
        $name=strtolower($name);
        return isset($this->options[$name]);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOptionSingleValue(string $name) {
        $values=$this->getOptionValues($name);
        return array_shift($values);
    }
}