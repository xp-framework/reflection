<?php namespace lang\reflection\unittest;

use lang\{Type, ArrayType, FunctionType, TypeUnion, Primitive, IllegalStateException, IllegalArgumentException};
use unittest\actions\RuntimeVersion;
use unittest\{Assert, Expect, Test, Action, Values};

class ParametersTest {
  use TypeDefinition;

  /** @return iterable */
  private function types() {
    yield ['%s()', []];
    yield ['%s($arg)', ['arg' => [false, Type::$VAR]]];
    yield ['%s(string $arg)', ['arg' => [true, Primitive::$STRING]]];
    yield ['%s(array $arg)', ['arg' => [true, Type::$ARRAY]]];
    yield ['%s(callable $arg)', ['arg' => [true, Type::$CALLABLE]]];
    yield ['/** @param string $arg */ %s($arg)', ['arg' => [false, Primitive::$STRING]]];
    yield ['/** @param array<string> $arg */ %s($arg)', ['arg' => [false, new ArrayType(Primitive::$STRING)]]];
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

  #[Test, Values('types')]
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
    Assert::equals($method->parameter(0), $method->parameters()->first());
  }

  #[Test]
  public function no_first_parameter() {
    $method= $this->declare('{ function fixture() { } }')->method('fixture');
    Assert::equals(null, $method->parameters()->first());
  }

  #[Test]
  public function parameter_at() {
    $method= $this->declare('{ function fixture($arg) { } }')->method('fixture');
    Assert::equals($method->parameter(0), $method->parameters()->at(0));
  }

  #[Test]
  public function no_parameter_at() {
    $method= $this->declare('{ function fixture() { } }')->method('fixture');
    Assert::null($method->parameters()->at(0));
  }

  #[Test]
  public function parameter_named() {
    $method= $this->declare('{ function fixture($arg) { } }')->method('fixture');
    Assert::equals($method->parameter(0), $method->parameters()->named('arg'));
  }

  #[Test]
  public function no_parameter_named() {
    $method= $this->declare('{ function fixture() { } }')->method('fixture');
    Assert::null($method->parameters()->named('arg'));
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

  #[Test]
  public function default_value_via_default_annotation() {
    $type= $this->declare('{ function fixture(
      #[\Default("test")]
      $arg= null
    ) { } }');

    Assert::equals('test', $type->method('fixture')->parameter(0)->default());
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
}