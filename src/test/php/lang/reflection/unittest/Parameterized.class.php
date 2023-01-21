<?php namespace lang\reflection\unittest;

use lang\IllegalArgumentException;

class Parameterized implements Declared {
  private $a, $b;

  /** @throws lang.IllegalArgumentException */
  public function __construct(int $a, int $b) {
    if ($b < $a) {
      throw new IllegalArgumentException('b may not be smaller than a');
    }

    $this->a= $a;
    $this->b= $b;
  }
}