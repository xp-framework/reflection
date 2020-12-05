<?php namespace lang\reflection;

class Constants extends Members {

  /** @return iterable */
  public function getIterator() {
    foreach ($this->reflect->getReflectionConstants() as $constant) {
      if (0 !== strncmp($constant->name, '__', 2)) {
        yield $constant->name => new Constant($constant);
      }
    }
  }

  /**
   * Returns a method by a given name, or NULL
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