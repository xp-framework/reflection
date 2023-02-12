<?php namespace lang\reflection\unittest;

use lang\reflection\{Annotations, Constants, Kind, Methods, Modifiers, Package, Properties};
use lang\{ClassLoader, ElementNotFoundException, Enum, Reflection, Runnable, XPClass};
use test\verify\{Condition, Runtime};
use test\{Action, Assert, Before, Test};

class TypeTest {
  private $fixture;
  private static $ENUMS;

  static function __static() {
    self::$ENUMS= class_exists(\ReflectionEnum::class, false);
  }

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
  public function package() {
    Assert::equals(new Package(__NAMESPACE__), $this->fixture->package());
  }

  #[Test]
  public function global_namespace() {
    Assert::null(Reflection::of(\Throwable::class)->package());
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
  public function native_modifiers() {
    $t= Reflection::of(\ReflectionClass::class);
    Assert::equals(new Modifiers('public native'), $t->modifiers());
  }

  #[Test, Runtime(php: '>=8.2')]
  public function readonly_modifiers() {
    $t= $this->declare('M_R', ['modifiers' => 'readonly']);
    Assert::equals(new Modifiers('public readonly'), $t->modifiers());
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
  public function enum_kind_for_xpenums() {
    $t= $this->declare('K_XE', ['kind' => 'class', 'extends' => [Enum::class]], '{ public static $M; }');
    Assert::equals(Kind::$ENUM, $t->kind());
  }

  #[Test, Condition(assert: '!self::$ENUMS')]
  public function enum_kind_for_enum_lookalikes() {
    $t= $this->declare('K_LE', ['kind' => 'class', 'implements' => [\UnitEnum::class]], '{ public static $M; }');
    Assert::equals(Kind::$ENUM, $t->kind());
  }

  #[Test, Condition(assert: 'self::$ENUMS')]
  public function enum_kind_for_native_enums() {
    $t= $this->declare('K_NE', ['kind' => 'enum'], '{ case M; }');
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
  public function class_without_interfaces() {
    $t= $this->declare('C_NI', ['kind' => 'class', 'implements' => []], '{ }');
    Assert::equals([], $t->interfaces());
  }

  #[Test]
  public function class_interfaces() {
    $t= $this->declare('C_WI', ['kind' => 'class', 'implements' => [Runnable::class]], '{ public function run() { } }');
    Assert::equals([Reflection::of(Runnable::class)], $t->interfaces());
  }

  #[Test]
  public function interface_parents() {
    $t= $this->declare('I_WP', ['kind' => 'interface', 'extends' => [Runnable::class]], '{ }');
    Assert::equals([Reflection::of(Runnable::class)], $t->interfaces());
  }

  #[Test]
  public function class_without_traits() {
    $t= $this->declare('C_NT', ['kind' => 'class', 'use' => []], '{ }');
    Assert::equals([], $t->traits());
  }

  #[Test]
  public function class_traits() {
    $t= $this->declare('C_WT', ['kind' => 'class', 'use' => [TypeDefinition::class]], '{ }');
    Assert::equals([Reflection::of(TypeDefinition::class)], $t->traits());
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
  public function no_classLoader_for_native_classes() {
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

  #[Test, Condition(assert: 'fn() => self::$ENUMS')]
  public function enum_annotation() {
   $t= $this->declare('A_E', ['kind' => 'enum'], '{ case M; }');
    Assert::equals('annotated', $t->annotation(Annotated::class)->name());
  }

  #[Test, Condition(assert: 'fn() => self::$ENUMS')]
  public function enum_case_annotation() {
   $t= $this->declare('A_C', ['kind' => 'enum'], '{ #[Annotated] case M; }');
    Assert::equals('annotated', $t->constant('M')->annotation(Annotated::class)->name());
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
  public function instantiable_with_private_constructor() {
    $t= $this->declare('P_CTOR', [], '{ private function __construct() { } }');
    Assert::true($t->instantiable());
  }

  #[Test]
  public function not_instantiable_directly_with_private_constructor() {
    $t= $this->declare('P_CTOR', [], '{ private function __construct() { } }');
    Assert::false($t->instantiable(true));
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
  public function without_comment() {
    $t= $this->declare('N_C', ['kind' => 'class']);
    Assert::null($t->comment());
  }

  #[Test]
  public function with_comment() {
    Assert::equals('Used by TypeTest', Reflection::of(WithComment::class)->comment());
  }

  #[Test]
  public function string_cast_returns_type_literal() {
    Assert::equals($this->fixture->literal(), (string)$this->fixture);
  }
}