<?php namespace lang\reflection;

use lang\{Type, XPClass, Reflection};

abstract class Member {
  protected $reflect;

  public function __construct($reflect) {
    $this->reflect= $reflect;
  }

  protected function resolve($name) {
    if ('self' === $name) {
      return new XPClass($this->reflect->getDeclaringClass());
    } else if ('static' === $name) {
      return new XPClass($this->reflect->class);
    } else {
      return Type::forName($name);
    }
  }

  public function name() { return $this->reflect->name; }

  public function modifiers() { return new Modifiers($this->reflect->getModifiers()); }

  public function declaredIn() { return Reflection::of($this->reflect->getDeclaringClass()); }

  public function evaluate($expression) {
    return Reflection::parse()->evaluate($this->reflect->getDeclaringClass(), $expression);
  }
}