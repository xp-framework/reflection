<?php namespace lang\reflection\unittest;

use lang\Primitive;
use lang\reflection\{Modifiers, CannotAccess, AccessingFailed, Constraint};
use unittest\actions\RuntimeVersion;
use unittest\{Assert, Action, Expect, Test, AssertionFailedError};

class PropertiesTest {
  use TypeDefinition;

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

  #[Test, Expect(CannotAccess::class)]
  public function cannot_read_private_by_default() {
    $type= $this->declare('{ private static $fixture = "Test"; }');
    $type->property('fixture')->get(null);
  }

  #[Test, Expect(CannotAccess::class)]
  public function cannot_write_private_by_default() {
    $type= $this->declare('{ private static $fixture = "Test"; }');
    $type->property('fixture')->set(null, 'Modified');
  }

  #[Test, Expect(CannotAccess::class)]
  public function cannot_read_private_with_incorrect_context() {
    $type= $this->declare('{ private static $fixture = "Test"; }');
    $type->property('fixture')->get(null, typeof($this));
  }

  #[Test, Expect(CannotAccess::class)]
  public function cannot_write_private_with_incorrect_context() {
    $type= $this->declare('{ private static $fixture = "Test"; }');
    $type->property('fixture')->set(null, 'Modified', typeof($this));
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=7.4")'), Expect(AccessingFailed::class)]
  public function type_mismatch() {
    $type= $this->declare('{ private static array $fixture; }');
    $type->property('fixture')->set(null, 1, $type);
  }

  #[Test]
  public function private_instance_roundtrip() {
    $type= $this->declare('{ private $fixture = "Test"; }');
    $instance= $type->newInstance();
    $property= $type->properties()->named('fixture');
    $property->set($instance, 'Modified', $type);

    Assert::equals('Modified', $property->get($instance, $type));
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

  #[Test]
  public function type_from_apidoc() {
    $type= $this->declare('{ /** @type string */ public $fixture; }');
    Assert::equals(new Constraint(Primitive::$STRING, false), $type->property('fixture')->constraint());
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=7.4")')]
  public function type_from_declaration() {
    $type= $this->declare('{ public string $fixture; }');
    Assert::equals(new Constraint(Primitive::$STRING, true), $type->property('fixture')->constraint());
  }

  #[Test]
  public function string_representation() {
    $t= $this->declare('{ public $fixture; }');
    Assert::equals(
      'public $fixture',
      $t->property('fixture')->toString()
    );
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=7.4")')]
  public function accessing_failed_target() {
    $t= $this->declare('{ public static array $fixture; }');
    try {
      $t->property('fixture')->set(null, 1);
      throw new AssertionFailedError('No exception was raised');
    } catch (AccessingFailed $expected) {
      Assert::equals($t->property('fixture'), $expected->target());
    }
  }

  #[Test]
  public function cannot_access_target() {
    $t= $this->declare('{ private static $fixture; }');
    try {
      $t->property('fixture')->get(null);
      throw new AssertionFailedError('No exception was raised');
    } catch (CannotAccess $expected) {
      Assert::equals($t->property('fixture'), $expected->target());
    }
  }
}