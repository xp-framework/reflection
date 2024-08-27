<?php namespace lang\reflection\unittest;

use ReflectionProperty;
use lang\reflection\{AccessingFailed, CannotAccess, Constraint, Modifiers};
use lang\{Primitive, Type, TypeIntersection, TypeUnion, XPClass, IllegalArgumentException};
use test\verify\{Condition, Runtime};
use test\{Action, Assert, Expect, Test, Values};

class PropertiesTest {
  use TypeDefinition;

  private static $ASYMMETRIC;

  static function __static() {
    self::$ASYMMETRIC= method_exists(ReflectionProperty::class, 'isPrivateSet');
  }

  #[Test]
  public function name() {
    Assert::equals('fixture', $this->declare('{ public $fixture; }')->property('fixture')->name());
  }

  #[Test]
  public function compoundName() {
    $t= $this->declare('{ public $fixture; }');
    Assert::equals($t->name().'::$fixture', $t->property('fixture')->compoundName());
  }

  #[Test]
  public function modifiers() {
    Assert::equals(
      new Modifiers('private'),
      $this->declare('{ private $fixture; }')->property('fixture')->modifiers()
    );
  }

  #[Test, Values(['public', 'protected', 'private'])]
  public function get_modifiers($modifier) {
    Assert::equals(
      new Modifiers($modifier),
      $this->declare('{ '.$modifier.' $fixture; }')->property('fixture')->modifiers('get')
    );
  }

  #[Test, Values(['public', 'protected', 'private'])]
  public function set_modifiers($modifier) {
    Assert::equals(
      new Modifiers($modifier),
      $this->declare('{ '.$modifier.' $fixture; }')->property('fixture')->modifiers('set')
    );
  }

  #[Test]
  public function get_modifiers_erases_static() {
    Assert::equals(
      new Modifiers('public'),
      $this->declare('{ public static $fixture; }')->property('fixture')->modifiers('get')
    );
  }

  #[Test]
  public function set_modifiers_erases_static() {
    Assert::equals(
      new Modifiers('public'),
      $this->declare('{ public static $fixture; }')->property('fixture')->modifiers('set')
    );
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function modifiers_unknown_hook() {
    $this->declare('{ private $fixture; }')->property('fixture')->modifiers('@unknown');
  }

  #[Test]
  public function no_comment() {
    Assert::null($this->declare('{ private $fixture; }')->property('fixture')->comment());
  }

  #[Test]
  public function with_comment() {
    Assert::equals('Test', $this->declare('{ /** Test */ private $fixture; }')->property('fixture')->comment());
  }

  #[Test]
  public function declaredIn() {
    $t= $this->declare('{ private $fixture; }');
    Assert::equals($t, $t->property('fixture')->declaredIn());
  }

  #[Test]
  public function named() {
    $type= $this->declare('{ public $fixture; }');
    Assert::equals($type->property('fixture'), $type->properties()->named('fixture'));
  }

  #[Test]
  public function get_instance() {
    $type= $this->declare('{ public $fixture = "Test"; }');
    Assert::equals('Test', $type->properties()->named('fixture')->get($type->newInstance()));
  }

  #[Test]
  public function get_static() {
    $type= $this->declare('{ public static $fixture = "Test"; }');
    Assert::equals('Test', $type->properties()->named('fixture')->get(null));
  }

  #[Test]
  public function set_instance() {
    $type= $this->declare('{ public $fixture = "Test"; }');
    $instance= $type->newInstance();
    $type->properties()->named('fixture')->set($instance, 'Modified');

    Assert::equals('Modified', $instance->fixture);
  }

  #[Test]
  public function set_static() {
    $type= $this->declare('{ public static $fixture = "Test"; }');
    $class= $type->literal();
    $type->properties()->named('fixture')->set(null, 'Modified');

    Assert::equals('Modified', $class::$fixture);
  }

  #[Test, Expect(AccessingFailed::class)]
  public function type_mismatch() {
    $type= $this->declare('{ private static array $fixture; }');
    $type->property('fixture')->set(null, 1);
  }

  #[Test]
  public function private_instance_roundtrip() {
    $type= $this->declare('{ private $fixture = "Test"; }');
    $instance= $type->newInstance();
    $property= $type->properties()->named('fixture');
    $property->set($instance, 'Modified');

    Assert::equals('Modified', $property->get($instance));
  }

  #[Test]
  public function non_existant() {
    $type= $this->declare('{ }');
    Assert::null($type->properties()->named('fixture'));
  }

  #[Test]
  public function without_properties() {
    Assert::equals([], iterator_to_array($this->declare('{ }')->properties()));
  }

  #[Test]
  public function properties() {
    $type= $this->declare('{ public $one, $two; }');
    Assert::equals(
      ['one' => $type->property('one'), 'two' => $type->property('two')],
      iterator_to_array($type->properties())
    );
  }

  #[Test, Values(['/** @var string */', '/** @type string */'])]
  public function type_from_apidoc($comment) {
    $type= $this->declare('{ '.$comment.' public $fixture; }');
    Assert::equals(new Constraint(Primitive::$STRING, false), $type->property('fixture')->constraint());
  }

  #[Test]
  public function type_from_declaration() {
    $type= $this->declare('{ public string $fixture; }');
    Assert::equals(
      new Constraint(Primitive::$STRING, true),
      $type->property('fixture')->constraint()
    );
  }

  #[Test]
  public function type_from_array_declaration() {
    $type= $this->declare('{ public array $fixture; }');
    Assert::equals(
      new Constraint(Type::$ARRAY, true),
      $type->property('fixture')->constraint()
    );
  }

  #[Test]
  public function type_from_self_declaration() {
    $type= $this->declare('{ public self $fixture; }');
    Assert::equals(
      new Constraint($type->class(), true),
      $type->property('fixture')->constraint()
    );
  }

  #[Test, Runtime(php: '>=8.0')]
  public function type_from_union_declaration() {
    $type= $this->declare('{ public string|int $fixture; }');
    Assert::equals(
      new Constraint(new TypeUnion([Primitive::$STRING, Primitive::$INT]), true),
      $type->property('fixture')->constraint()
    );
  }

  #[Test, Runtime(php: '>=8.1')]
  public function type_from_intersection_declaration() {
    $type= $this->declare('{ public \Countable&\Traversable $fixture; }');
    Assert::equals(
      new Constraint(new TypeIntersection([new XPClass('Countable'), new XPClass('Traversable')]), true),
      $type->property('fixture')->constraint()
    );
  }

  #[Test, Runtime(php: '>=8.2'), Values(['true', 'false'])]
  public function type_from_boolean_types($name) {
    $type= $this->declare('{ public '.$name.' $fixture; }');
    Assert::equals(
      new Constraint(Primitive::$BOOL, true),
      $type->property('fixture')->constraint()
    );
  }

  #[Test]
  public function no_type() {
    $type= $this->declare('{ public $fixture; }');
    Assert::equals(Type::$VAR, $type->property('fixture')->constraint()->type());
  }

  #[Test]
  public function string_representation_without_type() {
    $t= $this->declare('{ public $fixture; }');
    Assert::equals(
      'public var $fixture',
      $t->property('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_with_type_from_apidoc() {
    $t= $this->declare('{ /** @type string */ public $fixture; }');
    Assert::equals(
      'public string $fixture',
      $t->property('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_with_type_declaration() {
    $t= $this->declare('{ public string $fixture; }');
    Assert::equals(
      'public string $fixture',
      $t->property('fixture')->toString()
    );
  }

  #[Test, Runtime(php: '>=8.0')]
  public function string_representation_with_union_type_declaration() {
    $t= $this->declare('{ public string|int $fixture; }');
    Assert::equals(
      'public string|int $fixture',
      $t->property('fixture')->toString()
    );
  }

  #[Test]
  public function set_accessing_failed_exceptions_target_member() {
    $t= $this->declare('{ public static array $fixture; }');
    try {
      $t->property('fixture')->set(null, 1);
      throw new AssertionFailedError('No exception was raised');
    } catch (AccessingFailed $expected) {
      Assert::equals($t->property('fixture'), $expected->target());
    }
  }

  #[Test, Condition(assert: 'self::$ASYMMETRIC'), Values(['public protected(set)', 'public private(set)', 'protected private(set)'])]
  public function asymmetric_visibility($modifiers) {
    $t= $this->declare('{ '.$modifiers.' int $fixture; }');
    Assert::equals(
      $modifiers.' int $fixture',
      $t->property('fixture')->toString()
    );
  }

  #[Test, Condition(assert: 'self::$ASYMMETRIC'), Values(['public', 'protected', 'private'])]
  public function set_implicit_when_same_as_get($modifier) {
    $t= $this->declare('{ '.$modifier.' '.$modifier.'(set) int $fixture; }');
    Assert::equals(
      $modifier.' int $fixture',
      $t->property('fixture')->toString()
    );
  }

  #[Test, Condition(assert: 'self::$ASYMMETRIC'), Values(['public', 'protected', 'private'])]
  public function asymmetric_get($modifier) {
    Assert::equals(
      new Modifiers($modifier),
      $this->declare('{ '.$modifier.' private(set) int $fixture; }')->property('fixture')->modifiers('get')
    );
  }

  #[Test, Condition(assert: 'self::$ASYMMETRIC'), Values(['public', 'protected', 'private'])]
  public function asymmetric_set($modifier) {
    Assert::equals(
      new Modifiers($modifier),
      $this->declare('{ public '.$modifier.'(set) int $fixture; }')->property('fixture')->modifiers('set')
    );
  }
}