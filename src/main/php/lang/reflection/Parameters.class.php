<?php namespace lang\reflection;

class Parameters implements \IteratorAggregate {
  private $list;

  public function __construct($list) {
    $this->list= $list;
  }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->list as $parameter) {
      yield $parameter->name => new Parameter($parameter);
    }
  }
}