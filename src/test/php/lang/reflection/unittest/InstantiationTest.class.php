<?php namespace lang\reflection\unittest;

use lang\reflection\{CannotInstantiate, InvocationFailed};
use lang\{Reflection, Runnable, CommandLine, IllegalAccessException};
use unittest\actions\RuntimeVersion;
use unittest\{Assert, Action, Expect, Test, Values, AssertionFailedError};

class InstantiationTest {
  use TypeDefinition;

  /** @return iterable */
  private function invocations() {
    yield [function($t, $args) { return $t->newInstance(...$args); }];
    yield [function($t, $args) { return $t->constructor()->newInstance($args); }];
  }

  #[Test]
  public function instantiate_type() {
    $t= $this->declare('{}');
    Assert::instance($t->class(), $t->newInstance());
  }

  #[Test]
  public function arguments_can_be_omitted() {
    $t= $this->declare('{ public function __construct() { }}');
    Assert::instance($t->class(), $t->constructor()->newInstance());
  }

  #[Test, Values('invocations')]
  public function with_empty_constructor($invocation) {
    $t= $this->declare('{ public function __construct() { }}');
    Assert::instance($t->class(), $invocation($t, []));
  }

  #[Test, Values('invocations')]
  public function with_argument($invocation) {
    $t= $this->declare('{
      public $value= null;
      public function __construct($value) { $this->value= $value; }
    }');
    Assert::equals($this, $invocation($t, [$this])->value);
  }

  #[Test, Values('invocations')]
  public function exceptions_are_wrapped($invocation) {
    $t= $this->declare('{ public function __construct() { throw new \lang\IllegalAccessException("Test"); } }');
    try {
      $invocation($t, []);
      throw new AssertionFailedError('No exception was raised');
    } catch (InvocationFailed $expected) {
      Assert::instance(IllegalAccessException::class, $expected->getCause());
    }
  }

  #[Test, Expect(CannotInstantiate::class), Values('invocations')]
  public function cannot_instantiate_using_private_constructor($invocation) {
    $t= $this->declare('{ private function __construct() { } }');
    $invocation($t, []);
  }

  #[Test, Expect(CannotInstantiate::class), Values('invocations')]
  public function cannot_instantiate_using_protected_constructor($invocation) {
    $t= $this->declare('{ protected function __construct() { } }');
    $invocation($t, []);
  }

  #[Test]
  public function instantiate_with_private_constructor_in_context() {
    $t= $this->declare('{
      public $value= null;
      private function __construct($value) { $this->value= $value; }
    }');
    Assert::equals($this, $t->constructor()->newInstance([$this], $t)->value);
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0")')]
  public function instantiate_with_constructor_promotion() {
    $t= $this->declare('{ private function __construct(public $value) { } }');
    Assert::equals($this, $t->constructor()->newInstance([$this])->value);
  }

  #[Test, Expect(CannotInstantiate::class)]
  public function cannot_instantiate_with_private_constructor_in_incorrect_context() {
    $t= $this->declare('{ private function __construct() { } }');
    $t->constructor()->newInstance([], typeof($this));
  }

  #[Test, Expect(CannotInstantiate::class)]
  public function interfaces_cannot_be_instantiated() {
    Reflection::of(Runnable::class)->newInstance();
  }

  #[Test, Expect(CannotInstantiate::class)]
  public function abstract_classes_cannot_be_instantiated() {
    Reflection::of(CommandLine::class)->newInstance();
  }
}