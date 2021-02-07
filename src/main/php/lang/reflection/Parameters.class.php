<?php namespace lang\reflection;

use lang\{Reflection, Type};

/**
 * Method or constructor parameters enumeration and lookup
 *
 * @test lang.reflection.unittest.MethodsTest
 */
class Parameters implements \IteratorAggregate {
  private $method;

  /** @param ReflectionMethod $method */
  public function __construct($method) {
    $this->method= $method;
  }

  /**
   * Returns number of parameters
   *
   * @param  bool $required Whether to count only required parameters
   * @return int
   */
  public function size($required= false) {
    return $required ? $this->method->getNumberOfRequiredParameters() : $this->method->getNumberOfParameters();
  }

  /**
   * Gets a parameter at a given position
   *
   * @param  int $position
   * @return ?lang.reflection.Parameter
   */
  public function at(int $position) {
    $list= $this->method->getParameters();
    return isset($list[$position]) ? new Parameter($list[$position], $this->method) : null;
  }

  /**
   * Gets a parameter by a given name
   *
   * @param  string $name
   * @return ?lang.reflection.Parameter
   */
  public function named(string $name) {
    foreach ($this->method->getParameters() as $param) {
      if ($name === $param->name) return new Parameter($param, $this->method);
    }
    return null;
  }

  /** @return ?lang.reflection.Parameter */
  public function first() {
    $list= $this->method->getParameters();
    return $list ? new Parameter($list[0], $this->method) : null;
  }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->method->getParameters() as $parameter) {
      yield $parameter->name => new Parameter($parameter, $this->method);
    }
  }

  /**
   * Returns whether a method accepts a given argument list
   *
   * @param  var[] $args
   * @param  ?int $size
   * @return bool
   */
  public function accept(array $arguments, $size= null): bool {
    $parameters= $this->method->getParameters();
    if (null !== $size && $size !== sizeof($parameters)) return false;

    // Only fetch api doc types if necessary
    $api= function() use(&$i, &$types) {
      $types ?? $types= Reflection::meta()->methodParameterTypes($this->method);
      return $types[$i] ?? null;
    };

    $context= Member::resolve($this->method);
    foreach ($parameters as $i => $parameter) {

      // If a given value is missing check whether parameter is optional
      if (!array_key_exists($i, $arguments)) return $parameter->isOptional();

      // A value is present for this parameter, now check type
      if (null === ($type= Type::resolve($parameter->getType(), $context, $api))) continue;

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