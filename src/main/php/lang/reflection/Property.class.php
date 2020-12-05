<?php namespace lang\reflection;

use lang\Reflection;

class Property extends Member {

  public function annotations() {
    return new Annotations(Reflection::parse()->ofProperty($this->reflect));
  }

  public function toString() {
    return Modifiers::namesOf($this->reflect->getModifiers()).' $'.$this->reflect->name;
  }
}