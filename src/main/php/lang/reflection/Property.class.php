<?php namespace lang\reflection;

use lang\{Reflection, XPClass, Type, TypeUnion};

/**
 * Reflection for a single property
 *
 * @test lang.reflection.unittest.PropertiesTest
 */
class Property extends Member {

  protected function meta() { return Reflection::meta()->propertyAnnotations($this->reflect); }

  /**
   * Returns this property's doc comment, or NULL if there is none.
   *
   * @return ?string
   */
  public function comment() { return Reflection::meta()->propertyComment($this->reflect); }

  /** Returns a compound name consisting of `[CLASS]::$[NAME]`  */
  public function compoundName(): string { return strtr($this->reflect->class, '\\', '.').'::$'.$this->reflect->name; }

  /** @return lang.reflection.Constraint */
  public function constraint() {
    $present= true;

    // Only use meta information if necessary
    $api= function($set) use(&$present) {
      $present= $set;
      return Reflection::meta()->propertyType($this->reflect);
    };

    $t= Type::resolve(PHP_VERSION_ID >= 70400 ? $this->reflect->getType() : null, Member::resolve($this->reflect), $api);
    return new Constraint($t ?? Type::$VAR, $present);
  }

  /**
   * Gets this property's value
   *
   * @param  ?object $instance
   * @param  ?string|?lang.XPClass|?lang.reflection.Type $context
   * @return var
   * @throws lang.reflection.CannotAccess
   */
  public function get($instance, $context= null) {

    // Success oriented: Let PHP's reflection API raise the exceptions for us
    if ($context && !$this->reflect->isPublic()) {
      if (Reflection::of($context)->is($this->reflect->class)) {
        $this->reflect->setAccessible(true);
      }
    }

    try {
      return $this->reflect->getValue($instance);
    } catch (\ReflectionException $e) {
      throw new CannotAccess($this, $e);
    }
  }

  /**
   * Sets this property's value
   *
   * @param  ?object $instance
   * @param  var $value
   * @param  ?string|?lang.XPClass|?lang.reflection.Type $context
   * @return var The given value
   * @throws lang.reflection.CannotAccess
   * @throws lang.reflection.AccessFailed if setting raises an exception
   */
  public function set($instance, $value, $context= null) {

    // Success oriented: Let PHP's reflection API raise the exceptions for us
    if ($context && !$this->reflect->isPublic()) {
      if (Reflection::of($context)->is($this->reflect->class)) {
        $this->reflect->setAccessible(true);
      }
    }

    try {
      $this->reflect->setValue($instance, $value);
      return $value;
    } catch (\ReflectionException $e) {
      throw new CannotAccess($this, $e);
    } catch (\Throwable $e) {
      throw new AccessingFailed($this, $e);
    }
  }

  /** @return string */
  public function toString() {

    // Compile property type
    $t= PHP_VERSION_ID >= 70400 ? $this->reflect->getType() : null;
    if (null === $t) {
      $name= Reflection::meta()->propertyType($this->reflect) ?? 'var';
    } else if ($t instanceof \ReflectionUnionType) {
      $name= '';
      foreach ($t->getTypes() as $component) {
        $name.= '|'.$component->getName();
      }
      $name= substr($name, 1);
    } else {
      $name= $t->getName();
    }

    return Modifiers::namesOf($this->reflect->getModifiers()).' '.$name.' $'.$this->reflect->name;
  }
}