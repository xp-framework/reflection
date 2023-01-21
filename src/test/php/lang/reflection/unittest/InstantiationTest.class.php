<?php namespace lang\reflection\unittest;

use lang\reflection\{CannotInstantiate, InvocationFailed};
use lang\{Reflection, Runnable, CommandLine, Error, IllegalAccessException};
use unittest\actions\RuntimeVersion;
use unittest\{Assert, Action, Expect, Test, Values, AssertionFailedError};
use util\Date;

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

  #[Test, Values('invocations')]
  public function type_errors_are_wrapped($invocation) {
    $t= $this->declare('{
      public function __construct(\util\Date $date) { }
    }');
    try {
      $invocation($t, [null]);
      throw new AssertionFailedError('No exception was raised');
    } catch (CannotInstantiate $expected) {
      Assert::instance(Error::class, $expected->getCause());
    }
  }

  #[Test, Values('invocations')]
  public function missing_arguments_are_wrapped($invocation) {
    $t= $this->declare('{
      public function __construct(\util\Date $date) { }
    }');
    try {
      $invocation($t, []);
      throw new AssertionFailedError('No exception was raised');
    } catch (CannotInstantiate $expected) {
      Assert::instance(Error::class, $expected->getCause());
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
    Assert::instance($t->class(), $t->initializer(null)->newInstance());
  }

  #[Test]
  public function instantiate_without_invoking_constructor() {
    $t= $this->declare('{
      public function __construct() { throw new \lang\IllegalAccessException("Should not be called"); }
    }');
    Assert::instance($t->class(), $t->initializer(null)->newInstance());
  }

  #[Test]
  public function instantiate_setting_property() {
    $t= $this->declare('{
      private $name;
      public function name() { return $this->name; }
    }');

    $initializer= $t->initializer(function($name) { $this->name= $name; });
    Assert::equals('Test', $initializer->newInstance(['Test'])->name());
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

    $initializer= $t->initializer('__unserialize');
    Assert::equals('Test', $initializer->newInstance([['name' => 'Test']])->name());
  }

  #[Test]
  public function instantiate_from_private_constructor() {
    $t= $this->declare('{
      private $name;
      public function name() { return $this->name; }

      private function __construct($name) { $this->name= $name; }
    }');

    $initializer= $t->initializer('__construct');
    Assert::equals('Test', $initializer->newInstance(['Test'], $t)->name());
  }

  #[Test]
  public function instantiate_on_interface() {
    Assert::null(Reflection::of(Runnable::class)->initializer(null));
  }

  #[Test]
  public function instantiate_with_non_existant_method_reference() {
    Assert::null($this->declare('{}')->initializer('__unserialize'));
  }

  #[Test, Expect(InvocationFailed::class)]
  public function exceptions_from_initializer_functions_are_wrapped() {
    $t= $this->declare('{}');
    $t->initializer(function() { throw new IllegalAccessException('Test'); })->newInstance();
  }

  #[Test, Expect(InvocationFailed::class)]
  public function exceptions_from_initializer_methods_are_wrapped() {
    $t= $this->declare('{
      public function raise() { throw new \lang\IllegalAccessException("Test"); }
    }');
    $t->initializer('raise')->newInstance();
  }

  #[Test]
  public function supports_named_arguments() {
    $t= $this->declare('{
      public $values;
      public function __construct($a, $b) { $this->values= [$a, $b]; }
    }');
    Assert::equals([1, 2], $t->constructor()->newInstance(['b' => 2, 'a' => 1])->values);
  }

  #[Test]
  public function supports_optional_named_arguments() {
    $t= $this->declare('{
      public $values;
      public function __construct($a= 1, $b= 2) { $this->values= [$a, $b]; }
    }');
    Assert::equals([1, 2], $t->constructor()->newInstance(['b' => 2])->values);
  }

  #[Test, Expect(CannotInstantiate::class)]
  public function excess_named_arguments_raise_error() {
    $t= $this->declare('{
      public $values;
      public function __construct($a, $b) { $this->values= [$a, $b]; }
    }');
    $t->constructor()->newInstance(['b' => 2, 'a' => 1, 'extra' => 3]);
  }

  #[Test, Expect(CannotInstantiate::class)]
  public function unknown_named_arguments_raise_error() {
    $t= $this->declare('{
      public $values;
      public function __construct($a, $b) { $this->values= [$a, $b]; }
    }');
    $t->constructor()->newInstance(['c' => 3]);
  }
}