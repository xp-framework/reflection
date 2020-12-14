<?php namespace lang;

use lang\meta\{Cached, FromSyntaxTree, FromAttributes};
use lang\reflection\Type;

abstract class Reflection {
  private static $meta= null;

  public static function parse($version) {
    return $version >= 80000 ? new FromAttributes() : new FromSyntaxTree();
  }

  /** @return lang.annotations.FromMeta */
  public static function meta() {
    return self::$meta ?? self::$meta= new Cached(self::parse(PHP_VERSION_ID));
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