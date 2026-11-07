<?php namespace lang\reflection\unittest;

use ReflectionFunction;
use lang\reflection\{Closure, CannotInvoke};
use lang\{Type, Primitive};
use test\{Assert, Expect, Test, Values};

class ClosureTest {

  /** @return iterable */
  private function callables() {
    yield ['strtoupper'];
    yield [fn() => true];
    yield [function() { return true; }];
    yield [new ReflectionFunction('strtoupper')];
  }

  #[Test, Values(from: 'callables')]
  public function can_create($callable) {
    new Closure($callable);
  }

  #[Test]
  public function no_comment() {
    Assert::null((new Closure(fn() => true))->comment());
  }

  #[Test]
  public function with_comment() {
    Assert::equals('Test', (new Closure(/** Test */ fn() => true))->comment());
  }

  #[Test]
  public function empty_parameters() {
    $parameters= (new Closure(fn() => true))->parameters();
    Assert::equals(0, $parameters->size());
  }

  #[Test]
  public function parameters() {
    $parameters= (new Closure(fn($name) => true))->parameters();
    Assert::equals(1, $parameters->size());
  }

  #[Test, Values([0, 'name'])]
  public function parameter($arg) {
    $parameter= (new Closure(fn($name) => true))->parameter($arg);
    Assert::equals('name', $parameter->name());
    Assert::false($parameter->optional());
  }

  #[Test]
  public function optional_parameter() {
    $parameters= (new Closure(fn($name= null) => true))->parameters();
    Assert::equals(0, $parameters->size($required= true));
    Assert::equals(1, $parameters->size($required= false));
  }

  #[Test]
  public function without_return_type() {
    $constraint= (new Closure(fn($name) => true))->returns();
    Assert::false($constraint->present());
    Assert::equals(Type::$VAR, $constraint->type());
  }

  #[Test]
  public function return_type() {
    $constraint= (new Closure(fn($name): bool => true))->returns();
    Assert::true($constraint->present());
    Assert::equals(Primitive::$BOOL, $constraint->type());
  }

  #[Test]
  public function invoke() {
    $closure= (new Closure(fn($name) => strtoupper($name)));
    Assert::equals('TEST', $closure->invoke(['test']));
  }

  #[Test]
  public function invoke_named() {
    $closure= (new Closure(fn($name) => strtoupper($name)));
    Assert::equals('TEST', $closure->invoke(['name' => 'test']));
  }

  #[Test, Expect(CannotInvoke::class)]
  public function invoke_missing_argument() {
    $closure= (new Closure(fn($name) => strtoupper($name)));
    $closure->invoke([]);
  }
}