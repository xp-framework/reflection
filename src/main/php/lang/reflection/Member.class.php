<?php namespace lang\reflection;

use lang\{XPClass, Reflection, Value};

abstract class Member implements Value {
  protected $reflect, $annotations;

  /** @param ReflectionClass|ReflectionProperty|ReflectionClassConstant $reflect */
  public function __construct($reflect, $annotations= null) {
    $this->reflect= $reflect;
    $this->annotations= $annotations;
  }

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

  /** @return [:var] */
  protected abstract function getAnnotations();

  /** @return lang.reflection.Annotations */
  public function annotations() {
    $this->annotations ?? $this->annotations= $this->getAnnotations();
    return new Annotations($this->annotations);
  }

  /** @return ?lang.reflection.Annotation */
  public function annotation(string $type) {
    $this->annotations ?? $this->annotations= $this->getAnnotations();

    $t= strtr($type, '.', '\\');
    return isset($this->annotations[$t]) ? new Annotation($t, $this->annotations[$t]) : null;
  }

  /** Returns this member's name */
  public function name(): string { return $this->reflect->name; }

  /** Returns a compound name   */
  public abstract function compoundName(): string;

  /** @return lang.reflection.Modifiers */
  public function modifiers() {

    // Note: ReflectionMethod::getModifiers() returns whatever PHP reflection 
    // returns, but the numeric value changed since 5.0.0 as the zend_function
    // struct's fn_flags now contains not only ZEND_ACC_(PPP, STATIC, FINAL,
    // ABSTRACT) but also some internal information about how this method needs
    // to be called.
    //
    // == List of fn_flags we don't want to return from this method ==
    // #define ZEND_ACC_IMPLEMENTED_ABSTRACT   0x08
    // #define ZEND_ACC_IMPLICIT_PUBLIC        0x1000
    // #define ZEND_ACC_CTOR                   0x2000
    // #define ZEND_ACC_DTOR                   0x4000
    // #define ZEND_ACC_CLONE                  0x8000
    // #define ZEND_ACC_ALLOW_STATIC           0x10000
    // #define ZEND_ACC_SHADOW                 0x20000
    // #define ZEND_ACC_DEPRECATED             0x40000
    // #define ZEND_ACC_IMPLEMENT_INTERFACES   0x80000
    // #define ZEND_ACC_CLOSURE                0x100000
    // #define ZEND_ACC_CALL_VIA_HANDLER       0x200000
    // #define ZEND_ACC_IMPLEMENT_TRAITS       0x400000
    // #define ZEND_HAS_STATIC_IN_METHODS      0x800000
    // #define ZEND_ACC_PASS_REST_BY_REFERENCE 0x1000000
    // #define ZEND_ACC_PASS_REST_PREFER_REF   0x2000000
    // #define ZEND_ACC_RETURN_REFERENCE       0x4000000
    // #define ZEND_ACC_DONE_PASS_TWO          0x8000000
    // #define ZEND_ACC_HAS_TYPE_HINTS         0x10000000
    return new Modifiers($this->reflect->getModifiers() & ~0x1fb7f008);
  }

  /** Returns the type this member is declared in */
  public function declaredIn(): Type { return new Type($this->reflect->getDeclaringClass()); }

  /** @return string */
  public function hashCode() { return $this->compoundName(); }

  /** @return string */
  public abstract function toString();

  /**
   * Compares this member to another value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    if ($value instanceof self) {
      $r= $this->reflect->class <=> $value->reflect->class;
      return 0 === $r ? $this->reflect->name <=> $value->reflect->name : $r;
    }
    return 1;
  }
}