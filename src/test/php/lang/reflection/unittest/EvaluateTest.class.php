<?php namespace lang\reflection\unittest;

use lang\{IllegalArgumentException, Reflection, Type};
use test\{Assert, Expect, Test, Values};

class EvaluateTest {
  private static $EMPTY;
  const TEST = 1;

  static function __static() {
    self::$EMPTY= new Fixture();
  }

  /** @return iterable */
  private function expressions() {
    yield ['self::class', self::class];
    yield ['self::TEST', self::TEST];
    yield ['self::$EMPTY', self::$EMPTY];
    yield ['Fixture::class', Fixture::class];
    yield ['Fixture::TEST', Fixture::TEST];
    yield ['new Fixture()', new Fixture()];
    yield ['new Fixture("test")', new Fixture('test')];
  }

  /** @return iterable */
  private function functions() {
    yield ['fn() => "test"', [], 'test'];
    yield ['fn($a, $b) => $a + $b', [1, 2], 3];
    yield ['function() { return "test"; }', [], 'test'];
    yield ['function($arg) { return $arg; }', ['test'], 'test'];
    yield ['function($arg) { return version_compare($arg, "7.0.0"); }', [PHP_VERSION], 1];
    yield ['function($arg) { if ($arg) { return "test"; } }', [true], 'test'];
  }

  #[Test, Values(from: 'expressions')]
  public function evaluate($expression, $value) {
    Assert::equals($value, Reflection::of($this)->evaluate($expression));
  }

  #[Test, Values(from: 'functions')]
  public function run($expression, $args, $value) {
    $func= cast(Reflection::of($this)->evaluate($expression), 'callable');
    Assert::equals($value, $func(...$args));
  }

  #[Test]
  public function arrow_function_with_trailing_comma() {
    $func= cast(Reflection::of($this)->evaluate('[fn() => "test",]'), 'callable[]');
    Assert::equals('test', $func[0]());
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: 'Test')]
  public function throw_expression_supported_in_fn() {
    $func= Reflection::of($this)->evaluate('fn() => throw new \lang\IllegalArgumentException("Test")');
    $func();
  }
}