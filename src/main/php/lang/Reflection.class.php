<?php namespace lang;

use lang\meta\{MetaInformation, FromSyntaxTree, FromAttributes};
use lang\reflection\{Type, Package};
use lang\{ClassLoader, ClassNotFoundException, IllegalArgumentException};

/**
 * Factory for reflection instances.
 *
 * ```php
 * // Types can be instantiated by names, instances or via lang.XPClass
 * $type= Reflection::of(Runnable::class);
 * $type= Reflection::of($instance);
 * $type= Reflection::of(XPClass::forName('lang.Value'));
 *
 * // Packages can be instantiated via their name
 * $package= Reflection::of('lang.reflection');
 * ```
 *
 * @test lang.reflection.unittest.ReflectionTest
 */
abstract class Reflection {
  private static $meta= null;

  public static function annotations($version) {
    return $version >= 80000 ? new FromAttributes() : new FromSyntaxTree();
  }

  /** Lazy-loads meta information extraction */
  public static function meta(): MetaInformation {
    return self::$meta ?? self::$meta= new MetaInformation(self::annotations(PHP_VERSION_ID));
  }

  /**
   * Returns a reflection type for a given argument.
   *
   * @param  string|object|lang.XPClass|lang.reflection.Type|ReflectionClass $arg
   * @return lang.reflection.Type
   * @throws lang.ClassNotFoundException
   */
  public static function type($arg) {
    if ($arg instanceof XPClass) {
      return new Type($arg->reflect());
    } else if ($arg instanceof \ReflectionClass) {
      return new Type($arg);
    } else if ($arg instanceof Type) {
      return $arg;
    } else if (is_object($arg)) {
      return new Type(new \ReflectionObject($arg));
    } else {
      try {
        return new Type(new \ReflectionClass(strtr($arg, '.', '\\')));
      } catch (\ReflectionException $e) {
        throw new ClassNotFoundException($arg, [ClassLoader::getDefault()]);
      }
    }
  }

  /**
   * Returns a reflection package for a given argument.
   *
   * @param  string|object|lang.XPClass|lang.reflection.Type|ReflectionClass $arg
   * @return lang.reflection.Type
   * @throws lang.IllegalArgumentException
   */
  public static function package($arg) {
    if ($arg instanceof XPClass) {
      return new Package($arg->getPackage()->getName());
    } else if ($arg instanceof \ReflectionClass) {
      return new Package($arg->getNamespaceName());
    } else if ($arg instanceof Type) {
      return $arg->package();
    } else if (is_object($arg)) {
      $class= get_class($arg);
      return new Package(substr($class, 0, strrpos($class, '\\')));
    } else {
      $cl= ClassLoader::getDefault();
      $name= strtr($arg, '\\', '.');
      if ($cl->providesPackage($name)) {
        return new Package($name);
      }
      throw new IllegalArgumentException('No package named '.$name);
    }
  }

  /**
   * Creates a new reflection instance, which may either refer to a type
   * or to a package.
   *
   * @param  string|object|lang.XPClass|lang.reflection.Type|ReflectionClass $arg
   * @return lang.reflection.Type|lang.reflection.Package
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
      $cl= ClassLoader::getDefault();
      $name= strtr($arg, '\\', '.');
      if ($cl->providesClass($name)) {
        return new Type(new \ReflectionClass($cl->loadClass0($name)));
      } else if ($cl->providesPackage($name)) {
        return new Package($name);
      }

      try {
        return new Type(new \ReflectionClass(strtr($arg, '.', '\\')));
      } catch (\ReflectionException $e) {
        throw new ClassNotFoundException($name, [$cl]);
      }
    }
  }
}