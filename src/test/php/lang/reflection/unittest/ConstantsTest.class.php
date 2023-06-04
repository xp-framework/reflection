<?php namespace lang\reflection\unittest;

use lang\reflection\Constraint;
use lang\{Primitive, Type};
use test\verify\Runtime;
use test\{Assert, Test, Values};

class ConstantsTest {
  use TypeDefinition;

  #[Test]
  public function name() {
    Assert::equals('FIXTURE', $this->declare('{ const FIXTURE = "test"; }')->constant('FIXTURE')->name());
  }

  #[Test]
  public function compoundName() {
    $t= $this->declare('{ const FIXTURE = "test"; }');
    Assert::equals($t->name().'::FIXTURE', $t->constant('FIXTURE')->compoundName());
  }

  #[Test]
  public function value() {
    Assert::equals('test', $this->declare('{ const FIXTURE = "test"; }')->constant('FIXTURE')->value());
  }

  #[Test]
  public function no_comment() {
    Assert::null($this->declare('{ const FIXTURE = "test"; }')->constant('FIXTURE')->comment());
  }

  #[Test, Runtime(php: '>=7.1')]
  public function with_comment() {
    Assert::equals('Test', $this->declare('{ /** Test */ const FIXTURE = "test"; }')->constant('FIXTURE')->comment());
  }

  #[Test]
  public function declaredIn() {
    $t= $this->declare('{ const FIXTURE = "test";}');
    Assert::equals($t, $t->constant('FIXTURE')->declaredIn());
  }

  #[Test]
  public function constants() {
    $t= $this->declare('{ const one = 1, two = 2; }');
    Assert::equals(
      ['one' => $t->constant('one'), 'two' => $t->constant('two')],
      iterator_to_array($t->constants())
    );
  }

  #[Test]
  public function named() {
    $t= $this->declare('{ const FIXTURE = "test"; }');
    Assert::equals($t->constant('FIXTURE'), $t->constants()->named('FIXTURE'));
  }

  #[Test, Runtime(php: '>=7.1')]
  public function private_constant() {
    $const= $this->declare('{ private const FIXTURE = "test"; }')->constant('FIXTURE');
    Assert::equals([MODIFIER_PRIVATE, 'test'], [$const->modifiers()->bits(), $const->value()]);
  }

  #[Test, Runtime(php: '>=7.1')]
  public function protected_constant() {
    $const= $this->declare('{ protected const FIXTURE = "test"; }')->constant('FIXTURE');
    Assert::equals([MODIFIER_PROTECTED, 'test'], [$const->modifiers()->bits(), $const->value()]);
  }

  #[Test, Runtime(php: '>=7.1')]
  public function public_constant() {
    $const= $this->declare('{ public const FIXTURE = "test"; }')->constant('FIXTURE');
    Assert::equals([MODIFIER_PUBLIC, 'test'], [$const->modifiers()->bits(), $const->value()]);
  }

  #[Test]
  public function string_representation() {
    $t= $this->declare('{ const FIXTURE = "test"; }');
    Assert::equals(
      'public const var FIXTURE = "test"',
      $t->constant('FIXTURE')->toString()
    );
  }

  #[Test]
  public function without_constraint() {
    Assert::equals(
      new Constraint(Type::$VAR, false),
      $this->declare('{ const FIXTURE = "test"; }')->constant('FIXTURE')->constraint()
    );
  }

  #[Test, Values(['/** @type string */', '/** @var string */'])]
  public function with_constraint_in_apidoc($comment) {
    Assert::equals(
      new Constraint(Primitive::$STRING, false),
      $this->declare('{ '.$comment.' const FIXTURE = "test"; }')->constant('FIXTURE')->constraint()
    );
  }

  #[Test, Runtime(php: '>=8.3.0-dev')]
  public function with_constraint() {
    Assert::equals(
      new Constraint(Primitive::$STRING, true),
      $this->declare('{ const string FIXTURE = "test"; }')->constant('FIXTURE')->constraint()
    );
  }
}