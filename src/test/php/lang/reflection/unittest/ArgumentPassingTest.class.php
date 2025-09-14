<?php namespace lang\reflection\unittest;

use ReflectionFunction;
use lang\Error;
use lang\reflection\Routine;
use test\{Assert, Expect, Test};

class ArgumentPassingTest {

  #[Test]
  public function pass_ordered() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Assert::equals([1, 2], Routine::pass($f, [1, 2]));
  }

  #[Test]
  public function pass_ordered_null() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Assert::equals([null, 2], Routine::pass($f, [null, 2]));
  }

  #[Test, Expect(class: Error::class, message: 'Missing parameter $a')]
  public function missing() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Routine::pass($f, []);
  }

  #[Test, Expect(class: Error::class, message: 'Missing parameter $b')]
  public function missing_ordered() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Routine::pass($f, [1]);
  }

  #[Test]
  public function pass_named() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Assert::equals([1, 2], Routine::pass($f, ['a' => 1, 'b' => 2]));
  }

  #[Test]
  public function pass_named_null() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Assert::equals([null, 2], Routine::pass($f, ['a' => null, 'b' => 2]));
  }

  #[Test]
  public function pass_named_out_of_order() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Assert::equals([1, 2], Routine::pass($f, ['b' => 2, 'a' => 1]));
  }

  #[Test, Expect(class: Error::class, message: 'Missing parameter $b')]
  public function missing_named() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Routine::pass($f, ['a' => 1]);
  }

  #[Test, Expect(class: Error::class, message: 'Unknown named parameter $unknown')]
  public function unknown_named() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Routine::pass($f, ['a' => 1, 'b' => 2, 'unknown' => null]);
  }

  #[Test]
  public function pass_named_and_ordered() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Assert::equals([1, 2], Routine::pass($f, [1, 'b' => 2]));
  }

  #[Test]
  public function pass_too_many() {
    $f= new ReflectionFunction(fn($a, $b) => null);
    Assert::equals([1, 2], Routine::pass($f, [1, 2, 3]));
  }

  #[Test]
  public function pass_optional() {
    $f= new ReflectionFunction(fn($a, $b= 0) => null);
    Assert::equals([1, 2], Routine::pass($f, [1, 2]));
  }

  #[Test]
  public function pass_without_optional() {
    $f= new ReflectionFunction(fn($a, $b= 0) => null);
    Assert::equals([1, 0], Routine::pass($f, [1]));
  }

  #[Test]
  public function pass_variadic() {
    $f= new ReflectionFunction(fn(... $a) => null);
    Assert::equals([1, 2], Routine::pass($f, [1, 2]));
  }

  #[Test]
  public function pass_variadic_after() {
    $f= new ReflectionFunction(fn($a, ... $b) => null);
    Assert::equals([1, 2, 3], Routine::pass($f, [1, 2, 3]));
  }
}