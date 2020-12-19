<?php namespace lang\reflection;

class Parameters implements \IteratorAggregate {
  private $method;

  /**
   * Creates a new parameters list
   *
   * @param  ReflectionMethod $method
   */
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