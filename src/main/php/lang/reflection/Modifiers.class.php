<?php namespace lang\reflection;

use lang\{Value, IllegalArgumentException};

/**
 * Type and member modifiers
 *
 * @test lang.reflection.unittest.ModifiersTest
 */
class Modifiers implements Value {
  const IS_STATIC        = MODIFIER_STATIC;
  const IS_ABSTRACT      = MODIFIER_ABSTRACT;
  const IS_FINAL         = MODIFIER_FINAL;
  const IS_PUBLIC        = MODIFIER_PUBLIC;
  const IS_PROTECTED     = MODIFIER_PROTECTED;
  const IS_PRIVATE       = MODIFIER_PRIVATE;
  const IS_READONLY      = MODIFIER_READONLY;
  const IS_PUBLIC_SET    = 0x0400;
  const IS_PROTECTED_SET = 0x0800;
  const IS_PRIVATE_SET   = 0x1000;
  const IS_NATIVE        = 0x10000;

  private static $names= [
    'public'         => self::IS_PUBLIC,
    'protected'      => self::IS_PROTECTED,
    'private'        => self::IS_PRIVATE,
    'static'         => self::IS_STATIC,
    'final'          => self::IS_FINAL,
    'abstract'       => self::IS_ABSTRACT,
    'native'         => self::IS_NATIVE,
    'readonly'       => self::IS_READONLY,
    'public(set)'    => self::IS_PUBLIC_SET,
    'protected(set)' => self::IS_PROTECTED_SET,
    'private(set)'   => self::IS_PRIVATE_SET,
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
  public function isNative() { return 0 !== ($this->bits & self::IS_NATIVE); }

  /** @return bool */
  public function isReadonly() { return 0 !== ($this->bits & self::IS_READONLY); }

  /**
   * Gets whether these modifiers are public in regard to the specified hook
   *
   * @param  string $hook
   * @return bool
   * @throws lang.IllegalArgumentException
   */
  public function isPublic($hook= 'get') {
    switch ($hook) {
      case 'get': return 0 !== ($this->bits & self::IS_PUBLIC);
      case 'set': return 0 !== ($this->bits & self::IS_PUBLIC_SET);
      default: throw new IllegalArgumentException('Unknown hook '.$hook);
    }
  }

  /**
   * Gets whether these modifiers are protected in regard to the specified hook
   *
   * @param  string $hook
   * @return bool
   * @throws lang.IllegalArgumentException
   */
  public function isProtected($hook= 'get') {
    switch ($hook) {
      case 'get': return 0 !== ($this->bits & self::IS_PROTECTED);
      case 'set': return 0 !== ($this->bits & self::IS_PROTECTED_SET);
      default: throw new IllegalArgumentException('Unknown hook '.$hook);
    }
  }

  /**
   * Gets whether these modifiers are private in regard to the specified hook
   *
   * @param  string $hook
   * @return bool
   * @throws lang.IllegalArgumentException
   */
  public function isPrivate($hook= 'get') {
    switch ($hook) {
      case 'get': return 0 !== ($this->bits & self::IS_PRIVATE);
      case 'set': return 0 !== ($this->bits & self::IS_PRIVATE_SET);
      default: throw new IllegalArgumentException('Unknown hook '.$hook);
    }
  }

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