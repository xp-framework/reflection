<?php namespace lang\reflection;

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
    try {

      // Workaround for non-public constructors: Set accessible, then manually
      // invoke after creating an instance without invoking the constructor.
      if ($context && !$this->reflect->isPublic()) {
        if (Reflection::of($context)->is($this->class->name)) {
          $instance= $this->class->newInstanceWithoutConstructor();
          $this->reflect->setAccessible(true);
          $this->reflect->invokeArgs($instance, $args);
          return $instance;
        }
      }

      return $this->class->newInstanceArgs($args);
    } catch (\ReflectionException $e) {
      throw new CannotInstantiate($this->class->name, $e);
    } catch (\Throwable $e) {
      throw new InvocationFailed($this, $e);
    }
  }
}