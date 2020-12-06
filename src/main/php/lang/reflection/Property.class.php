<?php namespace lang\reflection;

use lang\Reflection;

class Property extends Member {

  protected function getAnnotations() { return Reflection::parse()->ofProperty($this->reflect); }

  public function get($instance, $context= null) {

    // TODO: Verify context is an instance of class this method is declared in
    if ($context) {
      $this->reflect->setAccessible(true);
    }

    return $this->reflect->getValue($instance);
  }

  public function set($instance, $value, $context= null) {

    // TODO: Verify context is an instance of class this method is declared in
    if ($context) {
      $this->reflect->setAccessible(true);
    }

    $this->reflect->setValue($instance, $value);
    return $value;
  }

  public function toString() {
    return Modifiers::namesOf($this->reflect->getModifiers()).' $'.$this->reflect->name;
  }
}