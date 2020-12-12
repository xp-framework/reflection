<?php namespace lang\reflection\unittest;

use lang\reflection\{Kind, Modifiers, Annotations, Constants, Properties, Methods};
use lang\{ElementNotFoundException, Reflection, Enum, XPClass, ClassLoader};
use unittest\{Assert, Test};

class TypeTest {
  private $fixture;

  /**
   * Declares a type and returns its reflection instance
   *
   * @param  string $suffix
   * @param  [:var] $definition
   * @param  string $body
   * @return lang.reflection.Type
   */
  private function declare($suffix, $definition, $body= '{}') {
    return Reflection::of(ClassLoader::defineType(
      '#[Annotated] '.self::class.$suffix,
      array_merge(['kind' => 'class', 'extends' => null, 'implements' => [], 'use' => []], $definition),
      $body
    ));
  }

  #[Before]
  public function fixture() {
    $this->fixture= $this->declare('Fixture', []);
  }

  #[Test]
  public function name() {
    Assert::equals(nameof($this).'Fixture', $this->fixture->name());
  }

  #[Test]
  public function literal() {
    Assert::equals(self::class.'Fixture', $this->fixture->literal());
  }

  #[Test]
  public function default_modifiers() {
    $t= $this->declare('M_D', []);
    Assert::equals(new Modifiers('public'), $t->modifiers());
  }

  #[Test]
  public function abstract_modifiers() {
    $t= $this->declare('M_A', ['modifiers' => 'abstract']);
    Assert::equals(new Modifiers('public abstract'), $t->modifiers());
  }

  #[Test]
  public function final_modifiers() {
    $t= $this->declare('M_F', ['modifiers' => 'final']);
    Assert::equals(new Modifiers('public final'), $t->modifiers());
  }

  #[Test]
  public function class_kind() {
    $t= $this->declare('K_C', ['kind' => 'class']);
    Assert::equals(Kind::$CLASS, $t->kind());
  }

  #[Test]
  public function interface_kind() {
    $t= $this->declare('K_I', ['kind' => 'interface']);
    Assert::equals(Kind::$INTERFACE, $t->kind());
  }

  #[Test]
  public function trait_kind() {
    $t= $this->declare('K_T', ['kind' => 'trait']);
    Assert::equals(Kind::$TRAIT, $t->kind());
  }

  #[Test]
  public function enum_kind() {
    $t= $this->declare('K_E', ['kind' => 'class', 'extends' => [Enum::class]], '{ public static $M; }');
    Assert::equals(Kind::$ENUM, $t->kind());
  }

  #[Test]
  public function no_parent() {
    Assert::null($this->fixture->parent());
  }

  #[Test]
  public function enum_parent() {
    $t= $this->declare('K_E', ['kind' => 'class', 'extends' => [Enum::class]], '{ public static $M; }');
    Assert::equals(Reflection::of(Enum::class), $t->parent());
  }

  #[Test]
  public function class() {
    Assert::equals(new XPClass($this->fixture->literal()), $this->fixture->class());
  }

  #[Test]
  public function classLoader() {
    Assert::equals((new XPClass($this->fixture->literal()))->getClassLoader(), $this->fixture->classLoader());
  }

  #[Test]
  public function no_classLoader_for_internal_classes() {
    Assert::null(Reflection::of(\Throwable::class)->classLoader());
  }

  #[Test]
  public function is_reflection_type() {
    Assert::true($this->fixture->is($this->fixture));
  }

  #[Test]
  public function is_literal() {
    Assert::true($this->fixture->is($this->fixture->literal()));
  }

  #[Test]
  public function is_xpclass() {
    Assert::true($this->fixture->is(new XPClass($this->fixture->literal())));
  }

  #[Test]
  public function newInstance_isInstance() {
    Assert::true($this->fixture->isInstance($this->fixture->newInstance()));
  }

  #[Test]
  public function annotation() {
    Assert::equals('annotated', $this->fixture->annotation(Annotated::class)->name());
  }

  #[Test]
  public function non_existant_annotation() {
    Assert::null($this->fixture->annotation('does-not-exist'));
  }

  #[Test]
  public function constant() {
    $t= $this->declare('M_C', [], '{ const CONSTANT = 1; }');
    Assert::equals('CONSTANT', $t->constant('CONSTANT')->name());
  }

  #[Test]
  public function non_existant_constant() {
    Assert::null($this->fixture->constant('DOES-NOT-EXIST'));
  }

  #[Test]
  public function property() {
    $t= $this->declare('M_P', [], '{ public $property; }');
    Assert::equals('property', $t->property('property')->name());
  }

  #[Test]
  public function non_existant_property() {
    Assert::null($this->fixture->property('does-not-exist'));
  }

  #[Test]
  public function method() {
    $t= $this->declare('M_M', [], '{ public function method() { } }');
    Assert::equals('method', $t->method('method')->name());
  }

  #[Test]
  public function non_existant_method() {
    Assert::null($this->fixture->method('does-not-exist'));
  }

  #[Test]
  public function annotations() {
    Assert::instance(Annotations::class, $this->fixture->annotations());
  }

  #[Test]
  public function constants() {
    Assert::instance(Constants::class, $this->fixture->constants());
  }

  #[Test]
  public function properties() {
    Assert::instance(Properties::class, $this->fixture->properties());
  }

  #[Test]
  public function methods() {
    Assert::instance(Methods::class, $this->fixture->methods());
  }

  #[Test]
  public function constructor() {
    $t= $this->declare('CTOR', [], '{ public function __construct() { } }');
    Assert::equals('__construct', $t->constructor()->name());
  }

  #[Test]
  public function no_constructor() {
    Assert::null($this->fixture->constructor());
  }

  #[Test]
  public function instantiable_without_constructor() {
    Assert::true($this->fixture->instantiable());
  }

  #[Test]
  public function instantiable_with_constructor() {
    $t= $this->declare('CTOR', [], '{ public function __construct() { } }');
    Assert::true($t->instantiable());
  }

  #[Test]
  public function interfaces_are_not_instantiable() {
    $t= $this->declare('K_I', ['kind' => 'interface']);
    Assert::false($t->instantiable());
  }

  #[Test]
  public function abstract_class_are_not_instantiable() {
    $t= $this->declare('K_A', ['kind' => 'class', 'modifiers' => 'abstract']);
    Assert::false($t->instantiable());
  }

  #[Test]
  public function traits_are_not_instantiable() {
    $t= $this->declare('K_T', ['kind' => 'trait']);
    Assert::false($t->instantiable());
  }

  #[Test]
  public function string_cast_returns_type_literal() {
    Assert::equals($this->fixture->literal(), (string)$this->fixture);
  }
}