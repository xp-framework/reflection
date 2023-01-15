<?php namespace lang\reflection\unittest;

class Parameterized implements Declared {
  private $a, $b;

  public function __construct($a, $b) {
    $this->a= $a;
    $this->b= $b;
  }
}