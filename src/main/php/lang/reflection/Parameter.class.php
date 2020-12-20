<?php namespace lang\reflection;

use lang\{Reflection, Type, TypeUnion, XPClass, IllegalStateException};

/**
 * Reflection for a method's or constructor's parameter
 *
 * @test lang.reflection.unittest.MethodsTest
 */
class Parameter {
  private $reflect, $method;
  private $annotations= null;

  public function __construct($reflect, $method= null) {
    $this->reflect= $reflect;
    $this->method= $method ?? $reflect->getDeclaringFunction();
  }

  /** Returns parameter name */
  public function name(): string { return $this->reflect->name; }

  /** Returns parameter position, starting at 0 */
  public function position(): int { return $this->reflect->getPosition(); }

  /** Returns whether this parameter accepts varargs */
  public function variadic() { return $this->reflect->isVariadic(); }

  /** Returns whether this parameter can be omitted */
  public function optional() { return $this->reflect->isOptional(); }

  /** 
   * Returns an optional parameter's default value
   * 
   * @return var
   * @throws lang.IllegalStateException if not default value is available
   */
  public function default() {
    if ($this->reflect->isDefaultValueAvailable()) return $this->reflect->getDefaultValue();

    throw new IllegalStateException('No default value avaible for parameter $'.$this->reflect->name);
  }

  /** @return lang.reflection.Annotations */
  public function annotations() {
    $this->annotations ?? $this->annotations= Reflection::meta()->parameterAnnotations($this->method, $this->reflect);
    return new Annotations($this->annotations);
  }

  /** @return ?lang.reflection.Annotation */
  public function annotation(string $type) {
    $this->annotations ?? $this->annotations= Reflection::meta()->parameterAnnotations($this->method, $this->reflect);

    $t= strtr($type, '.', '\\');
    return isset($this->annotations[$t]) ? new Annotation($t, $this->annotations[$t]) : null;
  }

  /**
   * Resolver handling `static`, `self` and `parent`.
   *
   * @return [:(function(string): lang.Type)]
   */
  protected function resolver() {
    return [
      'static' => function() { return new XPClass($this->method->class); },
      'self'   => function() { return new XPClass($this->method->getDeclaringClass()); },
      'parent' => function() { return new XPClass($this->method->getDeclaringClass()->getParentClass()); },
    ];
  }

  /** @return lang.reflection.Constraint */
  public function constraint() {
    $t= $this->reflect->getType();
    if (null === $t) {
      $present= false;

      // Check for type in api documentation, defaulting to `var`
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
      // `static` or `function(): string` in api documentation
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
    $p= $this->reflect->getPosition();
    $names= Reflection::meta()->methodParameterTypes($this->method);
    return new Constraint(isset($names[$p]) ? Type::resolve($names[$p], $this->resolver()) : $t, $present);
  }
}