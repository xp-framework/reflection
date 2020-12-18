<?php namespace lang\reflection;

use lang\Reflection;
use util\Objects;

class Constant extends Member {

  protected function meta() { return Reflection::meta()->ofConstant($this->reflect); }

  /** Returns a compound name consisting of `[CLASS]::$[NAME]`  */
  public function compoundName(): string { return strtr($this->reflect->class, '\\', '.').'::'.$this->reflect->name; }

  /** @return int */
  public function modifiers() { return $this->reflect->getModifiers(); }

  /** @return var */
  public function value() { return $this->reflect->getValue(); }

  /** @return string */
  public function toString() {
    return sprintf('%s const %s = %s',
      Modifiers::namesOf($this->reflect->getModifiers()),
      $this->reflect->name,
      Objects::stringOf($this->reflect->getValue())
    );
  }
}