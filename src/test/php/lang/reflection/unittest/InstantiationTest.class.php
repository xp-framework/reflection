<?php namespace lang\reflection\unittest;

use lang\Reflect;
use lang\reflection\{CannotInstantiate, InvocationFailed};
use unittest\{Assert, Test};

class InstantiationTest {
  use TypeDefinition;

  #[Test]
  public function without_arguments() {
    $t= $this->declare('{}');
    Assert::instance($t->class(), $t->newInstance());
  }

  #[Test]
  public function with_argument() {
    $t= $this->declare('{
      public $value= null;
      public function __construct($value) { $this->value= $value; }
    }');
    Assert::equals($this, $t->newInstance($this)->value);
  }

  #[Test, Expect(InvocationFailed::class)]
  public function exceptions_are_wrapped() {
    $type= $this->declare('{
      public function __construct() { throw new \lang\IllegalAccessException("Test"); }
    }');
    $type->newInstance();
  }

  #[Test, Expect(CannotInstantiate::class)]
  public function private_constructor() {
    $type= $this->declare('{
      private function __construct() { throw new \lang\IllegalAccessException("Unreachable"); }
    }');
    $type->newInstance();
  }
}