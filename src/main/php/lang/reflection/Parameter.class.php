<?php namespace lang\reflection;

use ReflectionMethod;
use lang\{Reflection, Type, IllegalStateException};

/**
 * Reflection for a method's or constructor's parameter
 *
 * @test lang.reflection.unittest.MethodsTest
 */
class Parameter implements Annotated {
  private $reflect, $holder;
  private $annotations= null;

  /**
   * Creates a new parameter
   *
   * @param  ReflectionParameter $reflect
   * @param  ReflectionFunctionAbstract $holder
   */
  public function __construct($reflect, $holder= null) {
    $this->reflect= $reflect;
    $this->holder= $holder ?? $reflect->getDeclaringFunction();
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
        $this->annotations ?? $this->annotations= $this->holder instanceof ReflectionMethod
          ? Reflection::meta()->parameterAnnotations($this->holder, $this->reflect)
          : Reflection::meta()->annotations->ofParameter($this->holder, $this->reflect)
        ;
        return $this->annotations['Default'][0] ?? null;
      }
      return $value;
    }

    throw new IllegalStateException('No default value avaible for parameter $'.$this->reflect->name);
  }

  /** @return lang.reflection.Annotations */
  public function annotations() {
    $this->annotations ?? $this->annotations= $this->holder instanceof ReflectionMethod
      ? Reflection::meta()->parameterAnnotations($this->holder, $this->reflect)
      : Reflection::meta()->annotations->ofParameter($this->holder, $this->reflect)
    ;
    return new Annotations($this->annotations);
  }

  /** @return ?lang.reflection.Annotation */
  public function annotation(string $type) {
    $this->annotations ?? $this->annotations= $this->holder instanceof ReflectionMethod
      ? Reflection::meta()->parameterAnnotations($this->holder, $this->reflect)
      : Reflection::meta()->annotations->ofParameter($this->holder, $this->reflect)
    ;

    $t= strtr($type, '.', '\\');
    return isset($this->annotations[$t]) ? new Annotation($t, $this->annotations[$t]) : null;
  }

  /** @return lang.reflection.Constraint */
  public function constraint() {
    $present= true;

    // Only use meta information if necessary
    if ($this->holder instanceof ReflectionMethod) {
      $resolve= Member::resolve($this->reflect);
      $api= function($set) use(&$present) {
        $present= $set;
        $names= Reflection::meta()->methodParameterTypes($this->holder);
        return $names[$this->reflect->getPosition()] ?? null;
      };
    } else {
      $resolve= [];
      $api= function($set) use(&$present) { $present= $set; return null; };
    }

    return new Constraint(
      Type::resolve($this->reflect->getType(), $resolve, $api) ?? Type::$VAR,
      $present
    );
  }
}