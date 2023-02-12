<?php namespace lang\reflection\unittest;

use lang\reflection\{CannotInvoke, InvocationFailed};
use lang\{CommandLine, IllegalAccessException, Reflection, Runnable};
use test\{Assert, AssertionFailedError, Before, Expect, Test, Values};

class InvocationTest {
  use TypeDefinition;

  private $fixtures= [];

  #[Before]
  public function fixtures() {
    $this->fixtures['parent']= $this->declare('{
      public static function creation() { return "Creation"; }
      public function external() { return "External"; }
      protected function friend() { return "Friend"; }
      private function internal() { return "Internal"; }
    }');
    $this->fixtures['child']= $this->extend($this->fixtures['parent']);
  }

  #[Test, Values([['parent'], ['child']])]
  public function invoke_class_method_from($context) {
    $method= $this->fixtures[$context]->method('creation');
    Assert::equals('Creation', $method->invoke(null, []));
  }

  #[Test, Values([['parent'], ['child']])]
  public function invoke_instance_method_from($context) {
    $method= $this->fixtures[$context]->method('external');
    Assert::equals('External', $method->invoke($this->fixtures[$context]->newInstance(), []));
  }

  #[Test, Values([['friend'], ['internal']]), Expect(CannotInvoke::class)]
  public function cannot_invoke_non_public_method_by_default($method) {
    $method= $this->fixtures['parent']->method($method);
    $method->invoke($this->fixtures['parent']->newInstance(), []);
  }

  #[Test, Values([['parent', 'parent'], ['child', 'parent'], ['parent', 'child']])]
  public function invoke_private_method_in_context($instance, $context) {
    $method= $this->fixtures['parent']->method('internal');
    Assert::equals('Internal', $method->invoke($this->fixtures[$instance]->newInstance(), [], $this->fixtures[$context]));
  }

  #[Test, Expect(CannotInvoke::class)]
  public function cannot_invoke_private_method_in_incorrect_context() {
    $method= $this->fixtures['parent']->method('internal');
    $method->invoke($this->fixtures['parent']->newInstance(), [], typeof($this));
  }

  #[Test, Expect(CannotInvoke::class)]
  public function cannot_invoke_instance_method_without_instance() {
    $method= $this->fixtures['parent']->method('external');
    $method->invoke(null, []);
  }

  #[Test]
  public function exceptions_are_wrapped() {
    $t= $this->declare('{ public static function fixture() { throw new \lang\IllegalAccessException("test"); } }');
    try {
      $t->method('fixture')->invoke(null, []);
      throw new AssertionFailedError('No exception was raised');
    } catch (InvocationFailed $expected) {
      Assert::instance(IllegalAccessException::class, $expected->getCause());
    }
  }

  #[Test]
  public function arguments_can_be_omitted() {
    $t= $this->declare('{ public function fixture() { return "test"; } }');
    Assert::equals('test', $t->method('fixture')->invoke($t->newInstance()));
  }

  #[Test]
  public function returning_arg() {
    $t= $this->declare('{ public function fixture($arg) { return $arg; } }');
    Assert::equals('test', $t->method('fixture')->invoke($t->newInstance(), ['test']));
  }

  #[Test]
  public function returning_optional_arg() {
    $t= $this->declare('{ public function fixture($arg= "test") { return $arg; } }');
    Assert::equals('test', $t->method('fixture')->invoke($t->newInstance(), []));
  }

  #[Test, Values([[[]], [[1, 2, 3]]])]
  public function returning_var_args($arguments) {
    $t= $this->declare('{ public function fixture(...$arg) { return $arg; } }');
    Assert::equals($arguments, $t->method('fixture')->invoke($t->newInstance(), $arguments));
  }

  #[Test, Expect(CannotInvoke::class)]
  public function missing_required_argument() {
    $t= $this->declare('{ public function fixture($arg) { } }');
    $t->method('fixture')->invoke($t->newInstance(), []);
  }

  #[Test, Expect(CannotInvoke::class)]
  public function incorrectly_typed_argument() {
    $t= $this->declare('{ public function fixture(array $arg) { } }');
    $t->method('fixture')->invoke($t->newInstance(), [1]);
  }

  #[Test, Expect(CannotInvoke::class)]
  public function interface_methods_cannot_be_instantiated() {
    Reflection::of(Runnable::class)->method('run')->invoke(null);
  }

  #[Test, Expect(CannotInvoke::class)]
  public function abstract_methods_cannot_be_instantiated() {
    Reflection::of(CommandLine::class)->method('parse')->invoke(null, ['...']);
  }

  #[Test]
  public function invoke_class_via_closure() {
    $closure= $this->fixtures['parent']->method('creation')->closure();
    Assert::equals('Creation', $closure());
  }

  #[Test, Values([['external', 'External'], ['friend', 'Friend'], ['internal', 'Internal']])]
  public function invoke_instance_via_closure($method, $expected) {
    $closure= $this->fixtures['parent']->method($method)->closure($this->fixtures['parent']->newInstance());
    Assert::equals($expected, $closure());
  }

  #[Test]
  public function invocation_failed_target() {
    $t= $this->declare('{ public static function fixture() { throw new \lang\IllegalAccessException("test"); } }');
    try {
      $t->method('fixture')->invoke(null, []);
      throw new AssertionFailedError('No exception was raised');
    } catch (InvocationFailed $expected) {
      Assert::equals($t->method('fixture'), $expected->target());
    }
  }

  #[Test]
  public function cannot_invoke_target() {
    $t= $this->declare('{ private static function fixture() { } }');
    try {
      $t->method('fixture')->invoke(null, []);
      throw new AssertionFailedError('No exception was raised');
    } catch (CannotInvoke $expected) {
      Assert::equals($t->method('fixture'), $expected->target());
    }
  }

  #[Test]
  public function supports_named_arguments() {
    $t= $this->declare('{
      public static function fixture($a, $b) { return [$a, $b]; }
    }');
    Assert::equals([1, 2], $t->method('fixture')->invoke(null, ['b' => 2, 'a' => 1]));
  }

  #[Test]
  public function supports_optional_named_arguments() {
    $t= $this->declare('{
      public static function fixture($a= 1, $b= 2) { return [$a, $b]; }
    }');
    Assert::equals([1, 2], $t->method('fixture')->invoke(null, ['b' => 2]));
  }

  #[Test, Expect(CannotInvoke::class)]
  public function excess_named_arguments_raise_error() {
    $t= $this->declare('{
      public static function fixture($a, $b) { return [$a, $b]; }
    }');
    $t->method('fixture')->invoke(null, ['b' => 2, 'a' => 1, 'extra' => 3]);
  }

  #[Test, Expect(CannotInvoke::class)]
  public function missing_named_arguments_raise_error() {
    $t= $this->declare('{
      public static function fixture($a, $b) { return [$a, $b]; }
    }');
    $t->method('fixture')->invoke(null, ['a' => 1]);
  }
}