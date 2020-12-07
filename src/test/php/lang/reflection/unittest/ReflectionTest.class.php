<?php namespace lang\reflection\unittest;

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
  public function of_Reflection() {
    Assert::equals(nameof($this), Reflection::of(new \ReflectionClass(self::class))->name());
  }

  #[Test]
  public function of_instance() {
    Assert::equals(nameof($this), Reflection::of($this)->name());
  }
}