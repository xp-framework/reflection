<?php namespace lang\reflection;

/**
 * Method or constructor parameters enumeration and lookup
 *
 * @test lang.reflection.unittest.MethodsTest
 */
class Parameters implements \IteratorAggregate {
  private $method;

  /** @param ReflectionMethod $method */
  public function __construct($method) {
    $this->method= $method;
  }

  /**
   * Returns number of parameters
   *
   * @param  bool $required Whether to count only required parameters
   * @return int
   */
  public function size($required= false) {
    return $required ? $this->method->getNumberOfRequiredParameters() : $this->method->getNumberOfParameters();
  }

  /**
   * Gets a parameter at a given position
   *
   * @param  int $position
   * @return ?lang.reflection.Parameter
   */
  public function at(int $position) {
    $list= $this->method->getParameters();
    return isset($list[$position]) ? new Parameter($list[$position], $this->method) : null;
  }

  /**
   * Gets a parameter by a given name
   *
   * @param  string $name
   * @return ?lang.reflection.Parameter
   */
  public function named(string $name) {
    foreach ($this->method->getParameters() as $param) {
      if ($name === $param->name) return new Parameter($param, $this->method);
    }
    return null;
  }

  /** @return ?lang.reflection.Parameter */
  public function first() {
    $list= $this->method->getParameters();
    return $list ? new Parameter($list[0], $this->method) : null;
  }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->method->getParameters() as $parameter) {
      yield $parameter->name => new Parameter($parameter, $this->method);
    }
  }
}