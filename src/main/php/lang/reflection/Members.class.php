<?php namespace lang\reflection;

abstract class Members implements \IteratorAggregate {
  protected $reflect;

  public function __construct($reflect) {
    $this->reflect= $reflect;
  }

  public abstract function named($name);
}