<?php namespace lang\reflection\unittest;

use lang\reflection\Package;
use unittest\{Assert, Test};

class PackageTest {

  #[Test]
  public function name() {
    Assert::equals('lang.reflection', (new Package('lang.reflection'))->name());
  }

  #[Test]
  public function create_via_components() {
    Assert::equals('lang.reflection.unittest', (new Package(['lang', 'reflection', 'unittest']))->name());
  }

  #[Test]
  public function create_via_namespace_constant() {
    Assert::equals('lang.reflection.unittest', (new Package(__NAMESPACE__))->name());
  }

  #[Test]
  public function child_packages() {
    Assert::equals(
      [new Package('lang.reflection.unittest')],
      iterator_to_array((new Package('lang.reflection'))->packages())
    );
  }

  #[Test]
  public function types() {
    Assert::instance(
      'lang.reflection.Type[]',
      iterator_to_array((new Package('lang.reflection'))->types())
    );
  }
}