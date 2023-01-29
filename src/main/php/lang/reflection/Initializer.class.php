<?php namespace lang\reflection;

use ArgumentCountError, Error, ReflectionException, Throwable, TypeError;
use lang\Reflection;

/**
 * Reflection for a type initializer function
 *
 * @test lang.reflection.unittest.InstantiationTest
 */
class Initializer extends Routine implements Instantiation {
  private static $NOOP;
  private $class, $function;

  static function __static() {
    self::$NOOP= new \ReflectionFunction(function() { });
  }

  /**
   * Creates a new instantiation function
   *
   * @param  ReflectionClass $class
   * @param  ?ReflectionFunctionAbstract $reflect
   * @param  ?Closure $function
   */
  public function __construct($class, $reflect= null, $function= null) {
    parent::__construct($reflect ?? self::$NOOP);
    $this->class= $class;
    $this->function= $function;
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
    } catch (ReflectionException $e) {
      throw new CannotInstantiate($this->class->name, $e);
    }

    if (null === $this->function) return $instance;

    try {
      $pass= PHP_VERSION_ID < 80000 && $args ? Routine::pass($this->reflect, $args) : $args;
      $this->function->__invoke($instance, $pass, $context);
      return $instance;
    } catch (ArgumentCountError $e) {
      throw new CannotInstantiate(new Type($this->class), $e);
    } catch (TypeError $e) {
      throw new CannotInstantiate(new Type($this->class), $e);
    } catch (ReflectionException $e) {
      throw new CannotInstantiate(new Type($this->class), $e);
    } catch (Throwable $e) {

      // This really should be an ArgumentCountError...
      if (0 === strpos($e->getMessage(), 'Unknown named parameter $')) {
        throw new CannotInstantiate(new Type($this->class), $e);
      }

      throw new InvocationFailed($this, $e);
    }
  }
}