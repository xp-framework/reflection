<?php namespace lang\reflection;

use lang\Enum;

/**
 * Value type kind enumeration
 *
 * @see  lang.reflection.Type::kind()
 */
class Kind extends Enum {
  public static $INTERFACE, $TRAIT, $CLASS, $ENUM;

  static function __static() {
    self::$CLASS= new self(0, 'class');
    self::$INTERFACE= new self(1, 'interface');
    self::$TRAIT= new self(2, 'trait');
    self::$ENUM= new self(3, 'enum');
  }
}