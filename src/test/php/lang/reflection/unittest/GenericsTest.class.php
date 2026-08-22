<?php namespace lang\reflection\unittest;

use lang\{Reflection, Type, Primitive, TypeParameter, IllegalArgumentException, IllegalStateException};
use test\{Assert, Before, Expect, Test, Values};

class GenericsTest {
  private $sequence;

  /** @return iterable */
  private function generic() {
    yield [self::class, false];
    yield [Sequence::class, false];
    yield [$this->sequence, true];
  }

  /** @return iterable */
  private function types() {
    yield [Reflection::of(self::class), null];
    yield [Reflection::of(Sequence::class), ['T']];
    yield [Reflection::of($this->sequence), null];
  }

  /** @return iterable */
  private function methods() {
    yield [Reflection::of(self::class)->method(__FUNCTION__), null];
    yield [Reflection::of(Sequence::class)->method('toString'), null];
    yield [Reflection::of($this->sequence)->method('map'), ['R']];
  }

  /** @return iterable */
  private function incorrect() {
    yield [[], 'no arguments'];
    yield [[Primitive::$INT, Primitive::$STRING], 'too many arguments'];
  }

  #[Before]
  public function sequence() {
    $this->sequence= create('new lang.reflection.unittest.Sequence<string>', 'Hello', 'Test');
  }

  #[Test, Values(from: 'generic')]
  public function reflection_of($arg, $expected) {
    Assert::equals($expected, Reflection::of($arg)->generic());
  }

  #[Test, Values(from: 'generic')]
  public function reflection_type($arg, $expected) {
    Assert::equals($expected, Reflection::type($arg)->generic());
  }

  #[Test, Values(from: 'types')]
  public function parameterized_type($type, $expected) {
    Assert::equals($expected, $type->parameterized());
  }

  #[Test, Values(from: 'methods')]
  public function parameterized_method($method, $expected) {
    Assert::equals($expected, $method->parameterized());
  }

  #[Test]
  public function parameterize_type() {
    $definition= Reflection::type(Sequence::class);
    $t= $definition->parameterize([Primitive::$STRING]);

    Assert::true($t->generic());
    Assert::equals($definition, $t->definition());
    Assert::equals([Primitive::$STRING], $t->arguments());
  }

  #[Test]
  public function parameterize_method() {
    $definition= Reflection::type($this->sequence)->method('map');
    $m= $definition->parameterize([Primitive::$INT]);

    Assert::true($m->generic());
    Assert::equals($definition, $m->definition());
    Assert::equals([Primitive::$INT], $m->arguments());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function parameterize_non_generic_type() {
    Reflection::type(self::class)->parameterize([]);
  }

  #[Test, Expect(IllegalStateException::class)]
  public function parameterize_non_generic_method() {
    Reflection::type($this->sequence)->method('elements')->parameterize([]);
  }

  #[Test, Expect(IllegalArgumentException::class), Values(from: 'incorrect')]
  public function parameterize_type_with_incorrect($arguments) {
    Reflection::type(Sequence::class)->parameterize($arguments);
  }

  #[Test, Expect(IllegalArgumentException::class), Values(from: 'incorrect')]
  public function parameterize_method_with_incorrect($arguments) {
    Reflection::type($this->sequence)->method('map')->parameterize($arguments);
  }

  #[Test]
  public function constructor_parameter_resolved() {
    Assert::equals(
      Type::forName('string[]'),
      Reflection::type($this->sequence)->constructor()->parameter(0)->constraint()->type()
    );
  }

  #[Test]
  public function method_parameter_resolved() {
    Assert::equals(
      Type::forName('string[]'),
      Reflection::type($this->sequence)->method('extend')->parameter(0)->constraint()->type()
    );
  }

  #[Test]
  public function method_returns_component_resolved() {
    Assert::equals(
      Type::forName('string[]'),
      Reflection::type($this->sequence)->method('elements')->returns()->type()
    );
  }

  #[Test]
  public function method_returns_self_resolved() {
    $type= Reflection::type($this->sequence);
    Assert::equals(
      $type->class(),
      $type->method('extend')->returns()->type()
    );
  }

  #[Test]
  public function generic_method_returns() {
    $method= Reflection::type($this->sequence)->method('map');
    Assert::equals(
      Type::named('lang.reflection.unittest.Sequence<R>', ['R' => fn() => new TypeParameter('R')]),
      $method->returns()->type()
    );
  }

  #[Test]
  public function generic_method_parameter() {
    $method= Reflection::type($this->sequence)->method('map');
    Assert::equals(
      Type::named('function(string): R', ['R' => fn() => new TypeParameter('R')]),
      $method->parameter(0)->constraint()->type()
    );
  }

  #[Test]
  public function generic_method_returns_resolved() {
    $method= Reflection::type($this->sequence)->method('map');
    Assert::equals(
      Type::forName('lang.reflection.unittest.Sequence<int>'),
      $method->parameterize([Primitive::$INT])->returns()->type()
    );
  }

  #[Test]
  public function generic_method_parameter_resolved() {
    $method= Reflection::type($this->sequence)->method('map');
    Assert::equals(
      Type::forName('function(string): int'),
      $method->parameterize([Primitive::$INT])->parameter(0)->constraint()->type()
    );
  }

  #[Test]
  public function generic_method_invocation() {
    $method= Reflection::type($this->sequence)
      ->method('map')
      ->parameterize([Primitive::$INT])
    ;
    Assert::equals([5, 4], $method->invoke($this->sequence, [fn($e) => strlen($e)])->elements());
  }

  #[Test]
  public function generic_method_closure() {
    $closure= Reflection::type($this->sequence)
      ->method('map')
      ->parameterize([Primitive::$INT])
      ->closure($this->sequence)
    ;
    Assert::equals([5, 4], $closure(fn($e) => strlen($e))->elements());
  }
}