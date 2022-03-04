<?php namespace xp\reflection;

use lang\{Enum, Reflection};

abstract class Information extends Enum {
  const ALL = 0x01;
  const DOC = 0x02; 

  public static $CLASS, $INTERFACE, $TRAIT, $ENUM;
  
  static function __static() {
    self::$CLASS= newinstance(self::class, [0, 'class'], '{
      static function __static() { }
      public function newInstance($type, $flags) { return new ClassInformation($type, $flags); }
    }');
    self::$INTERFACE= newinstance(self::class, [1, 'interface'], '{
      static function __static() { }
      public function newInstance($type, $flags) { return new InterfaceInformation($type, $flags); }
    }');
    self::$TRAIT= newinstance(self::class, [2, 'trait'], '{
      static function __static() { }
      public function newInstance($type, $flags) { return new TraitInformation($type, $flags); }
    }');
    self::$ENUM= newinstance(self::class, [3, 'enum'], '{
      static function __static() { }
      public function newInstance($type, $flags) { return new EnumInformation($type, $flags); }
    }');
  }

  public abstract function newInstance($type, $flags);

  /**
   * Factory for information based on a type
   *
   * @param  lang.XPClass $class
   * @param  int $flags
   * @return xp.reflection.TypeInformation
   */
  public static function forClass($class, $flags) {
    $type= Reflection::of($class);
    return Enum::valueOf(self::class, strtoupper($type->kind()->name()))->newInstance($type, $flags);
  }

  /**
   * Factory for information based on a package name
   *
   * @param  string $name
   * @param  int $flags
   * @return xp.reflection.PackageInformation
   */
  public static function forPackage($name, $flags) {
    return new PackageInformation($name, $flags);
  }

  /**
   * Factory for information based on a directory
   *
   * @param  string $name
   * @param  int $flags
   * @return xp.reflection.DirectoryInformation
   */
  public static function forDirectory($name, $flags) {
    return new DirectoryInformation($name, $flags);
  }
}