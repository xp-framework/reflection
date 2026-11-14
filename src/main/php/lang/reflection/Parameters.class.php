<?php namespace lang\reflection;

use Traversable;
use lang\{Reflection, Type};

/**
 * Method or constructor parameters enumeration and lookup
 *
 * @test lang.reflection.unittest.MethodsTest
 */
class Parameters implements \IteratorAggregate {
  private $holder;

  /** @param ReflectionFunctionAbstract $holder */
  public function __construct($holder) {
    $this->holder= $holder;
  }

  /**
   * Returns number of parameters
   *
   * @param  bool $required Whether to count only required parameters
   * @return int
   */
  public function size($required= false) {
    return $required ? $this->holder->getNumberOfRequiredParameters() : $this->holder->getNumberOfParameters();
  }

  /**
   * Gets a parameter at a given position
   *
   * @param  int $position
   * @return ?lang.reflection.Parameter
   */
  public function at(int $position) {
    $list= $this->holder->getParameters();
    return isset($list[$position]) ? new Parameter($list[$position], $this->holder) : null;
  }

  /**
   * Gets a parameter by a given name
   *
   * @param  string $name
   * @return ?lang.reflection.Parameter
   */
  public function named(string $name) {
    foreach ($this->holder->getParameters() as $param) {
      if ($name === $param->name) return new Parameter($param, $this->holder);
    }
    return null;
  }

  /** @return ?lang.reflection.Parameter */
  public function first() {
    $list= $this->holder->getParameters();
    return $list ? new Parameter($list[0], $this->holder) : null;
  }

  /** @return iterable */
  public function getIterator(): Traversable {
    foreach ($this->holder->getParameters() as $parameter) {
      yield $parameter->name => new Parameter($parameter, $this->holder);
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
    $parameters= $this->holder->getParameters();
    if (null !== $count && $count !== sizeof($parameters)) return false;

    // Only fetch api doc types if necessary
    $api= function() use(&$i, &$types) {
      $types ?? $types= Reflection::meta()->methodParameterTypes($this->holder);
      return $types[$i] ?? null;
    };

    $context= Member::resolve($this->holder);
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