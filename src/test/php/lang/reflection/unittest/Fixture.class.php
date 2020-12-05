<?php namespace lang\reflection\unittest;

class Fixture {
  const TEST = 'test';
  private $value;

  /** @param var */
  public function __construct($value= null) {
    $this->value= $value;
  }

  /** @return var */
  public function value() { return $this->value; }
}
