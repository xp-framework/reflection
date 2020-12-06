<?php namespace lang\reflection\unittest;

use lang\reflection\{CannotInvoke, InvocationFailed};
use lang\{Type, ArrayType, FunctionType, TypeUnion, Primitive, IllegalStateException};
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

  /** @return iterable */
  private function parameterTypes() {
    yield ['%s()', []];
    yield ['%s($arg)', ['arg' => [false, Type::$VAR]]];
    yield ['%s(string $arg)', ['arg' => [true, Primitive::$STRING]]];
    yield ['/** @param string $arg */ %s($arg)', ['arg' => [false, Primitive::$STRING]]];
    yield ['/** @param array<string> $arg */ %s($arg)', ['arg' => [false, new ArrayType(Primitive::$STRING)]]];
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
    Assert::equals([$present, $expected], [$returns->present(), $returns->type()]);
  }

  #[Test]
  public function returns_self() {
    $type= $this->type('{ function fixture(): self { } }');
    Assert::equals($type->class(), $type->method('fixture')->returns()->type());
  }

  #[Test, Values([0, 'arg'])]
  public function parameter_by($lookup) {
    $parameter= $this->type('{ function fixture($arg) { } }')->method('fixture')->parameter($lookup);
    Assert::equals([0, 'arg'], [$parameter->position(), $parameter->name()]);
  }

  #[Test, Values([0, 'arg'])]
  public function non_existant_parameter_by($lookup) {
    Assert::null($this->type('{ function fixture() { } }')->method('fixture')->parameter($lookup));
  }

  #[Test, Values('parameterTypes')]
  public function parameters($decl, $expected) {
    $method= $this->type('{ '.sprintf($decl, 'function fixture').' { } }')->method('fixture');
    $actual= [];
    foreach ($method->parameters() as $name => $parameter) {
      $type= $parameter->constraint();
      $actual[$name]= [$type->present(), $type->type()];
    }
    Assert::equals($expected, $actual);
  }

  #[Test]
  public function variadic_parameter() {
    $type= $this->type('{ function fixture(... $arg) { } }');
    Assert::true($type->method('fixture')->parameter(0)->variadic());
  }

  #[Test]
  public function optional_parameter() {
    $type= $this->type('{ function fixture($arg= null) { } }');
    Assert::true($type->method('fixture')->parameter(0)->optional());
  }

  #[Test]
  public function default_value() {
    $type= $this->type('{ function fixture($arg= "Test") { } }');
    Assert::equals('Test', $type->method('fixture')->parameter(0)->default());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function default_value_for_required() {
    $type= $this->type('{ function fixture($arg) { } }');
    $type->method('fixture')->parameter(0)->default();
  }

  #[Test]
  public function self_parameter_type() {
    $type= $this->type('{ function fixture(self $arg) { } }');
    Assert::equals($type->class(), $type->method('fixture')->parameter(0)->constraint()->type());
  }

  #[Test]
  public function parameter_annotations() {
    $t= $this->type('{
      public function fixture(
        #[Inject]
        $arg
      ) { }
    }');
    $parameter= $t->method('fixture')->parameter('arg');

    Assert::equals(
      [Inject::class => $parameter->annotation(Inject::class)],
      iterator_to_array($parameter->annotations())
    );
  }

  #[Test]
  public function annotations() {
    $t= $this->type('{
      #[Author("Test")]
      public function fixture() { }
    }');
    $method= $t->method('fixture');

    Assert::equals(
      [Author::class => $method->annotation(Author::class)],
      iterator_to_array($method->annotations())
    );
  }

  #[Test]
  public function annotation() {
    $t= $this->type('{
      #[Author("Test")]
      public function fixture() { }
    }');

    Assert::equals(['Test'], $t->method('fixture')->annotation(Author::class)->arguments());
  }
}