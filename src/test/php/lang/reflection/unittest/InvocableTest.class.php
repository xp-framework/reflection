<?php namespace lang\reflection\unittest;

use Closure, ReflectionFunction;
use lang\reflection\{Invocable, CannotInvoke};
use lang\{Type, Primitive};
use test\verify\Runtime;
use test\{Assert, Expect, Test, Values};

class InvocableTest {

  /** @return iterable */
  private function callables() {
    yield ['strtoupper', 'strtoupper'];
    yield [fn() => true, null];
    yield [function() { return true; }, null];
    yield [[$this, 'name'], 'name'];
    yield [[Closure::class, 'fromCallable'], 'fromCallable'];
    yield [Closure::fromCallable('strtoupper'), 'strtoupper'];
    yield [new ReflectionFunction('strtoupper'), 'strtoupper'];
  }

  #[Test, Values(from: 'callables')]
  public function can_create($callable) {
    new Invocable($callable);
  }

  #[Test, Values(from: 'callables')]
  public function name($callable, $expected) {
    Assert::equals($expected, (new Invocable($callable))->name());
  }

  #[Test]
  public function no_comment() {
    Assert::null((new Invocable(fn() => true))->comment());
  }

  #[Test]
  public function with_comment() {
    Assert::equals('Test', (new Invocable(/** Test */ fn() => true))->comment());
  }

  #[Test, Runtime(php: '>=8.0')]
  public function no_annotations() {
    Assert::equals([], iterator_to_array((new Invocable(fn() => true))->annotations()));
  }

  #[Test, Runtime(php: '>=8.0')]
  public function with_annotation() {
    $closure= eval('return #[Test] fn() => true;');
    Assert::equals('test', (new Invocable($closure))->annotation(Test::class)->name());
  }

  #[Test, Runtime(php: '>=8.0')]
  public function with_annotations() {
    $closure= eval('return #[Test, Values] fn() => true;');

    $annotations= [];
    foreach ((new Invocable($closure))->annotations() as $annotation) {
      $annotations[]= $annotation->name();
    }
    Assert::equals(['test', 'values'], $annotations);
  }

  #[Test]
  public function empty_parameters() {
    $parameters= (new Invocable(fn() => true))->parameters();
    Assert::equals(0, $parameters->size());
  }

  #[Test]
  public function parameters() {
    $parameters= (new Invocable(fn($name) => true))->parameters();
    Assert::equals(1, $parameters->size());
  }

  #[Test, Values([0, 'name'])]
  public function parameter($arg) {
    $parameter= (new Invocable(fn($name) => true))->parameter($arg);
    Assert::equals('name', $parameter->name());
    Assert::false($parameter->optional());
  }

  #[Test]
  public function optional_parameter() {
    $parameters= (new Invocable(fn($name= null) => true))->parameters();
    Assert::equals(0, $parameters->size($required= true));
    Assert::equals(1, $parameters->size($required= false));
  }

  #[Test]
  public function without_return_type() {
    $constraint= (new Invocable(fn($name) => true))->returns();
    Assert::false($constraint->present());
    Assert::equals(Type::$VAR, $constraint->type());
  }

  #[Test]
  public function return_type() {
    $constraint= (new Invocable(fn($name): bool => true))->returns();
    Assert::true($constraint->present());
    Assert::equals(Primitive::$BOOL, $constraint->type());
  }

  #[Test]
  public function invoke() {
    $Invocable= (new Invocable(fn($name) => strtoupper($name)));
    Assert::equals('TEST', $Invocable->invoke(['test']));
  }

  #[Test]
  public function invoke_named() {
    $Invocable= (new Invocable(fn($name) => strtoupper($name)));
    Assert::equals('TEST', $Invocable->invoke(['name' => 'test']));
  }

  #[Test, Expect(CannotInvoke::class)]
  public function invoke_missing_argument() {
    $Invocable= (new Invocable(fn($name) => strtoupper($name)));
    $Invocable->invoke([]);
  }
}