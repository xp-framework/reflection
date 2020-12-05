<?php namespace lang\reflection\unittest;

use lang\reflection\CannotInvoke;
use unittest\{Assert, Expect, Test};

class MethodsTest {
  use TypeDefinition;

  #[Test]
  public function name() {
    Assert::equals('fixture', $this->type('{ public function fixture() { } }')->method('fixture')->name());
  }

  #[Test]
  public function invoke_class_method() {
    $type= $this->type('{ public static function fixture() { return "Test"; } }');
    Assert::equals('Test', $type->method('fixture')->invoke(null, []));
  }

  #[Test]
  public function invoke_instance_method() {
    $type= $this->type('{ public function fixture() { return "Test"; } }');
    Assert::equals('Test', $type->method('fixture')->invoke($type->newInstance(), []));
  }

  #[Test]
  public function invoke_private_method_in_type_context() {
    $type= $this->type('{ private function fixture() { return "Test"; } }');
    Assert::equals('Test', $type->method('fixture')->invoke($type->newInstance(), [], $type));
  }

  #[Test]
  public function named() {
    $type= $this->type('{ public function fixture() { } }');
    Assert::equals($type->method('fixture'), $type->methods()->named('fixture'));
  }

  #[Test, Expect(CannotInvoke::class)]
  public function exceptions_are_wrapped() {
    $type= $this->type('{
      public static function fixture() { throw new \lang\IllegalAccessException("test"); }
    }');
    $type->method('fixture')->invoke(null, []);
  }

  #[Test]
  public function non_existant() {
    $type= $this->type('{ }');
    Assert::null($type->methods()->named('fixture'));
  }

  #[Test]
  public function without_methods() {
    Assert::equals([], iterator_to_array($this->type('{ }')->methods()));
  }

  #[Test]
  public function methods() {
    $type= $this->type('{
      public function one() { }
      public function two() { }
    }');
    Assert::equals(
      ['one' => $type->method('one'), 'two' => $type->method('two')],
      iterator_to_array($type->methods())
    );
  }
}