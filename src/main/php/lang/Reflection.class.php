<?php namespace lang;

use lang\annotations\{FromMeta, FromSyntaxTree, FromAttributes};
use lang\reflection\Type;

abstract class Reflection {
  private static $parse= null;

  /** @return lang.annotations.FromMeta */
  public static function parse() {
    if (self::$parse) {
      // NOOP
    } else if (PHP_VERSION_ID >= 80000) {
      self::$parse= new FromMeta(new FromAttributes());
    } else {
      self::$parse= new FromMeta(new FromSyntaxTree());
    }

    return self::$parse;
  }

  /**
   * Creates a new reflection instance
   *
   * @param  string|lang.XPClass|lang.reflection.Type|ReflectionClass|object $arg
   * @return lang.reflection.Type
   */
  public static function of($arg) {
    if ($arg instanceof XPClass) {
      return new Type($arg->reflect());
    } else if ($arg instanceof \ReflectionClass) {
      return new Type($arg);
    } else if ($arg instanceof Type) {
      return $arg;
    } else if (is_object($arg)) {
      return new Type(new \ReflectionObject($arg));
    } else {
      return new Type(new \ReflectionClass(strtr($arg, '.', '\\')));
    }
  }
}