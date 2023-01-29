<?php namespace lang\reflection\unittest;

use ReflectionClass, ReflectionObject;
use lang\reflection\Package;
use lang\{Reflection, Type, ClassNotFoundException};
use unittest\{Assert, Test, Values, Expect};

class ReflectionTest {

  /** @return iterable */
  private function arguments() {
    yield [self::class, 'class literal'];
    yield [nameof($this), 'class name'];
    yield [$this, 'instance'];
    yield [Type::forName(self::class), 'type'];
    yield [Reflection::of(self::class), 'reflection'];
    yield [new ReflectionClass(self::class), 'reflection class'];
    yield [new ReflectionObject($this), 'reflection object'];
  }

  #[Test, Values('arguments')]
  public function of($argument) {
    Assert::equals(nameof($this), Reflection::of($argument)->name());
  }

  #[Test, Values('arguments')]
  public function type($argument) {
    Assert::equals(nameof($this), Reflection::type($argument)->name());
  }

  #[Test]
  public function of_package_name() {
    Assert::instance(Package::class, Reflection::of('lang.reflection.unittest'));
  }

  #[Test, Expect(ClassNotFoundException::class)]
  public function of_non_existant() {
    Reflection::of('non.existant.Type');
  }

  #[Test, Expect(ClassNotFoundException::class)]
  public function type_existant() {
    Reflection::type('non.existant.Type');
  }

  #[Test, Expect(ClassNotFoundException::class)]
  public function type_given_package_name() {
    Assert::instance(Package::class, Reflection::type('lang.reflection.unittest'));
  }
}