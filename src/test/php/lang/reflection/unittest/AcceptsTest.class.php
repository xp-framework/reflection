<?php namespace lang\reflection\unittest;

use unittest\{Assert, Test, Values};

class AcceptsTest {
  use TypeDefinition;

  /** @return iterable */
  private function fixtures() {
    yield ['<T>()', [], true];
    yield ['<T>()', ['test'], true];

    yield ['<T>($name)', ['test'], true];
    yield ['<T>($name)', [], false];

    yield ['<T>(string $name)', ['test'], true];
    yield ['<T>(string $name)', [1], false];
    yield ['<T>(string $name, int $age)', ['test'], false];
    yield ['<T>(string $name, int $age)', ['test', 'fails'], false];
    yield ['<T>(string $name, int $age)', ['test', 1], true];

    yield ['<T>(string $name, int $age= 0)', ['test'], true];
    yield ['<T>(string $name, int $age= 0)', ['test', 'fails'], false];
    yield ['<T>(string $name, int $age= 0)', ['test', 1], true];

    yield ['<T>(string... $args)', [], true];
    yield ['<T>(string... $args)', ['test'], true];
    yield ['<T>(string... $args)', ['test', 'works'], true];
    yield ['<T>(string... $args)', [1], false];
    yield ['<T>(string... $args)', ['test', 1], false];

    yield ['/** @param string $name */ <T>($name)', ['test'], true];
    yield ['/** @param string $name */ <T>($name)', [1], false];
    yield ['/** @param string[] $name */ <T>($name)', [['test', 'works']], true];
    yield ['/** @param string[] $name */ <T>($name)', [['test', 1]], false];

    yield ['/** @param string|int $arg */ <T>($arg)', [1], true];
    yield ['/** @param string|int $arg */ <T>($arg)', ['test'], true];
    yield ['/** @param string|int $arg */ <T>($arg)', [$this], false];

    yield ['/** @param string[] $name */ <T>(array $name)', [['test', 1]], false];
    yield ['/** @param function(): string $func */ <T>(callable $func)', [function(string $arg): int { }], false];

    if (PHP_VERSION_ID >= 80000) {
      yield ['<T>(string|int $arg)', [1], true];
      yield ['<T>(string|int $arg)', ['test'], true];
      yield ['<T>(string|int $arg)', [$this], false];

      yield ['<T>(string|int... $arg)', ['test'], true];
      yield ['<T>(string|int... $arg)', ['test', 1], true];
      yield ['<T>(string|int... $arg)', ['test', $this], false];
    }
  }

  #[Test, Values('fixtures')]
  public function accepts($fixture, $values, $expected) {
    $t= $this->declare('{ '.str_replace('<T>', 'public function fixture', $fixture).' { } }');
    Assert::equals($expected, $t->method('fixture')->accepts($values));
  }

  #[Test]
  public function accepts_self() {
    $t= $this->declare('{ public function fixture(self $arg) { } }');
    Assert::true($t->method('fixture')->accepts([$t->newInstance()]));
  }
}