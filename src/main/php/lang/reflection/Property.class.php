<?php namespace lang\reflection;

use lang\{Reflection, XPClass, Type, TypeUnion};

class Property extends Member {

  protected function meta() { return Reflection::meta()->ofProperty($this->reflect); }

  /** Returns a compound name consisting of `[CLASS]::$[NAME]`  */
  public function compoundName(): string { return strtr($this->reflect->class, '\\', '.').'::$'.$this->reflect->name; }

  /**
   * Resolver handling `static`, `self` and `parent`.
   *
   * @return [:(function(string): lang.Type)]
   */
  protected function resolver() {
    return [
      'static' => function() { return new XPClass($this->reflect->class); },
      'self'   => function() { return new XPClass($this->reflect->getDeclaringClass()); },
      'parent' => function() { return new XPClass($this->reflect->getDeclaringClass()->getParentClass()); },
    ];
  }

  /** @return lang.reflection.TypeHint */
  public function constraint() {
    $t= PHP_VERSION_ID >= 70400 ? $this->reflect->getType() : null;
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

      // Check array and self for more specific types, e.g. `string[]`,
      // `static` or `function(): string` in api documentation
      if ('array' === $name) {
        $t= Type::$ARRAY;
      } else if ('self' === $name) {
        $t= new XPClass($this->reflect->getDeclaringClass());
      } else {
        return new TypeHint(Type::resolve($name, $this->resolver()));
      }
      $present= true;
    }

    $this->meta ?? $this->meta= Reflection::meta()->ofProperty($this->reflect);
    return new TypeHint(
      isset($this->meta[DETAIL_RETURNS]) ? Type::resolve($this->meta[DETAIL_RETURNS], $this->resolver()) : $t,
      $present
    );
  }

  /**
   * Gets this property's value
   *
   * @param  ?object $instance
   * @param  ?string|?lang.XPClass|?lang.reflection.Type $context
   * @return var
   * @throws lang.reflection.CannotAccess
   */
  public function get($instance, $context= null) {

    // Success oriented: Let PHP's reflection API raise the exceptions for us
    if ($context && !$this->reflect->isPublic()) {
      if (Reflection::of($context)->is($this->reflect->class)) {
        $this->reflect->setAccessible(true);
      }
    }

    try {
      return $this->reflect->getValue($instance);
    } catch (\ReflectionException $e) {
      throw new CannotAccess($this, $e);
    }
  }

  /**
   * Sets this property's value
   *
   * @param  ?object $instance
   * @param  var $value
   * @param  ?string|?lang.XPClass|?lang.reflection.Type $context
   * @return var The given value
   * @throws lang.reflection.CannotAccess
   * @throws lang.reflection.AccessFailed if setting raises an exception
   */
  public function set($instance, $value, $context= null) {

    // Success oriented: Let PHP's reflection API raise the exceptions for us
    if ($context && !$this->reflect->isPublic()) {
      if (Reflection::of($context)->is($this->reflect->class)) {
        $this->reflect->setAccessible(true);
      }
    }

    try {
      $this->reflect->setValue($instance, $value);
      return $value;
    } catch (\ReflectionException $e) {
      throw new CannotAccess($this, $e);
    } catch (\Throwable $e) {
      throw new AccessingFailed($this, $e);
    }
  }

  /** @return string */
  public function toString() {
    return Modifiers::namesOf($this->reflect->getModifiers()).' $'.$this->reflect->name;
  }
}