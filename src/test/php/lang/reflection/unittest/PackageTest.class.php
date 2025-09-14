<?php namespace lang\reflection\unittest;

use lang\reflection\Package;
use lang\reflection\unittest\fixture\{Instruction, CreateInstruction};
use lang\{Reflection, IllegalArgumentException};
use test\{Assert, Expect, Test, Values};

class PackageTest {
  const FIXTURES= 'lang.reflection.unittest.fixture';

  /** @return iterable */
  private function instructions() {
    yield [Instruction::class, 'class literal'];
    yield ['lang.reflection.unittest.fixture.Instruction', 'class name'];
    yield [Reflection::type(Instruction::class), 'type'];
  }

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
    Assert::equals(Reflection::type(self::class), (new Package(__NAMESPACE__))->type('PackageTest'));
  }

  #[Test]
  public function type_via_full_name() {
    Assert::equals(Reflection::type(self::class), (new Package(__NAMESPACE__))->type(self::class));
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: 'Given type util.Date is not in package lang')]
  public function type_with_namespace() {
    (new Package('lang'))->type('util.Date');
  }

  #[Test, Values(from: 'instructions')]
  public function implementation_by_name($type) {
    Assert::equals(
      Reflection::type(CreateInstruction::class),
      (new Package(self::FIXTURES))->implementation($type, 'CreateInstruction')
    );
  }

  #[Test, Values(from: 'instructions')]
  public function implementation_by_class($type) {
    Assert::equals(
      Reflection::type(CreateInstruction::class),
      (new Package(self::FIXTURES))->implementation($type, CreateInstruction::class)
    );
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: '/Given type util.Date is not in package .+/')]
  public function implementation_not_in_package() {
    (new Package(self::FIXTURES))->implementation(Instruction::class, 'util.Date');
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: '/Given type .+ is not an implementation of lang.Runnable/')]
  public function not_an_implementation() {
    (new Package(self::FIXTURES))->implementation('lang.Runnable', 'CreateInstruction');
  }
}