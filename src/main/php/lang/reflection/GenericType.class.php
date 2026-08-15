<?php namespace lang\reflection;

use ReflectionClass;
use lang\Reflection;

class GenericType extends Type {
  private $generics= null;

  /** Returns whether this type is generic */
  public function generic() { return true; }

  /** Returns definition for this generic type */
  public function definition(): parent {
    $this->generics??= Reflection::meta()->typeGenerics($this->reflect);
    return new parent(new ReflectionClass(strtr($this->generics[0], '.', '\\')));
  }

  /** @return lang.Type[] */
  public function arguments(): array {
    $this->generics??= Reflection::meta()->typeGenerics($this->reflect);
    return $this->generics[1];
  }
}