<?php namespace lang\reflection;

/** Base class for constants, properties and methods enumerations */
abstract class Members implements \IteratorAggregate {
  protected $reflect;

  public function __construct($reflect) {
    $this->reflect= $reflect;
  }

  public abstract function named($name);
}