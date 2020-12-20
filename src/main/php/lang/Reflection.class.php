<?php namespace lang;

use lang\meta\{MetaInformation, FromSyntaxTree, FromAttributes};
use lang\reflection\Type;
use lang\{ClassLoader, ClassNotFoundException};

/**
 * Factory for reflection instances.
 *
 * ```php
 * $type= Reflection::of(Runnable::class);
 * $type= Reflection::of($instance);
 * $type= Reflection::of(XPClass::forName('lang.Value'));
 * ```
 *
 * @test lang.reflection.unittest.ReflectionTest
 */
abstract class Reflection {
  private static $meta= null;

  public static function annotations($version) {
    return $version >= 80000 ? new FromAttributes() : new FromSyntaxTree();
  }

  /** @return lang.annotations.FromMeta */
  public static function meta() {
    return self::$meta ?? self::$meta= new MetaInformation(self::annotations(PHP_VERSION_ID));
  }

  /**
   * Creates a new reflection instance
   *
   * @param  string|lang.XPClass|lang.reflection.Type|ReflectionClass|object $arg
   * @return lang.reflection.Type
   * @throws lang.ClassNotFoundException
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

      // Instantiatin ReflectionClass triggers autoloading mechanism, which in turn
      // uses the default class loader to locate the class.
      try {
        return new Type(new \ReflectionClass(strtr($arg, '.', '\\')));
      } catch (\ReflectionException $e) {
        throw new ClassNotFoundException(strtr($arg, '\\', '.'), [ClassLoader::getDefault()]);
      }
    }
  }
}