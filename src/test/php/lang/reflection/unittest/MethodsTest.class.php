<?php namespace lang\reflection\unittest;

use lang\{Type, ArrayType, FunctionType, TypeUnion, Primitive, IllegalStateException};
use unittest\actions\RuntimeVersion;
use unittest\{Assert, Expect, Test, Values};

class MethodsTest {
  use TypeDefinition;

  /** @return iterable */
  private function returnTypes() {
    yield ['%s()', false, Type::$VAR];
    yield ['%s(): array', true, Type::$ARRAY];
    yield ['%s(): callable', true, Type::$CALLABLE];
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
    yield ['%s(array $arg)', ['arg' => [true, Type::$ARRAY]]];
    yield ['%s(callable $arg)', ['arg' => [true, Type::$CALLABLE]]];
    yield ['/** @param string $arg */ %s($arg)', ['arg' => [false, Primitive::$STRING]]];
    yield ['/** @param array<string> $arg */ %s($arg)', ['arg' => [false, new ArrayType(Primitive::$STRING)]]];
  }

  #[Test]
  public function name() {
    Assert::equals('fixture', $this->declare('{ public function fixture() { } }')->method('fixture')->name());
  }

  #[Test]
  public function compoundName() {
    $t= $this->declare('{ public function fixture() { } }');
    Assert::equals($t->name().'::fixture()', $t->method('fixture')->compoundName());
  }

  #[Test]
  public function declaredIn() {
    $t= $this->declare('{ public function fixture() { } }');
    Assert::equals($t, $t->method('fixture')->declaredIn());
  }

  #[Test]
  public function named() {
    $type= $this->declare('{ public function fixture() { } }');
    Assert::equals($type->method('fixture'), $type->methods()->named('fixture'));
  }

  #[Test]
  public function annotated() {
    $type= $this->declare('{
      #[Annotated]
      public function a() { }

      #[Annotated]
      public function b() { }

      public function c() { }
    }');

    Assert::equals(
      ['a' => $type->method('a'), 'b' => $type->method('b')],
      iterator_to_array($type->methods()->annotated(Annotated::class))
    );
  }

  #[Test]
  public function non_existant() {
    $type= $this->declare('{ }');
    Assert::null($type->methods()->named('fixture'));
  }

  #[Test]
  public function without_methods() {
    Assert::equals([], iterator_to_array($this->declare('{ }')->methods()));
  }

  #[Test]
  public function methods() {
    $type= $this->declare('{ public function one() { } public function two() { } }');
    Assert::equals(
      ['one' => $type->method('one'), 'two' => $type->method('two')],
      iterator_to_array($type->methods())
    );
  }

  #[Test, Values('returnTypes')]
  public function returns($decl, $present, $expected) {
    $returns= $this->declare('{ '.sprintf($decl, 'function fixture').' { } }')->method('fixture')->returns();
    Assert::equals([$present, $expected], [$returns->present(), $returns->type()]);
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0")')]
  public function returns_type_union() {
    $returns= $this->declare('{ function fixture(): string|int { } }')->method('fixture')->returns();
    Assert::equals(new TypeUnion([Primitive::$STRING, Primitive::$INT]), $returns->type());
  }

  #[Test]
  public function returns_self() {
    $type= $this->declare('{ function fixture(): self { } }');
    Assert::equals($type->class(), $type->method('fixture')->returns()->type());
  }

  #[Test, Values([0, 'arg'])]
  public function parameter_by($lookup) {
    $parameter= $this->declare('{ function fixture($arg) { } }')->method('fixture')->parameter($lookup);
    Assert::equals([0, 'arg'], [$parameter->position(), $parameter->name()]);
  }

  #[Test, Values([0, 'arg'])]
  public function non_existant_parameter_by($lookup) {
    Assert::null($this->declare('{ function fixture() { } }')->method('fixture')->parameter($lookup));
  }

  #[Test, Values('parameterTypes')]
  public function parameters($decl, $expected) {
    $method= $this->declare('{ '.sprintf($decl, 'function fixture').' { } }')->method('fixture');
    $actual= [];
    foreach ($method->parameters() as $name => $parameter) {
      $type= $parameter->constraint();
      $actual[$name]= [$type->present(), $type->type()];
    }
    Assert::equals($expected, $actual);
  }

  #[Test, Values([['fixture()', 0], ['fixture($a)', 1], ['fixture($a, $b)', 2], ['fixture($a, $b= null)', 2]])]
  public function number_of_parameters($declaration, $expected) {
    $method= $this->declare(sprintf('{ function %s { } }', $declaration))->method('fixture');
    Assert::equals($expected, $method->parameters()->size());
  }

  #[Test, Values([['fixture()', 0], ['fixture($a)', 1], ['fixture($a, $b)', 2], ['fixture($a, $b= null)', 1]])]
  public function number_of_required_parameters($declaration, $expected) {
    $method= $this->declare('{ function fixture($arg) { } }')->method('fixture');
    Assert::equals(1, $method->parameters()->size(true));
  }

  #[Test]
  public function first_parameter() {
    $method= $this->declare('{ function fixture($arg) { } }')->method('fixture');
    Assert::equals($method->parameters()->first(), $method->parameter(0));
  }

  #[Test]
  public function variadic_parameter() {
    $type= $this->declare('{ function fixture(... $arg) { } }');
    Assert::true($type->method('fixture')->parameter(0)->variadic());
  }

  #[Test]
  public function non_variadic_parameter() {
    $type= $this->declare('{ function fixture($arg) { } }');
    Assert::false($type->method('fixture')->parameter(0)->variadic());
  }

  #[Test]
  public function optional_parameter() {
    $type= $this->declare('{ function fixture($arg= null) { } }');
    Assert::true($type->method('fixture')->parameter(0)->optional());
  }

  #[Test]
  public function required_parameter() {
    $type= $this->declare('{ function fixture($arg) { } }');
    Assert::false($type->method('fixture')->parameter(0)->optional());
  }

  #[Test]
  public function default_value() {
    $type= $this->declare('{ function fixture($arg= "Test") { } }');
    Assert::equals('Test', $type->method('fixture')->parameter(0)->default());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function default_value_for_required() {
    $type= $this->declare('{ function fixture($arg) { } }');
    $type->method('fixture')->parameter(0)->default();
  }

  #[Test]
  public function self_parameter_constraint() {
    $type= $this->declare('{ function fixture(self $arg) { } }');
    Assert::equals($type->class(), $type->method('fixture')->parameter(0)->constraint()->type());
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0")')]
  public function type_union_parameter_constraint() {
    $param= $this->declare('{ function fixture(string|int $arg) { } }')->method('fixture')->parameter(0);
    Assert::equals(new TypeUnion([Primitive::$STRING, Primitive::$INT]), $param->constraint()->type());
  }

  #[Test]
  public function parameter_annotations() {
    $t= $this->declare('{
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
    $t= $this->declare('{
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
    $t= $this->declare('{
      #[Author("Test")]
      public function fixture() { }
    }');

    Assert::equals(['Test'], $t->method('fixture')->annotation(Author::class)->arguments());
  }

  #[Test]
  public function without_comment() {
    $t= $this->declare('{ public function fixture() { } }');
    Assert::null($t->method('fixture')->comment());
  }

  #[Test]
  public function with_single_line_comment() {
    $t= $this->declare('{ /** Returns the fixture */ public function fixture() { } }');
    Assert::equals('Returns the fixture', $t->method('fixture')->comment());
  }

  #[Test]
  public function with_multi_line_comment() {
    $t= $this->declare('{
      /**
       * Returns the fixture
       * or NULL.
       */
      public function fixture() { }
    }');
    Assert::equals("Returns the fixture\nor NULL.", $t->method('fixture')->comment());
  }

  #[Test]
  public function hash_code() {
    $t= $this->declare('{ public function fixture() { } }');
    Assert::equals($t->name().'::fixture()', $t->method('fixture')->hashCode());
  }

  #[Test]
  public function string_representation_with_typed_parameter() {
    $t= $this->declare('{ public function fixture(array $s): string { } }');
    Assert::equals(
      'public function fixture(array $s): string',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_with_apidoc_parameter() {
    $t= $this->declare('{
      /** @param array<string> $s */
      public function fixture($s): string { }
    }');
    Assert::equals(
      'public function fixture(array<string> $s): string',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_with_unconstrained_parameter() {
    $t= $this->declare('{ public function fixture($s): string { } }');
    Assert::equals(
      'public function fixture(var $s): string',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_with_typed_return() {
    $t= $this->declare('{ public function fixture(): string { } }');
    Assert::equals(
      'public function fixture(): string',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_with_apidoc_return() {
    $t= $this->declare('{
      /** @return array<string> */
      public function fixture() { }
    }');
    Assert::equals(
      'public function fixture(): array<string>',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_without_return() {
    $t= $this->declare('{ public function fixture() { } }');
    Assert::equals(
      'public function fixture(): var',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function compare_to_self() {
    $type= $this->declare('{ public function a() { } }');
    Assert::equals(0, $type->method('a')->compareTo($type->method('a')));
  }

  #[Test]
  public function compare_to_other_method() {
    $type= $this->declare('{ public function a() { } public function b() { } }');
    Assert::equals(-1, $type->method('a')->compareTo($type->method('b')));
  }

  #[Test]
  public function compare_to_other_value() {
    $type= $this->declare('{ public function a() { } }');
    Assert::equals(1, $type->method('a')->compareTo(null));
  }
}