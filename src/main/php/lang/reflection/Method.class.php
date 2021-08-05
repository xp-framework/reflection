<?php namespace lang\reflection;

use ReflectionException, ReflectionUnionType, ReflectionIntersectionType, Throwable;
use lang\{Reflection, TypeUnion, Type, XPClass, IllegalArgumentException};

/**
 * Reflection for a single method
 *
 * @test lang.reflection.unittest.MethodsTest
 * @test lang.reflection.unittest.InvocationTest
 */
class Method extends Routine {

  /**
   * Returns a closure
   *
   * @param  ?object $instance
   * @return Closure
   * @throws lang.IllegalArgumentException for incorrect or missing instances
   */
  public function closure($instance= null) {
    try {
      $closure= $this->reflect->getClosure($instance);
    } catch (\Throwable $e) {
      throw new IllegalArgumentException($e->getMessage());
    }

    // PHP 7.x generates warnings and returns NULL instead of throwing an
    // exception from ReflectionMethod::getClosure()
    //
    // @codeCoverageIgnoreStart
    if (null === $closure) {
      $e= new IllegalArgumentException('Cannot get closure');
      \xp::gc(__FILE__);
      throw $e;
    }
    // @codeCoverageIgnoreEnd

    return $closure;
  }

  /**
   * Invokes this method
   *
   * @param  ?object $instance
   * @param  var[] $args
   * @param  ?string|?lang.XPClass|?lang.reflection.Type $context
   * @return var
   * @throws lang.reflection.CannotInvoke
   * @throws lang.reflection.InvocationFailed if invocation raises an exception
   */
  public function invoke($instance, $args= [], $context= null) {

    // Only allow invoking non-public methods when given a compatible context
    if (!$this->reflect->isPublic()) {
      if ($context && Reflection::of($context)->is($this->reflect->class)) {
        $this->reflect->setAccessible(true);
      } else {
        throw new CannotInvoke($this, new ReflectionException('Trying to invoke non-public method'));
      }
    }

    try {
      return $this->reflect->invokeArgs($instance, $args);
    } catch (ReflectionException $e) {
      throw new CannotInvoke($this, $e);
    } catch (Throwable $e) {
      throw new InvocationFailed($this, $e);
    }
  }

  /** @return lang.reflection.Constraint */
  public function returns() {
    $present= true;

    // Only use meta information if necessary
    $api= function($set) use(&$present) {
      $present= $set;
      return Reflection::meta()->methodReturns($this->reflect);
    };

    $t= Type::resolve($this->reflect->getReturnType(), Member::resolve($this->reflect), $api);
    return new Constraint($t ?? Type::$VAR, $present);
  }

  /** @return string */
  public function toString() {
    $meta= Reflection::meta();

    // Put together return type
    $t= $this->reflect->getReturnType();
    $nullable= '';
    if (null === $t) {
      $returns= $meta->methodReturns($this->reflect) ?? 'var';
    } else if ($t instanceof ReflectionUnionType) {
      $name= '';
      foreach ($t->getTypes() as $component) {
        if ('null' === ($c= $component->getName())) {
          $nullable= '?';
        } else {
          $name.= '|'.$c;
        }
      }
      $returns= substr($name, 1);
    } else if ($t instanceof ReflectionIntersectionType) {
      $name= '';
      foreach ($t->getTypes() as $component) {
        $name.= '&'.$component->getName();
      }
      $returns= substr($name, 1);
    } else {
      $returns= strtr(PHP_VERSION_ID >= 70100 ? $t->getName() : $t->__toString(), '\\', '.');
      $t->allowsNull() && $nullable= '?';
    }

    return 
      Modifiers::namesOf($this->reflect->getModifiers() & ~0x1fb7f008).
      ' function '.$this->reflect->name.'('.$this->signature($meta).'): '.
      $nullable.$returns
    ;
  }
}