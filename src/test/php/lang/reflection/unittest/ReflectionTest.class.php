<?php namespace lang\reflection\unittest;

use lang\reflection\{Annotations, Constants, Kind, Methods, Properties};
use lang\{ElementNotFoundException, Reflection, Type};
use unittest\{Assert, Test};

#[Annotated]
class ReflectionTest {
  const CONSTANT = 1;
  private $property;

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

  #[Test]
  public function name() {
    Assert::equals(nameof($this), Reflection::of(self::class)->name());
  }

  #[Test]
  public function literal() {
    Assert::equals(self::class, Reflection::of(self::class)->literal());
  }

  #[Test]
  public function class() {
    Assert::equals(Type::forName(self::class), Reflection::of(self::class)->class());
  }

  #[Test]
  public function kind() {
    Assert::equals(Kind::$CLASS, Reflection::of(self::class)->kind());
  }

  #[Test]
  public function is_self() {
    Assert::true(Reflection::of(self::class)->is(self::class));
  }

  #[Test]
  public function classLoader() {
    Assert::equals(Type::forName(self::class)->getClassLoader(), Reflection::of(self::class)->classLoader());
  }

  #[Test]
  public function annotation() {
    Assert::equals('annotated', Reflection::of($this)->annotation(Annotated::class)->name());
  }

  #[Test]
  public function non_existant_annotation() {
    Assert::null(Reflection::of($this)->annotation('does-not-exist'));
  }

  #[Test]
  public function constant() {
    Assert::equals('CONSTANT', Reflection::of($this)->constant('CONSTANT')->name());
  }

  #[Test]
  public function non_existant_constant() {
    Assert::null(Reflection::of($this)->constant('DOES-NOT-EXIST'));
  }

  #[Test]
  public function property() {
    Assert::equals('property', Reflection::of($this)->property('property')->name());
  }

  #[Test]
  public function non_existant_property() {
    Assert::null(Reflection::of($this)->property('does-not-exist'));
  }

  #[Test]
  public function method() {
    Assert::equals('method', Reflection::of($this)->method('method')->name());
  }

  #[Test]
  public function non_existant_method() {
    Assert::null(Reflection::of($this)->method('does-not-exist'));
  }

  #[Test]
  public function annotations() {
    Assert::instance(Annotations::class, Reflection::of($this)->annotations());
  }

  #[Test]
  public function constants() {
    Assert::instance(Constants::class, Reflection::of($this)->constants());
  }

  #[Test]
  public function properties() {
    Assert::instance(Properties::class, Reflection::of($this)->properties());
  }

  #[Test]
  public function methods() {
    Assert::instance(Methods::class, Reflection::of($this)->methods());
  }
}