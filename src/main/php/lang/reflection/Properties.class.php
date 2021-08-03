<?php namespace lang\reflection;

use Traversable;

/**
 * Type properties enumeration and lookup
 *
 * @test lang.reflection.unittest.PropertiesTest
 */
class Properties extends Members {

  /**
   * Returns all properties
   *
   * @return iterable
   */
  public function getIterator(): Traversable {
    foreach ($this->reflect->getProperties() as $property) {
      if (0 !== strncmp($property->name, '__', 2)) {
        yield $property->name => new Property($property);
      }
    }
  }

  /**
   * Returns a property by a given name, or NULL
   *
   * @param  string $name
   * @return ?lang.reflection.Property
   */
  public function named($name) {
    return $this->reflect->hasProperty($name)
      ? new Property($this->reflect->getProperty($name))
      : null
    ;
  }
}