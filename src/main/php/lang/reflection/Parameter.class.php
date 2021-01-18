<?php namespace lang\reflection;

use lang\{Reflection, Type, XPClass, IllegalStateException};

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

  /** @return lang.reflection.Constraint */
  public function constraint() {
    $present= true;

    // Only use meta information if necessary
    $api= function($set) use(&$present, &$names) {
      $present= $set;
      $names= Reflection::meta()->methodParameterTypes($this->method);
      return $names[$this->reflect->getPosition()] ?? null;
    };

    // Resolve type references against declaring class
    $resolve= [
      'static' => function() { return new XPClass($this->method->class); },
      'self'   => function() { return new XPClass($this->method->getDeclaringClass()); },
      'parent' => function() { return new XPClass($this->method->getDeclaringClass()->getParentClass()); },
      '*'      => function($type) {
        $reflect= $this->method->getDeclaringClass();
        $imports= Reflection::meta()->scopeImports($reflect);
        return XPClass::forName($imports[$type] ?? $reflect->getNamespaceName().'\\'.$type);
      },
    ];

    return new Constraint(Type::resolve($this->reflect->getType(), $resolve, $api) ?? Type::$VAR, $present);
  }
}