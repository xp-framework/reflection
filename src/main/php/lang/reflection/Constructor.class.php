<?php namespace lang\reflection;

use ArgumentCountError, ReflectionMethod, ReflectionClass, ReflectionException, TypeError, Error, Throwable;
use lang\Reflection;

/**
 * Reflection for a type's constructor
 *
 * @test lang.reflection.unittest.ConstructorTest
 * @test lang.reflection.unittest.InstantiationTest
 */
class Constructor extends Routine implements Instantiation {
  private $class;

  /** @param ReflectionClass $reflect */
  public function __construct($reflect) {
    parent::__construct($reflect->getMethod('__construct'));
    $this->class= $reflect;
  }

  /** @return string */
  public function toString() {
    return
      Modifiers::namesOf($this->reflect->getModifiers() & ~0x1fb7f008).
      ' function __construct('.$this->signature(Reflection::meta()).')'
    ;
  }

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

    // Support named arguments for PHP 7.X
    if (PHP_VERSION_ID < 80000 && is_string(key($args))) {
      $pass= [];
      foreach ($this->reflect->getParameters() as $param) {
        $pass[]= $args[$param->name] ?? ($param->isOptional() ? $param->getDefaultValue() : null);
        unset($args[$param->name]);
      }
      if ($args) {
        throw new CannotInstantiate($this->class->name, new Error('Unknown named parameter $'.key($args)));
      }
    } else {
      $pass= $args;
    }

    try {

      // Workaround for non-public constructors: Set accessible, then manually
      // invoke after creating an instance without invoking the constructor.
      if ($context && !$this->reflect->isPublic()) {
        if (Reflection::of($context)->is($this->class->name)) {
          $instance= $this->class->newInstanceWithoutConstructor();
          $this->reflect->setAccessible(true);
          $this->reflect->invokeArgs($instance, $pass);
          return $instance;
        }
      }

      return $this->class->newInstanceArgs($pass);
    } catch (ArgumentCountError $e) {
      throw new CannotInstantiate($this->class->name, $e);
    } catch (TypeError $e) {
      throw new CannotInstantiate($this->class->name, $e);
    } catch (ReflectionException $e) {
      throw new CannotInstantiate($this->class->name, $e);
    } catch (Throwable $e) {

      // This really should be an ArgumentCountError...
      if (0 === strpos($e->getMessage(), 'Unknown named parameter $')) {
        throw new CannotInstantiate($this->class->name, $e);
      }

      throw new InvocationFailed($this, $e);
    }
  }
}