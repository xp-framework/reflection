<?php namespace lang\reflection;

use lang\Reflection;

class Property extends Member {

  protected function getAnnotations() { return Reflection::parse()->ofProperty($this->reflect); }

  public function get($instance, $context= null) {

    // Success oriented: Let PHP's reflection API raise the exceptions for us
    if ($context && !$this->reflect->isPublic()) {
      $t= $context instanceof Type ? $context : Reflection::of($context);
      if ($t->is($this->reflect->class)) {
        $this->reflect->setAccessible(true);
      }
    }

    try {
      return $this->reflect->getValue($instance);
    } catch (\ReflectionException $e) {
      throw new CannotAccess(strtr($this->reflect->class, '\\', '.').'::$'.$this->reflect->name, $e);
    }
  }

  public function set($instance, $value, $context= null) {

    // Success oriented: Let PHP's reflection API raise the exceptions for us
    if ($context && !$this->reflect->isPublic()) {
      $t= $context instanceof Type ? $context : Reflection::of($context);
      if ($t->is($this->reflect->class)) {
        $this->reflect->setAccessible(true);
      }
    }

    try {
      $this->reflect->setValue($instance, $value);
      return $value;
    } catch (\ReflectionException $e) {
      throw new CannotAccess(strtr($this->reflect->class, '\\', '.').'::$'.$this->reflect->name, $e);
    } catch (\Throwable $e) {
      throw new AccessingFailed(strtr($this->reflect->class, '\\', '.').'::$'.$this->reflect->name, $e);
    }
  }

  public function toString() {
    return Modifiers::namesOf($this->reflect->getModifiers()).' $'.$this->reflect->name;
  }
}