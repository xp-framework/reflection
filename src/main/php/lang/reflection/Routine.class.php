<?php namespace lang\reflection;

use lang\Reflection;

abstract class Routine extends Member {

  /** @return [:var] */
  protected function getAnnotations() { return Reflection::parse()->ofMethod($this->reflect); }

  /** Returns a compound name consisting of `[CLASS]::[NAME]()`  */
  public function compoundName(): string { return strtr($this->reflect->class, '\\', '.').'::'.$this->reflect->name.'()'; }

  /**
   * Looks up a parameter
   *
   * @param  int|string $arg Either a position or a name
   * @return ?lang.reflection.Parameter
   */
  public function parameter($arg) {
    if (is_int($arg)) {
      $p= $this->reflect->getParameters()[$arg] ?? null;
    } else {
      $p= null;
      foreach ($this->reflect->getParameters() as $parameter) {
        if ($arg !== $parameter->name) continue;
        $p= $parameter;
        break;
      }
    }
    return null === $p ? null : new Parameter($p, $this->reflect);
  }

  /**
   * Returns all parameters
   *
   * @return lang.reflection.Parameters
   */
  public function parameters() {
    return new Parameters($this->reflect->getParameters(), $this->reflect);
  }
}