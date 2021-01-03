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
    $t= $this->declare('{ public function __construct(public $value) { } }');
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

  #[Test]
  public function instantiate_constructorless() {
    $t= $this->declare('{}');
    Assert::instance($t->class(), $t->instantiation(null)->newInstance());
  }

  #[Test]
  public function instantiate_without_invoking_constructor() {
    $t= $this->declare('{
      public function __construct() { throw new \lang\IllegalAccessException("Should not be called"); }
    }');
    Assert::instance($t->class(), $t->instantiation(null)->newInstance());
  }

  #[Test]
  public function instantiate_setting_property() {
    $t= $this->declare('{
      private $name;
      public function name() { return $this->name; }
    }');

    $instantiation= $t->instantiation(function($name) { $this->name= $name; });
    Assert::equals('Test', $instantiation->newInstance(['Test'])->name());
  }

  #[Test]
  public function instantiate_from_unserialize() {
    $t= $this->declare('{
      private $name;
      public function name() { return $this->name; }

      public function __unserialize($data) {
        $this->name= $data["name"];
      }
    }');

    $instantiation= $t->instantiation('__unserialize');
    Assert::equals('Test', $instantiation->newInstance([['name' => 'Test']])->name());
  }

  #[Test]
  public function instantiate_from_private_constructor() {
    $t= $this->declare('{
      private $name;
      public function name() { return $this->name; }

      private function __construct($name) { $this->name= $name; }
    }');

    $instantiation= $t->instantiation('__construct');
    Assert::equals('Test', $instantiation->newInstance(['Test'])->name());
  }

  #[Test]
  public function instantiate_on_interface() {
    Assert::null(Reflection::of(Runnable::class)->instantiation(null));
  }

  #[Test]
  public function instantiate_with_non_existant_method_reference() {
    Assert::null($this->declare('{}')->instantiation('__unserialize'));
  }

  #[Test, Expect(InvocationFailed::class)]
  public function exceptions_from_initializer_functions_are_wrapped() {
    $t= $this->declare('{}');
    $t->instantiation(function() { throw new IllegalAccessException('Test'); })->newInstance();
  }

  #[Test, Expect(InvocationFailed::class)]
  public function exceptions_from_instantiation_methods_are_wrapped() {
    $t= $this->declare('{
      public function raise() { throw new \lang\IllegalAccessException("Test"); }
    }');
    $t->instantiation('raise')->newInstance();
  }
}