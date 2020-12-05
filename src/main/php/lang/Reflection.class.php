<?php namespace lang;

use lang\annotations\{FromMeta, FromSyntaxTree};
use lang\reflection\Type;

abstract class Reflection {
  private static $parse= null;

  /** @return lang.annotations.FromMeta */
  public static function parse() {
    return self::$parse ?? self::$parse= new FromMeta(new FromSyntaxTree());
  }

  /**
   * Creates a new reflection instance
   *
   * @param  string|lang.XPClass|ReflectionClass|object $arg
   * @return lang.reflection.Type
   */
  public static function of($arg) {
    if ($arg instanceof XPClass) {
      return new Type($arg->reflect());
    } else if ($arg instanceof \ReflectionClass) {
      return new Type($arg);
    } else if (is_object($arg)) {
      return new Type(new \ReflectionObject($arg));
    } else {
      return new Type(new \ReflectionClass(strtr($arg, '.', '\\')));
    }
  }
}