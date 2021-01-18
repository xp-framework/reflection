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
    yield [$t, [], true];
    yield [$t, ['test'], true];

    $t= $this->type('<T>($name)');
    yield [$t, ['test'], true];
    yield [$t, [], false];

    $t= $this->type('<T>(string $name)');
    yield [$t, ['test'], true];
    yield [$t, [1], false];

    $t= $this->type('<T>(string $name, int $age)');
    yield [$t, ['test'], false];
    yield [$t, ['test', 'fails'], false];
    yield [$t, ['test', 1], true];

    $t= $this->type('<T>(string $name, int $age= 0)');
    yield [$t, ['test'], true];
    yield [$t, ['test', 'fails'], false];
    yield [$t, ['test', 1], true];

    $t= $this->type('<T>(string... $args)');
    yield [$t, [], true];
    yield [$t, ['test'], true];
    yield [$t, ['test', 'works'], true];
    yield [$t, [1], false];
    yield [$t, ['test', 1], false];

    $t= $this->type('<T>(Date $arg)', ['util.Date' => null]);
    yield [$t, [], false];
    yield [$t, [null], false];
    yield [$t, [new Date()], true];

    $t= $this->type('<T>(AcceptsTest $arg)');
    yield [$t, [], false];
    yield [$t, [null], false];
    yield [$t, [$this], true];

    $t= $this->type('<T>(\lang\reflection\unittest\AcceptsTest $arg)');
    yield [$t, [], false];
    yield [$t, [null], false];
    yield [$t, [$this], true];

    $t= $this->type('<T>(self $arg)');
    yield [$t, [], false];
    yield [$t, [null], false];
    yield [$t, [$t->newInstance()], true];

    $t= $this->type('<T>(self $arg= null)');
    yield [$t, [], true];
    yield [$t, [null], true];
    yield [$t, ['test'], false];
    yield [$t, [$t->newInstance()], true];

    $t= $this->type('<T>(self... $instances)');
    yield [$t, [], true];
    yield [$t, [null], false];
    yield [$t, [$t->newInstance()], true];

    $t= $this->type('/** @param string $name */ <T>($name)');
    yield [$t, ['test'], true];
    yield [$t, [1], false];

    $t= $this->type('/** @param string[] $name */ <T>($name)');
    yield [$t, [['test', 'works']], true];
    yield [$t, [['test', 1]], false];

    $t= $this->type('/** @param string|int $arg */ <T>($arg)');
    yield [$t, [1], true];
    yield [$t, ['test'], true];
    yield [$t, [$this], false];

    $t= $this->type('/** @param ?(string|int) $arg */ <T>($arg)');
    yield [$t, [1], true];
    yield [$t, ['test'], true];
    yield [$t, [null], true];
    yield [$t, [$this], false];

    $t= $this->type('/** @param Date $arg */ <T>($arg)', ['util.Date' => null]);
    yield [$t, [], false];
    yield [$t, [null], false];
    yield [$t, [new Date()], true];

    $t= $this->type('/** @param AcceptsTest $arg */ <T>($arg)');
    yield [$t, [], false];
    yield [$t, [null], false];
    yield [$t, [$this], true];

    $t= $this->type('/** @param \lang\reflection\unittest\AcceptsTest $arg */ <T>($arg)');
    yield [$t, [], false];
    yield [$t, [null], false];
    yield [$t, [$this], true];

    $t= $this->type('/** @param lang.reflection.unittest.AcceptsTest $arg */ <T>($arg)');
    yield [$t, [], false];
    yield [$t, [null], false];
    yield [$t, [$this], true];

    $t= $this->type('/** @param self $arg */ <T>($arg)');
    yield [$t, [], false];
    yield [$t, [null], false];
    yield [$t, [$t->newInstance()], true];

    $t= $this->type('/** @param ?self $arg */ <T>($arg)');
    yield [$t, [], false];
    yield [$t, [null], true];
    yield [$t, ['test'], false];
    yield [$t, [$t->newInstance()], true];

    $t= $this->type('/** @param string[] $name */ <T>(array $name)');
    yield [$t, [[]], true];
    yield [$t, [['test', 'works']], true];
    yield [$t, [['test', 1]], false];

    $t= $this->type('/** @param self[] $name */ <T>(array $name)');
    yield [$t, [[]], true];
    yield [$t, [[$t->newInstance()]], true];
    yield [$t, [[$t->newInstance(), null]], false];

    $t= $this->type('/** @param function(): string $func */ <T>(callable $func)');
    yield [$t, [function(): int { }], false];
    yield [$t, [function(): string { }], true];

    $t= $this->type("/**\n * @param string \$a\n * @param string \$b\n*/ <T>(\$a, \$b)");
    yield [$t, [], false];
    yield [$t, ['test'], false];
    yield [$t, ['test', 1], false];
    yield [$t, ['test', 'works'], true];

    if (PHP_VERSION_ID >= 70100) {
      $t= $this->type('<T>(?string $arg)');
      yield [$t, [], false];
      yield [$t, [null], true];
      yield [$t, ['test'], true];
      yield [$t, [$this], false];
    }

    if (PHP_VERSION_ID >= 80000) {
      $t= $this->type('<T>(string|int $arg)');
      yield [$t, [1], true];
      yield [$t, ['test'], true];
      yield [$t, [$this], false];

      $t= $this->type('<T>(string|int|null $arg)');
      yield [$t, [1], true];
      yield [$t, ['test'], true];
      yield [$t, [null], true];
      yield [$t, [$this], false];

      $t= $this->type('<T>(string|int... $arg)');
      yield [$t, ['test'], true];
      yield [$t, ['test', 1], true];
      yield [$t, ['test', $this], false];

      $t= $this->type('<T>(string|int|null... $arg)');
      yield [$t, ['test'], true];
      yield [$t, [null], true];
      yield [$t, ['test', 1], true];
      yield [$t, ['test', $this], false];
    }
  }

  #[Test, Values('fixtures')]
  public function accepts($t, $values, $expected) {
    Assert::equals($expected, $t->method('fixture')->accepts($values));
  }
}