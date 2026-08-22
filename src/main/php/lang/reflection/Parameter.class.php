<?php namespace lang\reflection;

use lang\{Reflection, Type, IllegalStateException, Value};

/**
 * Reflection for a method's or constructor's parameter
 *
 * @test lang.reflection.unittest.MethodsTest
 */
class Parameter implements Annotated, Value {
  private $reflect, $resolve, $method;
  private $annotations= null;

  /**
   * Creates a new parameter
   *
   * @param  ReflectionParameter $reflect
   * @param  [:function(?string): Type] $resolve
   * @param  ReflectionMethod $method
   */
  public function __construct($reflect, $resolve, $method= null) {
    $this->reflect= $reflect;
    $this->resolve= $resolve;
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
        $this->annotations ?? $this->annotations= Reflection::meta()->parameterAnnotations($this->method, $this->reflect);
        return $this->annotations['Default'][0] ?? null;
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
      Type::resolve($this->reflect->getType(), $this->resolve, $api) ?? Type::$VAR,
      $present
    );
  }

  /** @return string */
  public function toString() {
    return nameof($this).'<'.$this->reflect->name.'>';
  }

  /** @return string */
  public function hashCode() {
    return 'P'.Objects::hashOf([$this->method->name, $this->reflect->name]);
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? $this->reflect <=> $value->reflect : 1;
  }
}