<?php namespace lang\reflection;

use IteratorAggregate;

/** Base class for constants, properties and methods enumerations */
abstract class Members implements IteratorAggregate {
  protected $reflect;

  /** @param ReflectionClassConstant|ReflectionProperty|ReflectionMethod $reflect */
  public function __construct($reflect) {
    $this->reflect= $reflect;
  }

  /**
   * Returns a member by a given name
   *
   * @param  string $name
   * @return ?static
   */
  public abstract function named($name);
}