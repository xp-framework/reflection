<?php namespace lang\reflection;

use ArgumentCountError, ReflectionException, ReflectionUnionType, ReflectionIntersectionType, Throwable, TypeError, Error;
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
  public function closure(?object $instance= null) {
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
   * @return var
   * @throws lang.reflection.CannotInvoke if prerequisites to the invocation fail
   * @throws lang.reflection.InvocationFailed if invocation raises an exception
   */
  public function invoke(?object $instance, $args= []) {
    try {
      $pass= PHP_VERSION_ID < 80000 && $args ? self::pass($this->reflect, $args) : $args;

      // TODO: Remove superfluous call to setAccessible() if on PHP8.1+
      // see https://wiki.php.net/rfc/make-reflection-setaccessible-no-op
      PHP_VERSION_ID < 80100 && $this->reflect->setAccessible(true);
      return $this->reflect->invokeArgs($instance, $pass);
    } catch (ReflectionException|ArgumentCountError|TypeError $e) {
      throw new CannotInvoke($this, $e);
    } catch (Throwable $e) {

      // This really should be an ArgumentCountError...
      if (0 === strpos($e->getMessage(), 'Unknown named parameter $')) {
        throw new CannotInvoke($this, $e);
      }

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
      $returns= strtr($t->getName(), '\\', '.');
      $t->allowsNull() && $nullable= '?';
    }

    return 
      Modifiers::namesOf($this->reflect->getModifiers() & ~0x1fb7f008).
      ' function '.$this->reflect->name.'('.$this->signature($meta).'): '.
      $nullable.$returns
    ;
  }
}