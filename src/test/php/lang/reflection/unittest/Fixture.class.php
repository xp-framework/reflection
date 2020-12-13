<?php namespace lang\reflection\unittest;

class Fixture {
  const TEST = 'test';

  public static $DEFAULT = null;
  private $value;

  /** @param var */
  public function __construct($value= null) {
    $this->value= $value;
  }

  /** @return var */
  public function value() { return $this->value; }
}
