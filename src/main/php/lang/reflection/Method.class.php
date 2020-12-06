<?php namespace lang\reflection;

use lang\{Reflection, Type, TypeUnion, XPClass};

class Method extends Member {

  /** @return [:var] */
  protected function getAnnotations() { return Reflection::parse()->ofMethod($this->reflect); }

  /**
   * Invokes this method
   *
   * @param  ?object $instance
   * @param  var[] $args
   * @param  ?string $context
   * @return var
   * @throws lang.reflection.CannotInvoke
   * @throws lang.reflection.InvocationFailed if invocation raises an exception
   */
  public function invoke($instance, $args, $context= null) {

    // TODO: Verify context is an instance of class this method is declared in
    if ($context) {
      $this->reflect->setAccessible(true);
    }

    try {
      return $this->reflect->invokeArgs($instance, $args);
    } catch (\ReflectionException $e) {
      throw new CannotInvoke(strtr($this->reflect->class, '\\', '.').'::'.$this->reflect->name, $e);
    } catch (\Throwable $e) {
      throw new InvocationFailed(strtr($this->reflect->class, '\\', '.').'::'.$this->reflect->name, $e);
    }
  }

  /** @return lang.reflection.TypeHint */
  public function returns() {
    $t= $this->reflect->getReturnType();
    if (null === $t) {
      $present= false;

      // Check for type in api documentation, defaulting to `var`
      $t= Type::$VAR;
    } else if ($t instanceof \ReflectionUnionType) {
      $union= [];
      foreach ($t->getTypes() as $component) {
        $union[]= Type::resolve($component->getName(), $this->resolver());
      }
      return new TypeHint(new TypeUnion($union));
    } else {
      $name= PHP_VERSION_ID >= 70100 ? $t->getName() : $t->__toString();

      // Check array, self and callable for more specific types, e.g. `string[]`,
      // `static` or `function(): string` in api documentation
      if ('array' === $name) {
        $t= Type::$ARRAY;
      } else if ('callable' === $name) {
        $t= Type::$CALLABLE;
      } else if ('self' === $name) {
        $t= new XPClass($this->reflect->getDeclaringClass());
      } else {
        return new TypeHint(Type::resolve($name, $this->resolver()));
      }
      $present= true;
    }

    // Parse apidoc. FIXME!
    preg_match_all('/@(return|param)\s+(.+)/', $this->reflect->getDocComment(), $matches, PREG_SET_ORDER);
    $tags= [];
    foreach ($matches as $match) {
      $tags[$match[1]][]= rtrim($match[2], ' */');
    }

    return new TypeHint(
      isset($tags['return'])? Type::resolve($tags['return'][0], $this->resolver()) : $t,
      $present
    );
  }

  /** @return string */
  public function toString() {

    // Parse apidoc. FIXME!
    preg_match_all('/@(return|param)\s+(.+)/', $this->reflect->getDocComment(), $matches, PREG_SET_ORDER);
    $tags= [];
    foreach ($matches as $match) {
      $tags[$match[1]][]= $match[2];
    }

    // Compile signature
    $sig= '';
    foreach ($this->reflect->getParameters() as $i => $parameter) {
      if ($t= $parameter->getType()) {
        $type= strtr($t->getName(), '\\', '.').($parameter->isVariadic() ? '...' : '');
      } else if (isset($tags['param'][$i])) {
        preg_match('/([^ ]+)( \$?[a-z_]+)/i', $tags['param'][$i], $matches);
        $type= $matches[1] ?? $tags['param'][$i];
      } else {
        $type= 'var';
      }
      $sig.= ', '.$type.' $'.$parameter->name;
    }

    // Put together return type
    if ($t= $this->reflect->getReturnType()) {
      $returns= strtr($t->getName(), '\\', '.');
    } else if (isset($tags['return'][0])) {
      preg_match('/([^ ]+)( .+)?/i', $tags['return'][0], $matches);
      $returns= $matches[1] ?? $tags['return'][0];
    } else {
      $returns= 'var';
    }

    return 
      Modifiers::namesOf($this->reflect->getModifiers()).
      ' function '.$this->reflect->name.'('.substr($sig, 2).'): '.
      $returns
    ;
  }
}