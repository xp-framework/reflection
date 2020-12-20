<?php namespace lang\reflection;

use lang\Value;

/**
 * Type and member modifiers
 *
 * @test lang.reflection.unittest.ModifiersTest
 */
class Modifiers implements Value {
  const IS_STATIC    = MODIFIER_STATIC;
  const IS_ABSTRACT  = MODIFIER_ABSTRACT;
  const IS_FINAL     = MODIFIER_FINAL;
  const IS_PUBLIC    = MODIFIER_PUBLIC;
  const IS_PROTECTED = MODIFIER_PROTECTED;
  const IS_PRIVATE   = MODIFIER_PRIVATE;
  const IS_NATIVE    = 0xF000;

  private static $names= [
    'public'    => self::IS_PUBLIC,
    'protected' => self::IS_PROTECTED,
    'private'   => self::IS_PRIVATE,
    'static'    => self::IS_STATIC,
    'final'     => self::IS_FINAL,
    'abstract'  => self::IS_ABSTRACT,
    'native'    => self::IS_NATIVE
  ];
  private $bits;

  /**
   * Creates a new modifiers instance
   *
   * @param  var $arg Either a number or a space-separated string, or an array with modifier names
   * @param  bool $visibility Whether to ensure a visibility modifier is present
   */
  public function __construct($arg= 0, $visibility= true) {
    if (is_string($arg)) {
      $this->bits= '' === $arg ? 0 : self::parse(explode(' ', $arg));
    } else if (is_array($arg)) {
      $this->bits= self::parse($arg);
    } else {
      $this->bits= (int)$arg;
    }

    if ($visibility && 0 === ($this->bits & (self::IS_PROTECTED | self::IS_PRIVATE))) {
      $this->bits |= self::IS_PUBLIC;
    }
  }

  /**
   * Parse names
   *
   * @param  string[] $names
   * @return int
   */
  private static function parse($names) {
    $bits= 0;
    foreach ($names as $name) {
      $bits |= self::$names[$name];
    }
    return $bits;
  }

  /** @return int */
  public function bits() { return $this->bits; }

  /**
   * Returns the modifier names as a string.
   *
   * @return string
   */
  public function names() {
    $names= '';
    foreach (self::$names as $name => $bit) {
      if ($this->bits & $bit) $names.= ' '.$name;
    }
    return substr($names, 1);
  }

  /**
   * Returns the modifier names as a string.
   *
   * @param  int $bits
   * @return string
   */
  public static function namesOf($bits) {
    $names= '';
    foreach (self::$names as $name => $bit) {
      if ($bits & $bit) $names.= ' '.$name;
    }
    return substr($names, 1);
  }

  /** @return bool */
  public function isStatic() { return 0 !== ($this->bits & self::IS_STATIC); }

  /** @return bool */
  public function isAbstract() { return 0 !== ($this->bits & self::IS_ABSTRACT); }

  /** @return bool */
  public function isFinal() { return 0 !== ($this->bits & self::IS_FINAL); }

  /** @return bool */
  public function isPublic() { return 0 !== ($this->bits & self::IS_PUBLIC); }

  /** @return bool */
  public function isProtected() { return 0 !== ($this->bits & self::IS_PROTECTED); }

  /** @return bool */
  public function isPrivate() { return 0 !== ($this->bits & self::IS_PRIVATE); }

  /** @return bool */
  public function isNative() { return 0 !== ($this->bits & self::IS_NATIVE); }

  /**
   * Compares a given value to this modifiers instance
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? $this->bits <=> $value->bits : 1;
  }

  /** @return string */
  public function hashCode() { return 'M['.$this->bits; }

  /** @return string */
  public function toString() { return nameof($this).'<'.$this->names().'>'; }

  /** @return string */
  public function __toString() { return $this->names(); }
}