<?php namespace xp\reflection;

use lang\{Enum, Reflection};

abstract class Information extends Enum {
  public static $CLASS, $INTERFACE, $TRAIT, $ENUM;
  
  static function __static() {
    self::$CLASS= newinstance(self::class, [0, 'class'], '{
      static function __static() { }
      public function newInstance($type) { return new ClassInformation($type); }
    }');
    self::$INTERFACE= newinstance(self::class, [1, 'interface'], '{
      static function __static() { }
      public function newInstance($type) { return new InterfaceInformation($type); }
    }');
    self::$TRAIT= newinstance(self::class, [2, 'trait'], '{
      static function __static() { }
      public function newInstance($type) { return new TraitInformation($type); }
    }');
    self::$ENUM= newinstance(self::class, [3, 'enum'], '{
      static function __static() { }
      public function newInstance($type) { return new EnumInformation($type); }
    }');
  }

  public abstract function newInstance($type);

  /**
   * Factory for information based on a type
   *
   * @param  lang.XPClass $class
   * @return self
   */
  public static function forClass($class) {
    $type= Reflection::of($class);
    return Enum::valueOf(self::class, strtoupper($type->kind()->name()))->newInstance($type);
  }
}