<?php namespace lang\reflection\unittest;

use unittest\actions\RuntimeVersion;
use unittest\{Action, Assert, Test};

class ConstantsTest {
  use TypeDefinition;

  #[Test]
  public function name() {
    Assert::equals('FIXTURE', $this->type('{ const FIXTURE = "test"; }')->constant('FIXTURE')->name());
  }

  #[Test]
  public function value() {
    Assert::equals('test', $this->type('{ const FIXTURE = "test"; }')->constant('FIXTURE')->value());
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=7.1")')]
  public function private_constant() {
    $const= $this->type('{ private const FIXTURE = "test"; }')->constant('FIXTURE');
    Assert::equals([MODIFIER_PRIVATE, 'test'], [$const->modifiers(), $const->value()]);
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=7.1")')]
  public function protected_constant() {
    $const= $this->type('{ protected const FIXTURE = "test"; }')->constant('FIXTURE');
    Assert::equals([MODIFIER_PROTECTED, 'test'], [$const->modifiers(), $const->value()]);
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=7.1")')]
  public function public_constant() {
    $const= $this->type('{ public const FIXTURE = "test"; }')->constant('FIXTURE');
    Assert::equals([MODIFIER_PUBLIC, 'test'], [$const->modifiers(), $const->value()]);
  }
}