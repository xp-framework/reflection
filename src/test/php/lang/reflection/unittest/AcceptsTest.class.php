<?php namespace lang\reflection\unittest;

use unittest\{Assert, Test, Values};
use util\Date;

class AcceptsTest {
  use TypeDefinition;

  /**
   * Declares a type with a given method declaration
   *
   * @param  string $declaration
   * @param  [:string] $imports
   * @return lang.reflection.Type
   */
  private function type($declaration, $imports= []) {
    return $this->declare(
      '{ '.str_replace('<T>', 'public function fixture', $declaration).' { } }',
      null,
      $imports
    );
  }

  /** @return iterable */
  private function fixtures() {
    $t= $this->type('<T>()');
    yield [$t, [], null, true];
    yield [$t, ['test'], null, true];
    yield [$t, [], 0, true];
    yield [$t, ['test'], 0, true];
    yield [$t, [], 1, false];
    yield [$t, ['test'], 1, false];

    $t= $this->type('<T>($name)');
    yield [$t, ['test'], null, true];
    yield [$t, [], null, false];
    yield [$t, [], 0, false];
    yield [$t, ['test'], 1, true];
    yield [$t, [], 0, false];
    yield [$t, ['test'], 1, true];

    $t= $this->type('<T>(string $name)');
    yield [$t, ['test'], null, true];
    yield [$t, [1], null, false];

    $t= $this->type('<T>(string $name, int $age)');
    yield [$t, ['test'], null, false];
    yield [$t, ['test', 'fails'], null, false];
    yield [$t, ['test', 1], null, true];

    $t= $this->type('<T>(string $name, int $age= 0)');
    yield [$t, ['test'], null, true];
    yield [$t, ['test', 'fails'], null, false];
    yield [$t, ['test', 1], null, true];

    $t= $this->type('<T>(string... $args)');
    yield [$t, [], null, true];
    yield [$t, ['test'], null, true];
    yield [$t, ['test', 'works'], null, true];
    yield [$t, [1], null, false];
    yield [$t, ['test', 1], null, false];

    $t= $this->type('<T>(Date $arg)', ['util.Date' => null]);
    yield [$t, [], null, false];
    yield [$t, [null], null, false];
    yield [$t, [new Date()], null, true];

    $t= $this->type('<T>(AcceptsTest $arg)');
    yield [$t, [], null, false];
    yield [$t, [null], null, false];
    yield [$t, [$this], null, true];

    $t= $this->type('<T>(\lang\reflection\unittest\AcceptsTest $arg)');
    yield [$t, [], null, false];
    yield [$t, [null], null, false];
    yield [$t, [$this], null, true];

    $t= $this->type('<T>(self $arg)');
    yield [$t, [], null, false];
    yield [$t, [null], null, false];
    yield [$t, [$t->newInstance()], null, true];

    $t= $this->type('<T>(self $arg= null)');
    yield [$t, [], null, true];
    yield [$t, [null], null, true];
    yield [$t, ['test'], null, false];
    yield [$t, [$t->newInstance()], null, true];

    $t= $this->type('<T>(self... $instances)');
    yield [$t, [], null, true];
    yield [$t, [null], null, false];
    yield [$t, [$t->newInstance()], null, true];

    $t= $this->type('/** @param string $name */ <T>($name)');
    yield [$t, ['test'], null, true];
    yield [$t, [1], null, false];

    $t= $this->type('/** @param string[] $name */ <T>($name)');
    yield [$t, [['test', 'works']], null, true];
    yield [$t, [['test', 1]], null, false];

    $t= $this->type('/** @param string|int $arg */ <T>($arg)');
    yield [$t, [1], null, true];
    yield [$t, ['test'], null, true];
    yield [$t, [$this], null, false];

    $t= $this->type('/** @param ?(string|int) $arg */ <T>($arg)');
    yield [$t, [1], null, true];
    yield [$t, ['test'], null, true];
    yield [$t, [null], null, true];
    yield [$t, [$this], null, false];

    $t= $this->type('/** @param Date $arg */ <T>($arg)', ['util.Date' => null]);
    yield [$t, [], null, false];
    yield [$t, [null], null, false];
    yield [$t, [new Date()], null, true];

    $t= $this->type('/** @param AcceptsTest $arg */ <T>($arg)');
    yield [$t, [], null, false];
    yield [$t, [null], null, false];
    yield [$t, [$this], null, true];

    $t= $this->type('/** @param \lang\reflection\unittest\AcceptsTest $arg */ <T>($arg)');
    yield [$t, [], null, false];
    yield [$t, [null], null, false];
    yield [$t, [$this], null, true];

    $t= $this->type('/** @param lang.reflection.unittest.AcceptsTest $arg */ <T>($arg)');
    yield [$t, [], null, false];
    yield [$t, [null], null, false];
    yield [$t, [$this], null, true];

    $t= $this->type('/** @param self $arg */ <T>($arg)');
    yield [$t, [], null, false];
    yield [$t, [null], null, false];
    yield [$t, [$t->newInstance()], null, true];

    $t= $this->type('/** @param ?self $arg */ <T>($arg)');
    yield [$t, [], null, false];
    yield [$t, [null], null, true];
    yield [$t, ['test'], null, false];
    yield [$t, [$t->newInstance()], null, true];

    $t= $this->type('/** @param string[] $name */ <T>(array $name)');
    yield [$t, [[]], null, true];
    yield [$t, [['test', 'works']], null, true];
    yield [$t, [['test', 1]], null, false];

    $t= $this->type('/** @param self[] $name */ <T>(array $name)');
    yield [$t, [[]], null, true];
    yield [$t, [[$t->newInstance()]], null, true];
    yield [$t, [[$t->newInstance(), null]], null, false];

    $t= $this->type('/** @param function(): string $func */ <T>(callable $func)');
    yield [$t, [function(): int { }], null, false];
    yield [$t, [function(): string { }], null, true];

    $t= $this->type("/**\n * @param string \$a\n * @param string \$b\n*/ <T>(\$a, \$b)");
    yield [$t, [], null, false];
    yield [$t, ['test'], null, false];
    yield [$t, ['test', 1], null, false];
    yield [$t, ['test', 'works'], null, true];

    if (PHP_VERSION_ID >= 70100) {
      $t= $this->type('<T>(?string $arg)');
      yield [$t, [], null, false];
      yield [$t, [null], null, true];
      yield [$t, ['test'], null, true];
      yield [$t, [$this], null, false];
    }

    if (PHP_VERSION_ID >= 80000) {
      $t= $this->type('<T>(string|int $arg)');
      yield [$t, [1], null, true];
      yield [$t, ['test'], null, true];
      yield [$t, [$this], null, false];

      $t= $this->type('<T>(string|int|null $arg)');
      yield [$t, [1], null, true];
      yield [$t, ['test'], null, true];
      yield [$t, [null], null, true];
      yield [$t, [$this], null, false];

      $t= $this->type('<T>(string|int... $arg)');
      yield [$t, ['test'], null, true];
      yield [$t, ['test', 1], null, true];
      yield [$t, ['test', $this], null, false];

      $t= $this->type('<T>(string|int|null... $arg)');
      yield [$t, ['test'], null, true];
      yield [$t, [null], null, true];
      yield [$t, ['test', 1], null, true];
      yield [$t, ['test', $this], null, false];
    }
  }

  #[Test, Values('fixtures')]
  public function accept($t, $values, $size, $expected) {
    Assert::equals($expected, $t->method('fixture')->parameters()->accept($values, $size));
  }
}