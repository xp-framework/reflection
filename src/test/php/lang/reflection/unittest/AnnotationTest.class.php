<?php namespace lang\reflection\unittest;

use lang\reflection\Annotation;
use lang\{XPClass, Reflection};
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

  /** @return iterable */
  private function evaluation() {
    yield ['#[Annotated(eval: "\"Test\"")]', ['Test']];
    yield ['#[Annotated(eval: \'"Test"\')]', ['Test']];
    yield ['#[Annotated(eval: "new Fixture()")]', [new Fixture()]];
    yield ['#[Annotated(eval: "Fixture::\$DEFAULT")]', [Fixture::$DEFAULT]];
    yield ['#[Annotated(eval: "self::\$member")]', ['Test']];
  }

  /** @return iterable */
  private function types() {
    yield [Declared::class];
    yield [new XPClass(Declared::class)];
    yield [Reflection::type(Declared::class)];
  }

  #[Test, Values('scalars')]
  public function with_scalar($annotation, $arguments) {
    $t= $this->declare('{}', $annotation);
    $this->assertAnnotations([Annotated::class => $arguments], $t->annotations());
  }

  #[Test, Values('arrays')]
  public function with_array($annotation, $arguments) {
    $t= $this->declare('{}', $annotation);
    $this->assertAnnotations([Annotated::class => $arguments], $t->annotations());
  }

  #[Test, Values('expressions')]
  public function with_expression($annotation, $arguments) {
    $t= $this->declare('{}', $annotation);
    $this->assertAnnotations([Annotated::class => $arguments], $t->annotations());
  }

  #[Test, Values('arguments')]
  public function with($annotation, $arguments) {
    $t= $this->declare('{}', $annotation);
    $this->assertAnnotations([Annotated::class => $arguments], $t->annotations());
  }

  #[Test, Values('evaluation')]
  public function with_eval($annotation, $arguments) {
    $t= $this->declare('{ private static $member= "Test"; }', $annotation);
    $this->assertAnnotations([Annotated::class => $arguments], $t->annotations());
  }

  #[Test]
  public function with_class_reference() {
    $t= $this->declare('{}', '#[Annotated(Fixture::class)]');
    $this->assertAnnotations([Annotated::class => [Fixture::class]], $t->annotations());
  }

  #[Test]
  public function with_constant_reference() {
    $t= $this->declare('{}', '#[Annotated(Fixture::TEST)]');
    $this->assertAnnotations([Annotated::class => ['test']], $t->annotations());
  }

  #[Test]
  public function with_function() {
    $t= $this->declare('{}', '#[Annotated(eval: "function() { return 6100; }")]');
    $f= $t->annotation(Annotated::class)->argument(0);

    Assert::instance('callable', $f);
    Assert::equals(6100, $f());
  }

  #[Test, Values([['fn() => 6100', 6100], ['fn() => array(1, array(2))', [1, [2]]], ['fn() => [array(1), array(2)]', [[1], [2]]]])]
  public function with_lambda($code, $expected) {
    $t= $this->declare('{}', '#[Annotated(eval: "'.$code.'")]');
    $f= $t->annotation(Annotated::class)->argument(0);

    Assert::instance('callable', $f);
    Assert::equals($expected, $f());
  }

  #[Test]
  public function with_nested_lambda() {
    $t= $this->declare('{}', '#[Annotated(eval: "new Fixture(fn() => 6100)")]');
    $f= $t->annotation(Annotated::class)->argument(0);

    Assert::instance(Fixture::class, $f);
    Assert::equals(6100, ($f->value())());
  }

  #[Test]
  public function with_array_lambda() {
    $t= $this->declare('{}', '#[Annotated(eval: "[fn() => 6100]")]');
    $f= $t->annotation(Annotated::class)->argument(0);

    Assert::instance('array', $f);
    Assert::equals(6100, $f[0]());
  }

  #[Test]
  public function multiple() {
    $t= $this->declare('{}', '#[Annotated, Enumeration([])]');
    $this->assertAnnotations([Annotated::class => [], Enumeration::class => [[]]], $t->annotations());
  }

  #[Test]
  public function name() {
    $t= $this->declare('{}', '#[Annotated]');
    Assert::equals('annotated', $t->annotation(Annotated::class)->name());
  }

  #[Test]
  public function name_in_namespace() {
    $t= $this->declare('{}', '#[\lang\reflection\unittest\Annotated]');
    Assert::equals('annotated', $t->annotation(Annotated::class)->name());
  }

  #[Test]
  public function name_in_global_namespace() {
    $t= $this->declare('{}', '#[\Annotated]');
    Assert::equals('annotated', $t->annotation(\Annotated::class)->name());
  }

  #[Test]
  public function literal() {
    $t= $this->declare('{}', '#[Annotated]');
    Assert::equals(Annotated::class, $t->annotation(Annotated::class)->type());
  }

  #[Test]
  public function no_arguments() {
    $t= $this->declare('{}', '#[Annotated]');
    Assert::equals([], $t->annotation(Annotated::class)->arguments());
  }

  #[Test]
  public function with_argument() {
    $t= $this->declare('{}', '#[Annotated(Fixture::class)]');
    Assert::equals([Fixture::class], $t->annotation(Annotated::class)->arguments());
  }

  #[Test]
  public function mutiple_arguments() {
    $t= $this->declare('{}', '#[Annotated(Fixture::class, true)]');
    Assert::equals([Fixture::class, true], $t->annotation(Annotated::class)->arguments());
  }

  #[Test, Values([[0, 'test'], [1, true], ['version', 2]])]
  public function select_argument($select, $expected) {
    $t= $this->declare('{}', '#[Annotated("test", true, version: 2)]');
    Assert::equals($expected, $t->annotation(Annotated::class)->argument($select));
  }

  #[Test]
  public function instantiation() {
    $t= $this->declare('{}', '#[Annotated]');
    Assert::instance(Annotated::class, $t->annotation(Annotated::class)->newInstance());
  }

  #[Test, Values(['#[Parameterized(1, 2)]', '#[Parameterized(a: 1, b: 2)]', '#[Parameterized(b: 2, a: 1)]'])]
  public function parameterized_instantiation($declaration) {
    $t= $this->declare('{}', $declaration);
    Assert::equals(new Parameterized(1, 2), $t->annotation(Parameterized::class)->newInstance());
  }

  #[Test, Values(eval: '[[Annotated::class], [new XPClass(Annotated::class)]]')]
  public function is($type) {
    $t= $this->declare('{}', '#[Annotated]');
    Assert::true($t->annotation(Annotated::class)->is($type));
  }

  #[Test, Values([[Annotated::class, true], [Fixture::class, false]])]
  public function provides($type, $expected) {
    $t= $this->declare('{}', '#[Annotated]');
    Assert::equals($expected, $t->annotations()->provides($type));
  }

  #[Test]
  public function on_constant() {
    $t= $this->declare('{
      #[Annotated]
      const FIXTURE = "test";
    }');
    $this->assertAnnotations([Annotated::class => []], $t->constant('FIXTURE')->annotations());
  }

  #[Test]
  public function on_property() {
    $t= $this->declare('{
      #[Annotated]
      public $fixture;
    }');
    $this->assertAnnotations([Annotated::class => []], $t->property('fixture')->annotations());
  }

  #[Test]
  public function on_method() {
    $t= $this->declare('{
      #[Annotated]
      public function fixture() { }
    }');
    $this->assertAnnotations([Annotated::class => []], $t->method('fixture')->annotations());
  }

  #[Test]
  public function on_parameter() {
    $t= $this->declare('{
      public function fixture(
        #[Annotated]
        $arg
      ) { }
    }');
    $this->assertAnnotations([Annotated::class => []], $t->method('fixture')->parameter(0)->annotations());
  }

  #[Test]
  public function by_type() {
    $t= $this->declare('{}', '#[Annotated]');
    Assert::equals(new Annotation(Annotated::class, []), $t->annotations()->type(Annotated::class));
  } 

  #[Test]
  public function non_existant() {
    $t= $this->declare('{}');
    Assert::null($t->annotations()->type(Annotated::class));
  }

  #[Test]
  public function three() {
    $t= $this->declare('{}', '#[Annotated, Enumeration("byValue"), Error(Fixture::class)]');
    $this->assertAnnotations(
      [Annotated::class => [], Enumeration::class => ['byValue'], Error::class => [Fixture::class]],
      $t->annotations()
    );
  } 

  #[Test]
  public function string_representation() {
    $t= $this->declare('{}', '#[Annotated]');
    Assert::equals(
      'lang.reflection.Annotation<lang\reflection\unittest\Annotated([])>',
      $t->annotation(Annotated::class)->toString()
    );
  } 

  #[Test]
  public function hash_code() {
    $t= $this->declare('{}', '#[Annotated]');
    Assert::equals(
      'A33d3567738fa0159cd21f46db6f5d219',
      $t->annotation(Annotated::class)->hashCode()
    );
  }

  #[Test]
  public function all_of_annotated() {
    $t= $this->declare('{}', '#[Annotated, Parameterized(1, 2), Error(Fixture::class)]');
    $this->assertAnnotations([Annotated::class => []], $t->annotations()->all(Annotated::class));
  }

  #[Test]
  public function all_of_error() {
    $t= $this->declare('{}', '#[Annotated, Parameterized(1, 2), Error(Fixture::class)]');
    $this->assertAnnotations([Error::class => [Fixture::class]], $t->annotations()->all(Error::class));
  }

  #[Test, Values('types')]
  public function all_of($type) {
    $t= $this->declare('{}', '#[Annotated, Parameterized(1, 2), Error(Fixture::class)]');
    $this->assertAnnotations(
      [Annotated::class => [], Parameterized::class => [1, 2]],
      $t->annotations()->all($type)
    );
  }
}