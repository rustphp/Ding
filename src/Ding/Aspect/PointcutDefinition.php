<?php
/**
 * A pointcut definition.
 *
 * @category Ding
 * @package  Aspect
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
namespace Ding\Aspect;
/**
 * A pointcut definition.
 *
 * @package Ding\Aspect
 */
class PointcutDefinition {
    /**
     * Pointcut name/id.
     *
     * @var string
     */
    private $name;
    /**
     * Pointcut regular expression.
     *
     * @var string
     */
    private $expression;
    /**
     * Target method to execute.
     *
     * @var string $method
     */
    private $method;

    /**
     * Returns pointcut name.
     *
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * Sets the pointcut name.
     *
     * @param string $name Pointcut name.
     *
     * @return void
     */
    public function setName(string $name) {
        $this->name=$name;
    }

    /**
     * Returns the target method.
     *
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Sets the target method.
     *
     * @param string $method Sets the target method to execute.
     */
    public function setMethod(string $method) : void {
        $this->method=$method;
    }

    /**
     * Returns pointcut expression.
     *
     * @return string
     */
    public function getExpression() : string {
        return $this->expression;
    }

    /**
     * Sets the pointcut expression.
     *
     * @param string $expression Pointcut expression.
     *
     * @return void
     */
    public function setExpression(string $expression) : void {
        $this->expression=$expression;
    }

    /**
     * Constructor.
     *
     * @param string $name       Pointcut name.
     * @param string $expression Pointcut expression.
     * @param string $method     Target method to execute.
     *
     */
    public function __construct(string $name, string $expression, string $method) {
        $this->name=$name;
        $this->expression=$expression;
        $this->method=$method;
    }
}