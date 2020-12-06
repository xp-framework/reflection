<?php namespace lang\reflection\unittest;

use lang\Reflect;
use lang\reflection\CannotInstantiate;
use unittest\{Assert, Test};

class InstantiationTest {
  use TypeDefinition;

  #[Test]
  public function without_arguments() {
    $t= $this->type('{}');
    Assert::instance($t->class(), $t->newInstance());
  }

  #[Test]
  public function with_argument() {
    $t= $this->type('{
      public $value= null;
      public function __construct($value) { $this->value= $value; }
    }');
    Assert::equals($this, $t->newInstance($this)->value);
  }

  #[Test, Expect(CannotInstantiate::class)]
  public function exceptions_are_wrapped() {
    $type= $this->type('{
      public function __construct() { throw new \lang\IllegalAccessException("test"); }
    }');
    $type->newInstance();
  }
}