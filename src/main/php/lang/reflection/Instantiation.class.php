<?php namespace lang\reflection;

use lang\Reflection;

/**
 * Reflection for a type instantation from an initializer function
 *
 * @test lang.reflection.unittest.InstantiationTest
 */
class Instantiation extends Routine {
  private $class, $initializer;

  /**
   * Creates a new instantiation function
   *
   * @param  ReflectionClass $class
   * @param  ReflectionFunctionAbstract $reflect 
   * @param  ?Closure $initializer
   */
  public function __construct($class, $reflect, $initializer= null) {
    parent::__construct($reflect);
    $this->class= $class;
    $this->initializer= $initializer;
  }

  /** @return string */
  public function toString() {
    return 'public function __construct('.$this->signature(Reflection::meta()).')';
  }

  /** Returns a compound name consisting of `[CLASS]::[NAME]()`  */
  public function compoundName(): string { return strtr($this->class->name, '\\', '.').'::__construct()'; }

  /**
   * Creates a new instance of the type this constructor belongs to
   *
   * @param  var[] $args
   * @param  ?string|?lang.XPClass|?lang.reflection.Type $context
   * @return object
   * @throws lang.reflection.InvocationFailed
   * @throws lang.reflection.CannotInstantiate
   */
  public function newInstance(array $args= [], $context= null) {
    try {
      $instance= $this->class->newInstanceWithoutConstructor();
    } catch (\ReflectionException $e) {
      throw new CannotInstantiate($this->class->name, $e);
    }

    if (null === $this->initializer) return $instance;
    try {
      $this->initializer->__invoke($instance, $args, $context);
      return $instance;
    } catch (\Throwable $e) {
      throw new InvocationFailed($this, $e);
    }
  }
}