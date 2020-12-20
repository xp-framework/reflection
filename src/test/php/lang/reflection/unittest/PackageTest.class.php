<?php namespace lang\reflection\unittest;

use lang\IllegalArgumentException;
use lang\reflection\Package;
use unittest\{Assert, Test};

class PackageTest {

  #[Test, Values(['lang.reflection', 'lang\reflection', 'lang.reflection.'])]
  public function name($arg) {
    Assert::equals('lang.reflection', (new Package($arg))->name());
  }

  #[Test]
  public function create_via_components() {
    Assert::equals('lang.reflection.unittest', (new Package('lang', 'reflection', 'unittest'))->name());
  }

  #[Test]
  public function create_via_namespace_constant() {
    Assert::equals('lang.reflection.unittest', (new Package(__NAMESPACE__))->name());
  }

  #[Test]
  public function parent() {
    Assert::equals(new Package('lang'), (new Package('lang.reflection'))->parent());
  }

  #[Test]
  public function children() {
    Assert::equals(
      [new Package('lang.reflection.unittest')],
      iterator_to_array((new Package('lang.reflection'))->children())
    );
  }

  #[Test]
  public function types() {
    Assert::instance(
      'lang.reflection.Type[]',
      iterator_to_array((new Package('lang.reflection'))->types())
    );
  }

  #[Test]
  public function type_via_short_name() {
    Assert::equals(self::class, (new Package(__NAMESPACE__))->type('PackageTest')->literal());
  }

  #[Test]
  public function type_via_full_name() {
    Assert::equals(self::class, (new Package(__NAMESPACE__))->type(self::class)->literal());
  }

  #[Test, Expect(class: IllegalArgumentException::class, withMessage: 'Given type util.Date is not in package lang')]
  public function type_with_namespace() {
    (new Package('lang'))->type('util.Date');
  }
}