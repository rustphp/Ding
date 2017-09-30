<?php
/**
 * A collection of annotations.
 *
 * @category   Ding
 * @package    Annotation
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
namespace Ding\Annotation;

use Ding\Annotation\Exception\AnnotationException;

/**
 * A collection of annotations.
 *
 * @package Ding\Annotation
 */
class Collection {
    /**
     * @var array $annotations
     */
    private $annotations=[];

    /**
     * @return array
     */
    public function __sleep() {
        return ['annotations'];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function contains(string $name):bool {
        $name=strtolower($name);
        return isset($this->annotations[$name]);
    }

    /**
     * @return array
     */
    public function getAll():array {
        return $this->annotations;
    }

    /**
     * @param string $name
     *
     * @return null|Annotation
     */
    public function getSingleAnnotation(string $name) :?Annotation {
        $annotations=$this->getAnnotations($name);
        $annotation=array_shift($annotations);
        if ($annotation instanceof Annotation) {
            return $annotation;
        }
        return null;
    }

    /**
     * @param string $name
     *
     * @return Annotation[]
     * @throws AnnotationException
     */
    public function getAnnotations(string $name) : array {
        $name=strtolower($name);
        if ($this->contains($name)) {
            return $this->annotations[$name];
        }
        throw new AnnotationException("Unknown annotation: $name");
    }

    /**
     * @param Annotation $annotation
     */
    public function add(Annotation $annotation) : void {
        $name=strtolower($annotation->getName());
        if (!$this->contains($name)) {
            $this->annotations[$name]=[];
        }
        $this->annotations[$name][]=$annotation;
    }
}
