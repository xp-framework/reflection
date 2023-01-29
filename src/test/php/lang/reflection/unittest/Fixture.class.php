<?php namespace lang\reflection\unittest;

#[Annotated('test')]
class Fixture {

  #[Annotated('test')]
  const TEST = 'test';

  #[Annotated('test')]
  public static $DEFAULT = null;

  private $value;

  /** @param var */
  #[Annotated('test')]
  public function __construct(
    #[Annotated('test')]
    $value= null
  ) {
    $this->value= $value;
  }

  /** @return var */
  public function value() { return $this->value; }
}
