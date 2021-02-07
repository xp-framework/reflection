<?php namespace lang\reflection\unittest;

use lang\reflection\Constructor;
use unittest\actions\RuntimeVersion;
use unittest\{Action, Assert, Test};

class ConstructorTest {
  use TypeDefinition;

  #[Test]
  public function absent() {
    Assert::null($this->declare('{ }')->constructor());
  }

  #[Test]
  public function present() {
    Assert::instance(Constructor::class, $this->declare('{ public function __construct() { } }')->constructor());
  }

  #[Test]
  public function name() {
    Assert::equals('__construct', $this->declare('{ public function __construct() { } }')->constructor()->name());
  }

  #[Test]
  public function string_representation_with_typed_parameter() {
    $t= $this->declare('{ public function __construct(array $s) { } }');
    Assert::equals(
      'public function __construct(array $s)',
      $t->constructor()->toString()
    );
  }

  #[Test]
  public function string_representation_with_apidoc_parameter() {
    $t= $this->declare('{
      /** @param array<string> $s */
      public function __construct($s) { }
    }');
    Assert::equals(
      'public function __construct(array<string> $s)',
      $t->constructor()->toString()
    );
  }

  #[Test]
  public function string_representation_with_unconstrained_parameter() {
    $t= $this->declare('{ public function __construct($s) { } }');
    Assert::equals(
      'public function __construct(var $s)',
      $t->constructor()->toString()
    );
  }
}