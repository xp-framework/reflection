<?php namespace lang\reflection\unittest;

use ReflectionProperty;
use lang\reflection\AccessingFailed;
use test\verify\Condition;
use test\{Assert, Expect, Test};

#[Condition(assert: 'method_exists(ReflectionProperty::class, "getHooks")')]
class PropertyHooksTest {
  use TypeDefinition;

  #[Test]
  public function is_virtual() {
    $type= $this->declare('{ public string $fixture { get => "test"; } }');
    Assert::true($type->properties()->named('fixture')->virtual());
  }

  #[Test]
  public function get() {
    $type= $this->declare('{ public string $fixture { get => "test"; } }');
    $instance= $type->newInstance();

    Assert::equals('test', $type->properties()->named('fixture')->get($instance));
  }

  #[Test]
  public function set() {
    $type= $this->declare('{ public string $fixture { set => ucfirst($value); } }');
    $instance= $type->newInstance();
    $type->properties()->named('fixture')->set($instance, 'test');

    Assert::equals('Test', $instance->fixture);
  }

  #[Test]
  public function set_with_parameter() {
    $type= $this->declare('{ public string $fixture { set(string $arg) => ucfirst($arg); } }');
    $instance= $type->newInstance();
    $type->properties()->named('fixture')->set($instance, 'test');

    Assert::equals('Test', $instance->fixture);
  }

  #[Test, Expect(AccessingFailed::class)]
  public function get_set_only_raises() {
    $type= $this->declare('{ public string $fixture { set => ucfirst($value); } }');
    $instance= $type->newInstance();

    $type->properties()->named('fixture')->get($instance);
  }

  #[Test, Expect(AccessingFailed::class)]
  public function set_get_only_raises() {
    $type= $this->declare('{ public string $fixture { get => "test"; } }');
    $instance= $type->newInstance();

    $type->properties()->named('fixture')->set($instance, 'test');
  }
}