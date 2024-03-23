<?php namespace lang\reflection;

use Traversable, ReflectionClassConstant;

/**
 * Type constants enumeration and lookup
 *
 * @test lang.reflection.unittest.ConstantsTest
 */
class Constants extends Members {

  /**
   * Returns all constants
   *
   * @return iterable
   */
  public function getIterator(): Traversable {
    foreach ($this->reflect->getReflectionConstants() as $constant) {
      if (0 !== strncmp($constant->name, '__', 2)) {
        yield $constant->name => new Constant($constant);
      }
    }
  }

  /**
   * Returns a constant by a given name, or NULL
   *
   * @param  string $name
   * @return ?lang.reflection.Constant
   */
  public function named($name) {
    return $this->reflect->hasConstant($name)
      ? new Constant(new ReflectionClassConstant($this->reflect->name, $name))
      : null
    ;
  }
}