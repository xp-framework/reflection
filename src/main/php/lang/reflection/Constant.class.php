<?php namespace lang\reflection;

use lang\Reflection;

class Constant extends Member {

  protected function getAnnotations() { return Reflection::meta()->ofConstant($this->reflect); }

  /** Returns a compound name consisting of `[CLASS]::$[NAME]`  */
  public function compoundName(): string { return strtr($this->reflect->class, '\\', '.').'::'.$this->reflect->name; }

  /** @return int */
  public function modifiers() { return $this->reflect->getModifiers(); }

  /** @return var */
  public function value() { return $this->reflect->getValue(); }
}