<?php namespace lang\reflection;

class Parameters implements \IteratorAggregate {
  private $list, $method;

  /**
   * Creates a new parameters list
   *
   * @param  ReflectionParameter[] $list
   * @param  ReflectionMethod $method
   */
  public function __construct($list, $method= null) {
    $this->list= $list;
    $this->method= $method;
  }

  /** @return ?lang.reflection.Parameter */
  public function first() { return $this->list ? new Parameter($this->list[0], $this->method) : null; }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->list as $parameter) {
      yield $parameter->name => new Parameter($parameter, $this->method);
    }
  }
}