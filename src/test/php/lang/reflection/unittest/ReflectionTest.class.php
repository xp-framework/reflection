<?php namespace lang\reflection\unittest;

use lang\meta\{FromAttributes, FromSyntaxTree};
use lang\{Reflection, Type, ClassNotFoundException};
use unittest\{Assert, Test, Values, Expect};

class ReflectionTest {

  #[Test]
  public function of_class() {
    Assert::equals(nameof($this), Reflection::of(self::class)->name());
  }

  #[Test]
  public function of_name() {
    Assert::equals(nameof($this), Reflection::of(nameof($this))->name());
  }

  #[Test]
  public function of_type() {
    Assert::equals(nameof($this), Reflection::of(Type::forName(self::class))->name());
  }

  #[Test]
  public function of_reflection() {
    $t= Reflection::of(self::class);
    Assert::equals($t, Reflection::of($t));
  }

  #[Test]
  public function of_reflection_class() {
    Assert::equals(nameof($this), Reflection::of(new \ReflectionClass(self::class))->name());
  }

  #[Test]
  public function of_reflection_object() {
    Assert::equals(nameof($this), Reflection::of(new \ReflectionObject($this))->name());
  }

  #[Test]
  public function of_instance() {
    Assert::equals(nameof($this), Reflection::of($this)->name());
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
}