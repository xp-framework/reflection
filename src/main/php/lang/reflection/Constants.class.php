<?php namespace lang\reflection;

class Constants extends Members {

  /**
   * Returns all constants
   *
   * @return iterable
   */
  public function getIterator() {
    if (PHP_VERSION_ID >= 70100) {
      foreach ($this->reflect->getReflectionConstants() as $constant) {
        if (0 !== strncmp($constant->name, '__', 2)) {
          yield $constant->name => new Constant($constant);
        }
      }
    } else {

      // PHP 7.0 does not have getReflectionConstants(), enumerate constants
      // using name and values instead and use ReflectionClassConstant polyfill
      //
      // @codeCoverageIgnoreStart
      foreach ($this->reflect->getConstants() as $name => $_) {
        if (0 !== strncmp($name, '__', 2)) {
          yield $name => new Constant(new \ReflectionClassConstant($this->reflect->name, $name));
        }
      }
      // @codeCoverageIgnoreEnd
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
      ? new Constant(new \ReflectionClassConstant($this->reflect->name, $name))
      : null
    ;
  }
}