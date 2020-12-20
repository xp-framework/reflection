<?php namespace lang\reflection;

use lang\Reflection;

/**
 * Type methods enumeration and lookup
 *
 * @test lang.reflection.unittest.MethodsTest
 */
class Methods extends Members {

  /**
   * Returns all methods
   *
   * @return iterable
   */
  public function getIterator() {
    foreach ($this->reflect->getMethods() as $method) {
      if (0 !== strncmp($method->name, '__', 2)) yield $method->name => new Method($method);
    }
  }

  /**
   * Return methods annotated with a given annotation
   *
   * @param  string $annotation
   * @return iterable
   */
  public function annotated($annotation) {
    $t= strtr($annotation, '.', '\\');
    foreach ($this->reflect->getMethods() as $method) {
      if (0 !== strncmp($method->name, '__', 2)) {
        $annotations= Reflection::meta()->methodAnnotations($method);
        if (isset($annotations[$t])) yield $method->name => new Method($method, $annotations);
      }
    }
  }

  /**
   * Returns a method by a given name, or NULL
   *
   * @param  string $name
   * @return ?lang.reflection.Method
   */
  public function named($name) {
    return $this->reflect->hasMethod($name)
      ? new Method($this->reflect->getMethod($name))
      : null
    ;
  }
}