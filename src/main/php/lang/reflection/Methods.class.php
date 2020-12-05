<?php namespace lang\reflection;

class Methods extends Members {

  /** @return iterable */
  public function getIterator() {
    foreach ($this->reflect->getMethods() as $method) {
      if (0 !== strncmp($method->name, '__', 2)) {
        yield $method->name => new Method($method);
      }
    }
  }

  /**
   * Returns a method by a given name, or NULL
   *
   * @param  string $name
   * @return ?lang.reflection.Method
   */
  public function named($name) {
    return $this->reflect->hasMethod($name)
      ? new Method($this->reflect->getMethod($name))
      : null
    ;
  }
}