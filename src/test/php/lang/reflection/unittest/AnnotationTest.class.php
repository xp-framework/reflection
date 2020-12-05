<?php namespace lang\reflection\unittest;

use lang\reflection\Annotation;
use unittest\{Assert, Test, Values};

class AnnotationTest {
  use TypeDefinition;

  /**
   * Assertion helper
   *
   * @param  [:var] $expected
   * @param  iterable $annotations
   * @throws unittest.AssertionFailedError
   */
  private function assertAnnotations($expected, $annotations) {
    $actual= [];
    foreach ($annotations as $type => $annotation) {
      $actual[$type]= $annotation->arguments();
    }
    Assert::equals($expected, $actual);
  }

  /** @return iterable */
  private function scalars() {
    yield ['#[Annotated(null)]', [null]];
    yield ['#[Annotated(false)]', [false]];
    yield ['#[Annotated(true)]', [true]];
    yield ['#[Annotated(1)]', [1]];
    yield ['#[Annotated(1.5)]', [1.5]];
    yield ['#[Annotated("")]', ['']];
    yield ['#[Annotated("test")]', ['test']];
  }

  /** @return iterable */
  private function arrays() {
    yield ['#[Annotated([])]', [[]]];
    yield ['#[Annotated([1, 2,])]', [[1, 2]]];
    yield ['#[Annotated([1, 2, 3])]', [[1, 2, 3]]];
    yield ['#[Annotated(["key" => "value"])]', [['key' => 'value']]];
    yield ['#[Annotated(["key" => "value",])]', [['key' => 'value']]];
    yield ['#[Annotated(["a" => 1, "b" => 2])]', [['a' => 1, 'b' => 2]]];
  }

  /** @return iterable */
  private function expressions() {
    yield ['#[Annotated(+1)]', [1]];
    yield ['#[Annotated(-1)]', [-1]];
    yield ['#[Annotated(~1)]', [-2]];
    yield ['#[Annotated(!true)]', [false]];
    yield ['#[Annotated(true && false)]', [false]];
    yield ['#[Annotated(true || false)]', [true]];
    yield ['#[Annotated(1 + 1)]', [2]];
    yield ['#[Annotated(2 - 1)]', [1]];
    yield ['#[Annotated(2 * 3)]', [6]];
    yield ['#[Annotated(4 / 2)]', [2]];
    yield ['#[Annotated(4 % 3)]', [1]];
    yield ['#[Annotated(4 ^ 1)]', [5]];
    yield ['#[Annotated(2 ** 3)]', [8]];
    yield ['#[Annotated(2 << 1)]', [4]];
    yield ['#[Annotated(4 >> 2)]', [1]];
    yield ['#[Annotated(2 + 4 * 3)]', [14]];
  }

  /** @return iterable */
  private function arguments() {
    yield ['#[Annotated]', []];
    yield ['#[Annotated()]', []];
    yield ['#[Annotated(1)]', [1]];
    yield ['#[Annotated(1, 2)]', [1, 2]];
    yield ['#[Annotated(using: "values")]', ['using' => 'values']];
    yield ['#[Annotated(true, using: "values")]', [0 => true, 'using' => 'values']];
  }

  #[Test, Values('scalars')]
  public function with_scalar($annotation, $arguments) {
    $t= $this->type('{}', $annotation);
    $this->assertAnnotations([Annotated::class => $arguments], $t->annotations());
  }

  #[Test, Values('arrays')]
  public function with_array($annotation, $arguments) {
    $t= $this->type('{}', $annotation);
    $this->assertAnnotations([Annotated::class => $arguments], $t->annotations());
  }

  #[Test, Values('expressions')]
  public function with_expression($annotation, $arguments) {
    $t= $this->type('{}', $annotation);
    $this->assertAnnotations([Annotated::class => $arguments], $t->annotations());
  }

  #[Test, Values('arguments')]
  public function with($annotation, $arguments) {
    $t= $this->type('{}', $annotation);
    $this->assertAnnotations([Annotated::class => $arguments], $t->annotations());
  }

  #[Test]
  public function with_class_reference() {
    $t= $this->type('{}', '#[Annotated(Fixture::class)]');
    $this->assertAnnotations([Annotated::class => [Fixture::class]], $t->annotations());
  }

  #[Test]
  public function with_constant_reference() {
    $t= $this->type('{}', '#[Annotated(Fixture::TEST)]');
    $this->assertAnnotations([Annotated::class => ['test']], $t->annotations());
  }

  #[Test]
  public function multiple() {
    $t= $this->type('{}', '#[Annotated, Enumeration([])]');
    $this->assertAnnotations([Annotated::class => [], Enumeration::class => [[]]], $t->annotations());
  }

  #[Test]
  public function on_constant() {
    $t= $this->type('{
      #[Annotated]
      const FIXTURE = "test";
    }');
    $this->assertAnnotations([Annotated::class => []], $t->constant('FIXTURE')->annotations());
  }

  #[Test]
  public function on_property() {
    $t= $this->type('{
      #[Annotated]
      public $fixture;
    }');
    $this->assertAnnotations([Annotated::class => []], $t->property('fixture')->annotations());
  }

  #[Test]
  public function on_method() {
    $t= $this->type('{
      #[Annotated]
      public function fixture() { }
    }');
    $this->assertAnnotations([Annotated::class => []], $t->method('fixture')->annotations());
  }

  #[Test]
  public function by_type() {
    $t= $this->type('{}', '#[Annotated]');
    Assert::equals(new Annotation(Annotated::class, []), $t->annotations()->type(Annotated::class));
  } 

  #[Test]
  public function non_existant() {
    $t= $this->type('{}');
    Assert::null($t->annotations()->type(Annotated::class));
  }

  #[Test]
  public function three() {
    $t= $this->type('{}', '#[Annotated, Enumeration("byValue"), Error(Fixture::class)]');
    $this->assertAnnotations(
      [Annotated::class => [], Enumeration::class => ['byValue'], Error::class => [Fixture::class]],
      $t->annotations()
    );
  } 
}