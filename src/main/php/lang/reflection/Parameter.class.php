<?php namespace lang\reflection;

use lang\{Reflection, Type, IllegalStateException};

/**
 * Reflection for a method's or constructor's parameter
 *
 * @test lang.reflection.unittest.MethodsTest
 */
class Parameter {
  private $reflect, $method;
  private $annotations= null;

  /**
   * Creates a new parameter
   *
   * @param  ReflectionParameter $reflect
   * @param  ReflectionMethod $method
   */
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
   * Returns an optional parameter's default value. Additionally checks `default`
   * parameter annotation in XP meta information if available.
   * 
   * @return var
   * @throws lang.IllegalStateException if not default value is available
   */
  public function default() {
    if ($this->reflect->isDefaultValueAvailable()) {
      $value= $this->reflect->getDefaultValue();
      if (null === $value) {
        $class= strtr($this->reflect->getDeclaringClass()->name, '\\', '.');
        return \xp::$meta[$class][1][$this->method->name][DETAIL_TARGET_ANNO]['$'.$this->reflect->name]['default'] ?? null;
      }
      return $value;
    }

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

    return new Constraint(
      Type::resolve($this->reflect->getType(), Member::resolve($this->reflect), $api) ?? Type::$VAR,
      $present
    );
  }
}