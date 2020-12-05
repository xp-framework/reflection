<?php namespace lang\reflection;

use lang\Reflection;

class Constant extends Member {

  public function annotations() {
    return new Annotations(Reflection::parse()->ofConstant($this->reflect));
  }

  /** @return int */
  public function modifiers() { return $this->reflect->getModifiers(); }

  /** @return var */
  public function value() { return $this->reflect->getValue(); }
}