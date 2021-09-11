<?php namespace lang\reflection;

use Traversable;
use lang\{VirtualProperty, Reflection};

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
    foreach (Reflection::meta()->virtualProperties($this->reflect) as $name => $virtual) {
      yield $name => new Property(new VirtualProperty($this->reflect, $name, $virtual));
    }
  }

  /**
   * Returns a property by a given name, or NULL
   *
   * @param  string $name
   * @return ?lang.reflection.Property
   */
  public function named($name) {
    if ($this->reflect->hasProperty($name)) {
      return new Property($this->reflect->getProperty($name));
    } else if ($virtual= Reflection::meta()->virtualProperties($this->reflect)[$name] ?? null) {
      return new Property(new VirtualProperty($this->reflect, $name, $virtual));
    }
    return null;
  }
}