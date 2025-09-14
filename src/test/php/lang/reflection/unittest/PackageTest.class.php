<?php namespace lang\reflection\unittest;

use lang\IllegalArgumentException;
use lang\reflection\Package;
use test\{Assert, Expect, Test, Values};

class PackageTest {

  #[Test, Values(['lang.reflection', 'lang\reflection', 'lang.reflection.'])]
  public function name($arg) {
    Assert::equals('lang.reflection', (new Package($arg))->name());
  }

  #[Test]
  public function global() {
    Assert::true((new Package())->global());
  }

  #[Test, Values(['lang', 'lang.reflection', 'lang.reflect.unittest'])]
  public function leveled($package) {
    Assert::false((new Package($package))->global());
  }

  #[Test]
  public function global_name() {
    Assert::equals('', (new Package())->name());
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
  public function parent_of_toplevel() {
    Assert::equals(new Package(), (new Package('lang'))->parent());
  }

  #[Test]
  public function global_package_has_no_parent() {
    Assert::null((new Package())->parent());
  }

  #[Test]
  public function non_existant_child() {
    Assert::null((new Package())->child('lang.non_existant_package'));
  }

  #[Test, Values(['reflection.unittest', 'reflection\\unittest'])]
  public function resolve_child($reference) {
    Assert::equals(
      new Package('lang.reflection.unittest'),
      (new Package('lang'))->child($reference)
    );
  }

  #[Test, Values(['lang.reflection.unittest', 'lang\\reflection\\unittest'])]
  public function toplevel_child($reference) {
    Assert::equals(
      new Package('lang.reflection.unittest'),
      (new Package())->child($reference)
    );
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

  #[Test, Expect(class: IllegalArgumentException::class, message: 'Given type util.Date is not in package lang')]
  public function type_with_namespace() {
    (new Package('lang'))->type('util.Date');
  }
}