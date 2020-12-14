<?php namespace lang\reflection\unittest;

use unittest\actions\RuntimeVersion;
use unittest\{Action, Assert, Test};

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

  #[Test, Action(eval: 'new RuntimeVersion(">=7.1")')]
  public function private_constant() {
    $const= $this->declare('{ private const FIXTURE = "test"; }')->constant('FIXTURE');
    Assert::equals([MODIFIER_PRIVATE, 'test'], [$const->modifiers(), $const->value()]);
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=7.1")')]
  public function protected_constant() {
    $const= $this->declare('{ protected const FIXTURE = "test"; }')->constant('FIXTURE');
    Assert::equals([MODIFIER_PROTECTED, 'test'], [$const->modifiers(), $const->value()]);
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=7.1")')]
  public function public_constant() {
    $const= $this->declare('{ public const FIXTURE = "test"; }')->constant('FIXTURE');
    Assert::equals([MODIFIER_PUBLIC, 'test'], [$const->modifiers(), $const->value()]);
  }
}