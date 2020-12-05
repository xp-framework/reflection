<?php namespace lang\reflection\unittest;

use lang\{ClassLoader, Reflection};

trait TypeDefinition {

  /**
   * Defines a type
   *
   * @param  var $declaration
   * @param  var $annotations
   * @return lang.reflection.Type
   */
  private function type($declaration, $annotations= null) {
    static $i= 0;

    $type= ClassLoader::defineType(
      ($annotations ? $annotations.' ' : '').self::class.($i++),
      ['kind' => 'class', 'extends' => null, 'implements' => [], 'use' => []],
      $declaration
    );
    return Reflection::of($type);
  }
}