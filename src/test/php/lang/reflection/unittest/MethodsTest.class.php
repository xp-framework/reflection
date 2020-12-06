<?php namespace lang\reflection\unittest;

use lang\reflection\{CannotInvoke, InvocationFailed};
use lang\{Type, ArrayType, FunctionType, TypeUnion, Primitive};
use unittest\{Assert, Expect, Test, Values};

class MethodsTest {
  use TypeDefinition;

  /** @return iterable */
  private function returnTypes() {
    yield ['%s()', false, Type::$VAR];
    yield ['%s(): array', true, Type::$ARRAY];
    yield ['%s(): string', true, Primitive::$STRING];

    yield ['/** @return string */ %s()', false, Primitive::$STRING];
    yield ['/** @return function(): int */ %s()', false, new FunctionType([], Primitive::$INT)];
    yield ['/** @return int|string */ %s()', false, new TypeUnion([Primitive::$STRING, Primitive::$INT])];
    yield ['/** @return array<string> */ %s()', false, new ArrayType(Primitive::$STRING)];
  }

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

  #[Test, Expect(InvocationFailed::class)]
  public function exceptions_are_wrapped() {
    $type= $this->type('{
      public static function fixture() { throw new \lang\IllegalAccessException("test"); }
    }');
    $type->method('fixture')->invoke(null, []);
  }

  #[Test, Expect(CannotInvoke::class)]
  public function cannot_invoke_private_method_by_default() {
    $type= $this->type('{
      private static function fixture() { }
    }');
    $type->method('fixture')->invoke(null, []);
  }

  #[Test, Expect(CannotInvoke::class)]
  public function cannot_invoke_instance_method_without_instance() {
    $type= $this->type('{
      public function fixture() { }
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
    $type= $this->type('{ public function one() { } public function two() { } }');
    Assert::equals(
      ['one' => $type->method('one'), 'two' => $type->method('two')],
      iterator_to_array($type->methods())
    );
  }

  #[Test, Values('returnTypes')]
  public function returns($decl, $present, $expected) {
    $returns= $this->type('{ '.sprintf($decl, 'function fixture').' { } }')->method('fixture')->returns();
    Assert::equals(
      ['present' => $present, 'type' => $expected],
      ['present' => $returns->present(), 'type' => $returns->type()]
    );
  }

  #[Test]
  public function returns_self() {
    $type= $this->type('{ function fixture(): self { } }');
    Assert::equals($type->class(), $type->method('fixture')->returns()->type());
  }
}