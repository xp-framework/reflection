<?php namespace lang\reflection;

use Traversable;
use lang\{Reflection, Type};

/**
 * Method or constructor parameters enumeration and lookup
 *
 * @test lang.reflection.unittest.MethodsTest
 */
class Parameters implements \IteratorAggregate {
  private $reflect, $resolve;

  /**
   * Creates a new instance
   *
   * @param  ReflectionMethod $reflect
   * @param  [:function(?string): Type] $resolve
   */
  public function __construct($reflect, $resolve) {
    $this->reflect= $reflect;
    $this->resolve= $resolve;
  }

  /**
   * Returns number of parameters
   *
   * @param  bool $required Whether to count only required parameters
   * @return int
   */
  public function size($required= false) {
    return $required ? $this->reflect->getNumberOfRequiredParameters() : $this->reflect->getNumberOfParameters();
  }

  /**
   * Gets a parameter at a given position
   *
   * @param  int $position
   * @return ?lang.reflection.Parameter
   */
  public function at(int $position) {
    $list= $this->reflect->getParameters();
    return isset($list[$position]) ? new Parameter($list[$position], $this->resolve, $this->reflect) : null;
  }

  /**
   * Gets a parameter by a given name
   *
   * @param  string $name
   * @return ?lang.reflection.Parameter
   */
  public function named(string $name) {
    foreach ($this->reflect->getParameters() as $param) {
      if ($name === $param->name) return new Parameter($param, $this->resolve, $this->reflect);
    }
    return null;
  }

  /** @return ?lang.reflection.Parameter */
  public function first() {
    $list= $this->reflect->getParameters();
    return $list ? new Parameter($list[0], $this->resolve, $this->reflect) : null;
  }

  /** @return iterable */
  public function getIterator(): Traversable {
    foreach ($this->reflect->getParameters() as $parameter) {
      yield $parameter->name => new Parameter($parameter, $this->resolve, $this->reflect);
    }
  }

  /**
   * Returns whether these parameters accepts a given argument list. Optionally,
   * the argument count can be passed, testing for an *exact match* with the number
   * of parameters, e.g. to find "zero-arg getters" and "one-arg constructors".
   *
   * @param  var[] $args
   * @param  ?int $count
   * @return bool
   */
  public function accept(array $arguments, $count= null): bool {
    $parameters= $this->reflect->getParameters();
    if (null !== $count && $count !== sizeof($parameters)) return false;

    // Only fetch api doc types if necessary
    $api= function() use(&$i, &$types) {
      $types ?? $types= Reflection::meta()->methodParameterTypes($this->reflect);
      return $types[$i] ?? null;
    };

    foreach ($parameters as $i => $parameter) {

      // If a given value is missing check whether parameter is optional
      if (!array_key_exists($i, $arguments)) return $parameter->isOptional();

      // A value is present for this parameter, now check type
      if (null === ($type= Type::resolve($parameter->getType(), $this->resolve, $api))) continue;

      // For variadic parameters, verify rest of arguments
      if ($parameter->isVariadic()) {
        for ($s= sizeof($arguments); $i < $s; $i++) {
          if (!$type->isInstance($arguments[$i])) return false;
        }
        return true;
      }

      // ...otherwise, verify this argument and continue to next
      if (!$type->isInstance($arguments[$i])) return false;
    }
    return true;
  }
}