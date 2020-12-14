<?php namespace lang\reflection\unittest;

use lang\annotations\{FromAttributes, FromSyntaxTree};
use lang\{Reflection, Type};
use unittest\{Assert, Test};

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
  public function of_instance() {
    Assert::equals(nameof($this), Reflection::of($this)->name());
  }

  #[Test]
  public function parser_for_php7() {
    Assert::instance(FromSyntaxTree::class, Reflection::parse(70000));
  }

  #[Test]
  public function parser_for_php8() {
    Assert::instance(FromAttributes::class, Reflection::parse(80000));
  }
}