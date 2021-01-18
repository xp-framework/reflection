<?php namespace lang\reflection\unittest;

use lang\{ClassLoader, Reflection};

trait TypeDefinition {

  /**
   * Defines a type
   *
   * @param  var $declaration
   * @param  var $annotations
   * @param  [:string] $imports
   * @return lang.reflection.Type
   */
  private function declare($declaration, $annotations= null, $imports= []) {
    static $u= 0;

    return Reflection::of(ClassLoader::defineType(
      ($annotations ? $annotations.' ' : '').self::class.'Parent'.($u++),
      ['kind' => 'class', 'extends' => null, 'implements' => [], 'use' => [], 'imports' => $imports],
      $declaration
    ));
  }

  /**
   * Extends a type
   *
   * @param  lang.reflection.Type $base
   * @param  var $declaration
   * @param  var $annotations
   * @return lang.reflection.Type
   */
  private function extend($base, $declaration= [], $annotations= null) {
    static $u= 0;

    return Reflection::of(ClassLoader::defineType(
      ($annotations ? $annotations.' ' : '').self::class.'Child'.($u++),
      ['kind' => 'class', 'extends' => [$base], 'implements' => [], 'use' => []],
      $declaration
    ));
  }
}