<?php namespace lang\reflection;

use lang\Reflection;

class Property extends Member {

  protected function meta() { return Reflection::meta()->ofProperty($this->reflect); }

  /** Returns a compound name consisting of `[CLASS]::$[NAME]`  */
  public function compoundName(): string { return strtr($this->reflect->class, '\\', '.').'::$'.$this->reflect->name; }

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
    return Modifiers::namesOf($this->reflect->getModifiers()).' $'.$this->reflect->name;
  }
}