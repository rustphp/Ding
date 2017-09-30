<?php
/**
 * This class is used when reading the bean definition. Aspects will be
 * constructed and applyed using this information, you may thing of this as
 * some kind of Aspect DTO created somewhere else and used by the container to
 * assemble the final bean.
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
 * This class is used when reading the bean definition. Aspects will be
 * constructed and applyed using this information, you may thing of this as
 * some kind of Aspect DTO created somewhere else and used by the container to
 * assemble the final bean.
 *
 * @package Ding\Aspect
 */
class AspectDefinition {
    /**
     * This kind of aspect will be run before the method call.
     *
     * @var integer
     */
    const ASPECT_METHOD=0;
    /**
     * This kind of aspect will be run when the method throws an uncatched
     * exception.
     *
     * @var integer
     */
    const ASPECT_EXCEPTION=1;
    /**
     * Aspect name.
     *
     * @var string
     */
    private $name;
    /**
     * Target aspected methods.
     *
     * @var string[]
     */
    private $pointcuts;
    /**
     * Aspect bean name.
     *
     * @var string
     */
    private $beanName;
    /**
     * Aspect type (or when the advice should be invoked).
     *
     * @var integer
     */
    private $type;
    /**
     * Regular expression for this aspect (global).
     *
     * @var string
     */
    private $expression;

    /**
     * Returns the expression for this aspect.
     *
     * @return string
     */
    public function getExpression() : string {
        return $this->expression;
    }

    /**
     * Returns pointcut names.
     *
     * @return string[]
     */
    public function getPointcuts() : array {
        return $this->pointcuts;
    }

    /**
     * Returns advice type.
     *
     * @return int
     */
    public function getType() : int {
        return $this->type;
    }

    /**
     * Returns bean name.
     *
     * @return string
     */
    public function getBeanName() : string {
        return $this->beanName;
    }

    /**
     * Returns aspect name.
     *
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * Constructor.
     *
     * @param string   $name       Aspect name.
     * @param string[] $pointcuts  Pointcut names.
     * @param integer  $type       Aspect type (see this class constants).
     * @param string   $beanName   Aspect bean name.
     * @param string   $expression Regular expression for this aspect.
     */
    public function __construct(string $name, array $pointcuts, int $type, string $beanName,
        string $expression) {
        $this->name=$name;
        $this->pointcuts=$pointcuts;
        $this->beanName=$beanName;
        $this->type=$type;
        $this->expression=$expression;
    }
}