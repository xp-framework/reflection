<?php namespace lang\reflection;

use lang\{Reflection, TypeUnion, Type, XPClass, IllegalArgumentException};

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
      return $this->reflect->getClosure($instance);
    } catch (\Throwable $e) {
      throw new IllegalArgumentException($e->getMessage());
    }
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

    // Success oriented: Let PHP's reflection API raise the exceptions for us
    if ($context && !$this->reflect->isPublic()) {
      if (Reflection::of($context)->is($this->reflect->class)) {
        $this->reflect->setAccessible(true);
      }
    }

    try {
      return $this->reflect->invokeArgs($instance, $args);
    } catch (\ReflectionException $e) {
      throw new CannotInvoke($this, $e);
    } catch (\Throwable $e) {
      throw new InvocationFailed($this, $e);
    }
  }

  /** @return lang.reflection.Constraint */
  public function returns() {
    $t= $this->reflect->getReturnType();
    if (null === $t) {
      $present= false;

      // Check for type in meta information, defaulting to `var`
      $t= Type::$VAR;
    } else if ($t instanceof \ReflectionUnionType) {
      $union= [];
      foreach ($t->getTypes() as $component) {
        $union[]= Type::resolve($component->getName(), $this->resolver());
      }
      return new Constraint(new TypeUnion($union));
    } else {
      $name= PHP_VERSION_ID >= 70100 ? $t->getName() : $t->__toString();

      // Check array, self and callable for more specific types, e.g. `string[]`,
      // `static` or `function(): string` in meta information
      if ('array' === $name) {
        $t= Type::$ARRAY;
      } else if ('callable' === $name) {
        $t= Type::$CALLABLE;
      } else if ('self' === $name) {
        $t= new XPClass($this->reflect->getDeclaringClass());
      } else {
        return new Constraint(Type::resolve($name, $this->resolver()));
      }
      $present= true;
    }

    // Use meta information
    $name= Reflection::meta()->methodReturns($this->reflect);
    return new Constraint($name ? Type::resolve($name, $this->resolver()) : $t, $present);
  }

  /** @return string */
  public function toString() {
    $meta= Reflection::meta();

    // Put together return type
    $t= $this->reflect->getReturnType();
    if (null === $t) {
      $returns= $meta->methodReturns($this->reflect) ?? 'var';
    } else if ($t instanceof \ReflectionUnionType) {
      $name= '';
      foreach ($t->getTypes() as $component) {
        $name.= '|'.$component->getName();
      }
      $returns= substr($name, 1);
    } else {
      $returns= strtr(PHP_VERSION_ID >= 70100 ? $t->getName() : $t->__toString(), '\\', '.');
    }

    return 
      Modifiers::namesOf($this->reflect->getModifiers() & ~0x1fb7f008).
      ' function '.$this->reflect->name.'('.$this->signature($meta).'): '.
      $returns
    ;
  }
}