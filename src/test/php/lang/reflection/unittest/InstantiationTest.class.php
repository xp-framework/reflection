<?php namespace lang\reflection\unittest;

use lang\reflection\{CannotInstantiate, InvocationFailed};
use lang\{CommandLine, Error, IllegalAccessException, Reflection, Runnable};
use test\verify\Runtime;
use test\{Action, Assert, AssertionFailedError, Expect, Test, Values};
use util\Date;

class InstantiationTest {
  use TypeDefinition;

  /** @return iterable */
  private function memberInitializers() {
    yield [fn($t, $args) => $t->constructor()->newInstance($args)];
    yield [fn($t, $args) => $t->initializer('__construct')->newInstance($args)];
  }

  /** @return iterable */
  private function allInitializers() {
    yield [fn($t, $args) => $t->newInstance(...$args)];
    yield [fn($t, $args) => $t->constructor()->newInstance($args)];
    yield [fn($t, $args) => $t->initializer('__construct')->newInstance($args)];
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

  #[Test, Values(from: 'allInitializers')]
  public function with_empty_constructor($invocation) {
    $t= $this->declare('{ public function __construct() { }}');
    Assert::instance($t->class(), $invocation($t, []));
  }

  #[Test, Values(from: 'allInitializers')]
  public function with_argument($invocation) {
    $t= $this->declare('{
      public $value= null;
      public function __construct($value) { $this->value= $value; }
    }');
    Assert::equals($this, $invocation($t, [$this])->value);
  }

  #[Test, Values(from: 'allInitializers')]
  public function exceptions_are_wrapped($invocation) {
    $t= $this->declare('{ public function __construct() { throw new \lang\IllegalAccessException("Test"); } }');
    try {
      $invocation($t, []);
      throw new AssertionFailedError('No exception was raised');
    } catch (InvocationFailed $expected) {
      Assert::instance(IllegalAccessException::class, $expected->getCause());
    }
  }

  #[Test, Values(from: 'allInitializers')]
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

  #[Test, Values(from: 'allInitializers')]
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

  #[Test, Expect(CannotInstantiate::class), Values(['private', 'protected'])]
  public function newInstance_cannot_instantiate_using_non_public_constructor($modifier) {
    $t= $this->declare('{ '.$modifier.' function __construct() { } }');
    $t->newInstance();
  }

  #[Test, Expect(CannotInstantiate::class), Values(['private', 'protected'])]
  public function constructor_cannot_instantiate_using_non_public_constructor($modifier) {
    $t= $this->declare('{ '.$modifier.' function __construct() { } }');
    $t->constructor()->newInstance();
  }

  #[Test, Values(['private', 'protected'])]
  public function instantiate_with_non_public_constructor_in_context($modifier) {
    $t= $this->declare('{
      public $value= null;
      '.$modifier.' function __construct($value) { $this->value= $value; }
    }');
    Assert::equals($this, $t->constructor()->newInstance([$this], $t)->value);
  }

  #[Test, Runtime(php: '>=8.0')]
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
    Reflection::type(Runnable::class)->newInstance();
  }

  #[Test, Expect(CannotInstantiate::class)]
  public function abstract_classes_cannot_be_instantiated() {
    Reflection::type(CommandLine::class)->newInstance();
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
  public function instantiate_from_private_constructor_using_initializer() {
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
    Assert::null(Reflection::type(Runnable::class)->initializer(null));
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

  #[Test, Values(from: 'memberInitializers')]
  public function supports_named_arguments($invocation) {
    $t= $this->declare('{
      public $values;
      public function __construct($a, $b) { $this->values= [$a, $b]; }
    }');
    Assert::equals([1, 2], $invocation($t, ['b' => 2, 'a' => 1])->values);
  }

  #[Test, Values(from: 'memberInitializers')]
  public function supports_optional_named_arguments($invocation) {
    $t= $this->declare('{
      public $values;
      public function __construct($a= 1, $b= 2) { $this->values= [$a, $b]; }
    }');
    Assert::equals([1, 3], $invocation($t, ['b' => 3])->values);
  }

  #[Test, Expect(CannotInstantiate::class), Values(from: 'memberInitializers')]
  public function excess_named_arguments_raise_error($invocation) {
    $t= $this->declare('{
      public $values;
      public function __construct($a, $b) { $this->values= [$a, $b]; }
    }');
    $invocation($t, ['b' => 2, 'a' => 1, 'extra' => 3]);
  }

  #[Test, Expect(CannotInstantiate::class), Values(from: 'memberInitializers')]
  public function unknown_named_arguments_raise_error($invocation) {
    $t= $this->declare('{
      public $values;
      public function __construct($a, $b) { $this->values= [$a, $b]; }
    }');
    $invocation($t, ['c' => 3]);
  }

  #[Test, Runtime(php: '>=8.0')]
  public function newInstance_supports_named_arguments() {
    $t= $this->declare('{
      public $values;
      public function __construct($a, $b) { $this->values= [$a, $b]; }
    }');
    Assert::equals([1, 2], $t->newInstance(...['b' => 2, 'a' => 1])->values);
  }

  #[Test, Runtime(php: '>=8.0')]
  public function newInstance_supports_optional_named_arguments() {
    $t= $this->declare('{
      public $values;
      public function __construct($a= 1, $b= 2) { $this->values= [$a, $b]; }
    }');
    Assert::equals([1, 3], $t->newInstance(...['b' => 3])->values);
  }

  #[Test, Runtime(php: '>=8.0'), Expect(CannotInstantiate::class)]
  public function excess_named_arguments_raise_error_for_newInstance() {
    $t= $this->declare('{
      public $values;
      public function __construct($a, $b) { $this->values= [$a, $b]; }
    }');
    $t->newInstance(...['b' => 2, 'a' => 1, 'extra' => 3]);
  }

  #[Test, Runtime(php: '>=8.0'), Expect(CannotInstantiate::class)]
  public function unknown_named_arguments_raise_error_for_newInstance() {
    $t= $this->declare('{
      public $values;
      public function __construct($a, $b) { $this->values= [$a, $b]; }
    }');
    $t->newInstance(...['c' => 3]);
  }
}