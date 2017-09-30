<?php
/**
 * Definition for a bean lifecycle.
 *
 * @category   Ding
 * @package    Bean
 * @subpackage Lifecycle
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
namespace Ding\Bean\Lifecycle;
/**
 * Definition for a bean lifecycle.
 *
 * @package Ding\Bean\Lifecycle
 */
class BeanLifecycle {
    /**
     * The bean has just been defined and is about to be created. The definition
     * will not change after this step.
     *
     * @var string
     */
    const AfterDefinition='afterDefinition';
    /**
     * Before calling factory::createBean()
     *
     * @var string
     */
    const BeforeCreate='beforeCreate';
    /**
     * After calling factory::createBean()
     *
     * @var string
     */
    const AfterCreate='afterCreate';
    /**
     * Before calling factory::assemble()
     *
     * @var string
     */
    const BeforeAssemble='beforeAssemble';
    /**
     * After calling factory::assemble()
     *
     * @var string
     */
    const AfterAssemble='afterAssemble';
    /**
     * Right after configuring everything, ready to be used by the user.
     *
     * @var string
     */
    const AfterConfig='afterConfig';
}
