<?php
/**
 * Bean property definition.
 *
 * @category Ding
 * @package  Bean
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
namespace Ding\Bean;
/**
 * Bean property definition.
 *
 * @package Ding\Bean
 */
class BeanPropertyDefinition {
    /**
     * This constant represents a property that is an integer, string, or any
     * other native type.
     *
     * @var integer
     */
    const PROPERTY_SIMPLE=0;
    /**
     * This constant represents a property that is another bean.
     *
     * @var integer
     */
    const PROPERTY_BEAN=1;
    /**
     * This constant represents a property that is an array.
     *
     * @var integer
     */
    const PROPERTY_ARRAY=2;
    /**
     * This constant represents a property that is php code to be evaluated.
     *
     * @var integer
     */
    const PROPERTY_CODE=3;
    /**
     * Property name
     *
     * @var string
     */
    private $name;
    /**
     * Property value (in the case of a bean property, this is the bean name).
     *
     * @var string|array
     */
    private $value;
    /**
     * Property type (see this class constants)
     *
     * @var string
     */
    private $type;

    /**
     * Returns true if this property is a reference to another bean.
     *
     * @return boolean
     */
    public function isBean() : bool {
        return $this->type === static::PROPERTY_BEAN;
    }

    /**
     * Returns true if this property is php code.
     *
     * @return boolean
     */
    public function isCode() : bool {
        return $this->type === static::PROPERTY_CODE;
    }

    /**
     * Returns true if this property is an array.
     *
     * @return boolean
     */
    public function isArray() : bool {
        return $this->type === static::PROPERTY_ARRAY;
    }

    /**
     * Returns property value (or bean name in the case of a bean property).
     *
     * @return string|array
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Returns property name
     *
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * Constructor.
     *
     * @param string       $name  Target property name.
     * @param int          $type  Target property type (See this class constants).
     * @param string|array $value Target property value.
     */
    public function __construct(string $name, int $type, $value) {
        $this->name=$name;
        $this->type=$type;
        $this->value=$value;
    }
}
