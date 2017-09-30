<?php
/**
 * Bean Definition.
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
 * Bean Definition.
 *
 * @package Ding\Bean
 */
class BeanDefinition {
    /**
     * Specifies scope prototype for beans, meaning that a new instance will
     * be returned every time.
     *
     * @var integer
     */
    const BEAN_PROTOTYPE=0;
    /**
     * Specifies scope singleton for beans, meaning that the same instance will
     * be returned every time.
     *
     * @var integer
     */
    const BEAN_SINGLETON=1;
    /**
     * Bean name
     *
     * @var string
     */
    private $name;
    /**
     * Bean class name.
     *
     * @var string
     */
    private $class;
    /**
     * Bean type (scope). See this class constants.
     *
     * @var integer
     */
    private $scope;
    /**
     * Properties to be di'ed to this bean.
     *
     * @var BeanPropertyDefinition[]
     */
    private $properties;
    /**
     * Aspects mapped to this bean.
     *
     * @var null|string[]
     */
    private $aspects;
    /**
     * Constructor arguments.
     *
     * @var BeanConstructorArgumentDefinition[]
     */
    private $constructorArgs;
    /**
     * Factory method name (if any).
     *
     * @var string
     */
    private $factoryMethod;
    /**
     * Factory bean name (if any).
     *
     * @var string
     */
    private $factoryBean;
    /**
     * Init method (if any).
     *
     * @var string
     */
    private $initMethod;
    /**
     * Destroy method (called when container is destroyed).
     *
     * @var string
     */
    private $destroyMethod;
    /**
     * Dependency beans literally specified in the configuration.
     *
     * @var string[]
     */
    private $dependsOn;
    /**
     * Methods injection.
     *
     * @var string[]
     */
    private $lookupMethods;
    /**
     * True if this bean cant be instantiated.
     *
     * @var boolean
     */
    private $isAbstract;
    /**
     * Parent bean, if any.
     *
     * @var string
     */
    private $parent;
    /**
     * Bean aliases.
     *
     * @var string[]
     */
    private $aliases;
    /**
     * Holds the name of the proxy class that was generated for this bean, only
     * valid for those who actually have aspects applied.
     *
     * @var string
     */
    private $proxyClassName;
    /**
     * When wiring by type, this will mark this bean definition as the primary
     * source when multiple candidates are found.
     *
     * @var boolean;
     */
    private $isPrimaryCandidate;

    /**
     * Mark this bean definition as primary candidate when multiple candidates
     * are found for wiring by type.
     */
    public function markAsPrimaryCandidate() : void {
        $this->isPrimaryCandidate=true;
    }

    /**
     * True if this bean definition is the primary candidate when wiring by type.
     *
     * @return boolean
     */
    public function isPrimaryCandidate() : bool {
        return $this->isPrimaryCandidate;
    }

    /**
     * Aliases for this bean.
     *
     * @return string[]
     */
    public function getAliases() : array {
        return $this->aliases;
    }

    /**
     * This is needed when creating a child bean.
     *
     * @return void
     */
    public function clearAliases() : void {
        $this->aliases=[];
    }

    /**
     * Add an alias to this bean.
     *
     * @param string $name New alias for this bean.
     *
     * @return void
     */
    public function addAlias(string $name) : void {
        $this->aliases[$name]=$name;
    }

    /**
     * Returns true if this bean definition is abstract and cant be instantiated.
     *
     * @return boolean
     */
    public function isAbstract() : bool {
        return $this->isAbstract;
    }

    /**
     * Makes this bean an abstract bean definition.
     *
     * @return void
     */
    public function makeAbstract() : void {
        $this->isAbstract=true;
    }

    /**
     * Makes this bean concrete, that is, not abstract.
     *
     * @return void
     */
    public function makeConcrete() : void {
        $this->isAbstract=false;
    }

    /**
     * Returns true if this bean definition is for a bean of type singleton.
     *
     * @return boolean
     */
    public function isSingleton() : bool {
        return $this->scope == BeanDefinition::BEAN_SINGLETON;
    }

    /**
     * Returns true if this bean definition is for a bean of type prototype.
     *
     * @return boolean
     */
    public function isPrototype() : bool {
        return $this->scope == BeanDefinition::BEAN_PROTOTYPE;
    }

    /**
     * Returns true if this bean has mapped aspects.
     *
     * @return boolean
     */
    public function hasAspects() : bool {
        return $this->aspects !== null;
    }

    /**
     * Sets new aspects for this bean.
     *
     * @param string[] $aspects New aspects.
     *
     * @return void
     */
    public function setAspects(array $aspects) : void {
        $this->aspects=$aspects;
    }

    /**
     * Returns aspects for this bean.
     *
     * @return string[]
     */
    public function getAspects() : array {
        return $this->aspects;
    }

    /**
     * Changes the scope for this bean.
     *
     * @param string $scope New scope.
     *
     * @return void
     */
    public function setScope(string $scope) : void {
        $this->scope=$scope;
    }

    /**
     * Sets new method injections.
     *
     * @param string[] $methods Methods injected.
     *
     * @return void
     */
    public function setMethodInjections(array $methods) : void {
        $this->lookupMethods=$methods;
    }

    /**
     * Returns the method injections.
     *
     * @return string[]
     */
    public function getMethodInjections() : array {
        return $this->lookupMethods;
    }

    /**
     * Sets a new name for this bean.
     *
     * @param string $name New name.
     *
     * @return void
     */
    public function setName(string $name) : void {
        $this->name=$name;
    }

    /**
     * Returns bean name.
     *
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * Sets a new class name for this bean.
     *
     * @param string $class New class name.
     *
     * @return void
     */
    public function setClass(string $class) : void {
        $this->class=$class;
    }

    /**
     * Returns bean class.
     *
     * @return string
     */
    public function getClass() : string {
        return $this->class;
    }

    /**
     * Sets new properties for this bean.
     *
     * @param BeanPropertyDefinition[] $properties New properties.
     *
     * @return void
     */
    public function setProperties(array $properties) : void {
        $this->properties=$properties;
    }

    /**
     * Returns properties for this bean.
     *
     * @return BeanPropertyDefinition[]
     */
    public function getProperties() : array {
        return $this->properties;
    }

    /**
     * Sets new arguments for this bean.
     *
     * @param BeanConstructorArgumentDefinition[] $arguments New arguments.
     *
     * @return void
     */
    public function setArguments(array $arguments) : void {
        $this->constructorArgs=$arguments;
    }

    /**
     * Returns arguments for this bean.
     *
     * @return BeanConstructorArgumentDefinition[]
     */
    public function getArguments() : array {
        return $this->constructorArgs;
    }

    /**
     * Sets a new factory method for this bean.
     *
     * @param string $factoryMethod New factory method.
     *
     * @return void
     */
    public function setFactoryMethod(?string $factoryMethod) : void {
        $this->factoryMethod=$factoryMethod;
    }

    /**
     * Factory method, null if none was set.
     *
     * @return string
     */
    public function getFactoryMethod() : ?string {
        return $this->factoryMethod;
    }

    /**
     * Sets a new factory bean for this bean.
     *
     * @param string $factoryBean New factory bean.
     *
     * @return void
     */
    public function setFactoryBean(?string $factoryBean) : void {
        $this->factoryBean=$factoryBean;
    }

    /**
     * Factory bean, null if none was set.
     *
     * @return null|string
     */
    public function getFactoryBean() :?string {
        return $this->factoryBean;
    }

    /**
     * Sets a new init method for this bean.
     *
     * @param string $initMethod New init method.
     *
     * @return void
     */
    public function setInitMethod(?string $initMethod) : void {
        $this->initMethod=$initMethod;
    }

    /**
     * Init method, null if none was set.
     *
     * @return string
     */
    public function getInitMethod() :?string {
        return $this->initMethod;
    }

    /**
     * Sets a new destroy method for this bean.
     *
     * @param string $destroyMethod New destroy method.
     *
     * @return void
     */
    public function setDestroyMethod(?string $destroyMethod) {
        $this->destroyMethod=$destroyMethod;
    }

    /**
     * Destroy method, null if none was set.
     *
     * @return null|string
     */
    public function getDestroyMethod() :?string {
        return $this->destroyMethod;
    }

    /**
     * Returns all beans marked as dependencies for this bean.
     *
     * @return string[]
     */
    public function getDependsOn() : array {
        return $this->dependsOn;
    }

    /**
     * Set bean dependencies.
     *
     * @param string[] $dependsOn Dependencies (bean names).
     *
     * @return void
     */
    public function setDependsOn(array $dependsOn) : void {
        $this->dependsOn=$dependsOn;
    }

    /**
     * Returns a new bean definition, a copy of this one.
     *
     * @param string $childName The name for the new bean
     *
     * @return BeanDefinition
     */
    public function makeChildBean(string $childName) : BeanDefinition {
        //$bean=serialize($this);
        //$bean=unserialize($bean);
        $bean=clone $this;
        $bean->setName($childName);
        $bean->clearAliases();
        $bean->makeConcrete();
        return $bean;
    }

    /**
     * Creates a new unique bean name.
     *
     * @param string $prefix A name prefix
     *
     * @return string
     */
    public static function generateName(string $prefix) : string {
        return $prefix . mt_rand(1, microtime(true));
    }

    /**
     * Returns true if this bean is created by calling the constructor.
     *
     * @return boolean
     */
    public function isCreatedByConstructor() : bool {
        return empty($this->factoryMethod);
    }

    /**
     * Returns true if this bean is created by calling a factory bean (as
     * opposed to a factory class)
     *
     * @return boolean
     */
    public function isCreatedWithFactoryBean() : bool {
        return !empty($this->factoryBean);
    }

    /**
     * @param string $name
     */
    public function setProxyClassName(string $name) : void {
        $this->proxyClassName=$name;
    }

    /**
     * @return string
     */
    public function getProxyClassName() :?string {
        return $this->proxyClassName;
    }

    /**
     * @return bool
     */
    public function hasProxyClass() : bool {
        return (null !== $this->proxyClassName);
    }

    /**
     * @return bool
     */
    public function hasInitMethod() : bool {
        return (null !== $this->initMethod);
    }

    /**
     * @return bool
     */
    public function hasDestroyMethod() : bool {
        return (null !== $this->destroyMethod);
    }

    /**
     * Constructor.
     *
     * @param string $name Bean name.
     */
    public function __construct(string $name) {
        $this->name=$name;
        $soullessString='';
        $soullessArray=[];
        $this->class='stdclass';
        $this->aliases=$soullessArray;
        $this->scope=BeanDefinition::BEAN_SINGLETON;
        $this->factoryMethod=$soullessString;
        $this->factoryBean=$soullessString;
        $this->initMethod=null;
        $this->lookupMethods=$soullessArray;
        $this->destroyMethod=null;
        $this->dependsOn=$soullessArray;
        $this->properties=$soullessArray;
        $this->aspects=null;
        $this->constructorArgs=$soullessArray;
        $this->isAbstract=false;
        $this->parent=null;
        $this->proxyClassName=null;
        $this->isPrimaryCandidate=false;
    }
}
