<?php namespace lang\reflection;

use lang\Reflection;

class Property extends Member {

  protected function getAnnotations() { return Reflection::parse()->ofProperty($this->reflect); }

  public function toString() {
    return Modifiers::namesOf($this->reflect->getModifiers()).' $'.$this->reflect->name;
  }
}