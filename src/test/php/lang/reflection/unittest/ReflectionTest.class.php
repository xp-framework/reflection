<?php namespace lang\reflection\unittest;

use ReflectionClass, ReflectionObject;
use lang\meta\{FromAttributes, FromSyntaxTree};
use lang\reflection\{Invocable, Package};
use lang\{ClassNotFoundException, Reflection, Type};
use test\{Assert, Expect, Test, Values};

class ReflectionTest {

  /** @return iterable */
  private function invocables() {
    yield ['strlen', 'global function'];
    yield [fn() => true, 'short closure'];
    yield [function() { }, 'closure'];
  }

  /** @return iterable */
  private function types() {
    yield [self::class, 'class literal'];
    yield [nameof($this), 'class name'];
    yield [$this, 'instance'];
    yield [Type::forName(self::class), 'type'];
    yield [Reflection::of(self::class), 'reflection'];
    yield [new ReflectionClass(self::class), 'reflection class'];
    yield [new ReflectionObject($this), 'reflection object'];
  }

  /** @return iterable */
  private function packages() {
    yield [__NAMESPACE__, 'namespace literal'];
    yield ['lang.reflection.unittest', 'namespace name'];
    yield [self::class, 'class literal'];
    yield [nameof($this), 'class name'];
    yield [$this, 'instance'];
    yield [Type::forName(self::class), 'type'];
    yield [Reflection::of(self::class), 'reflection'];
    yield [new ReflectionClass($this), 'reflection class'];
    yield [new ReflectionObject($this), 'reflection object'];
  }

  #[Test, Values(from: 'types')]
  public function of($argument) {
    Assert::equals(nameof($this), Reflection::of($argument)->name());
  }

  #[Test, Values(from: 'invocables')]
  public function invocable($argument) {
    Assert::instance(Invocable::class, Reflection::invocable($argument));
  }

  #[Test, Values(from: 'types')]
  public function type($argument) {
    Assert::equals(nameof($this), Reflection::type($argument)->name());
  }

  #[Test, Values(from: 'packages')]
  public function package($argument) {
    Assert::equals(new Package('lang.reflection.unittest'), Reflection::package($argument));
  }

  #[Test]
  public function of_package_name() {
    Assert::instance(Package::class, Reflection::of('lang.reflection.unittest'));
  }

  #[Test, Values([70000, 70100, 70200, 70300, 70400])]
  public function parser_for_php7($versionId) {
    Assert::instance(FromSyntaxTree::class, Reflection::annotations($versionId));
  }

  #[Test, Values([80000, 80100, 80200])]
  public function parser_for_php8($versionId) {
    Assert::instance(FromAttributes::class, Reflection::annotations($versionId));
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