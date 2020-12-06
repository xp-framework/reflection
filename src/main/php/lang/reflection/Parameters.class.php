<?php namespace lang\reflection;

class Parameters implements \IteratorAggregate {
  private $list;

  /** @param var[] $list */
  public function __construct($list) {
    $this->list= $list;
  }

  /** @return ?lang.reflection.Parameter */
  public function first() { return $this->list ? new Parameter($this->list[0]) : null; }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->list as $parameter) {
      yield $parameter->name => new Parameter($parameter);
    }
  }
}