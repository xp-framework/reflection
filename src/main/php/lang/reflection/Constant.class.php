<?php namespace lang\reflection;

use lang\{Type, Reflection};
use util\Objects;

/**
 * Reflection for a single constant
 *
 * @test lang.reflection.unittest.ConstantsTest
 */
class Constant extends Member {

  protected function meta() { return Reflection::meta()->constantAnnotations($this->reflect); }

  /**
   * Returns this constant's doc comment, or NULL if there is none.
   *
   * @return ?string
   */
  public function comment() { return Reflection::meta()->constantComment($this->reflect); }

  /** Returns a compound name consisting of `[CLASS]::$[NAME]`  */
  public function compoundName(): string { return strtr($this->reflect->class, '\\', '.').'::'.$this->reflect->name; }

  /** @return lang.reflection.Constraint */
  public function constraint() {
    $present= true;

    // Only use meta information if necessary
    $api= function($set) use(&$present) {
      $present= $set;
      return Reflection::meta()->constantType($this->reflect);
    };

    $t= Type::resolve(
      PHP_VERSION_ID >= 80300 ? $this->reflect->getType() : null,
      Member::resolve($this->reflect),
      $api
    );
    return new Constraint($t ?? Type::$VAR, $present);
  }

  /** @return var */
  public function value() { return $this->reflect->getValue(); }

  /** @return string */
  public function toString() {

    // Compile constant type
    $t= PHP_VERSION_ID >= 80300 ? $this->reflect->getType() : null;
    if (null === $t) {
      $type= Reflection::meta()->constantType($this->reflect) ?? 'var';
    } else if ($t instanceof ReflectionUnionType) {
      $type= '';
      foreach ($t->getTypes() as $component) {
        $type.= '|'.$component->getName();
      }
      $type= substr($type, 1);
    } else {
      $type= $t->getName();
    }

    return sprintf('%s const %s %s = %s',
      Modifiers::namesOf($this->reflect->getModifiers()),
      $type,
      $this->reflect->name,
      Objects::stringOf($this->reflect->getValue())
    );
  }
}