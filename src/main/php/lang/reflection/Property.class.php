<?php namespace lang\reflection;

use ReflectionException, ReflectionProperty, ReflectionUnionType, Throwable;
use lang\{Reflection, XPClass, Type, VirtualProperty, TypeUnion};

/**
 * Reflection for a single property
 *
 * @test lang.reflection.unittest.PropertiesTest
 */
class Property extends Member {
  private static $HOOKS;

  static function __static() {
    self::$HOOKS= method_exists(ReflectionProperty::class, 'getHooks');
  }

  protected function meta() { return Reflection::meta()->propertyAnnotations($this->reflect); }

  /**
   * Returns this property's doc comment, or NULL if there is none.
   *
   * @return ?string
   */
  public function comment() { return Reflection::meta()->propertyComment($this->reflect); }

  /** Returns whether this property is virtual */
  public function virtual() {
    return $this->reflect instanceof VirtualProperty || self::$HOOKS && $this->reflect->isVirtual();
  }

  /** Returns a compound name consisting of `[CLASS]::$[NAME]`  */
  public function compoundName(): string {
    return strtr($this->reflect->getDeclaringClass()->name , '\\', '.').'::$'.$this->reflect->getName();
  }

  /** @return lang.reflection.Constraint */
  public function constraint() {
    $present= true;

    // Only use meta information if necessary
    $api= function($set) use(&$present) {
      $present= $set;
      return Reflection::meta()->propertyType($this->reflect);
    };

    $t= Type::resolve($this->reflect->getType(), Member::resolve($this->reflect), $api);
    return new Constraint($t ?? Type::$VAR, $present);
  }

  /**
   * Gets this property's value
   *
   * @param  ?object $instance
   * @return var
   * @throws lang.reflection.CannotAccess
   * @throws lang.reflection.AccessingFailed if getting raises an exception
   */
  public function get(?object $instance) {
    try {

      // TODO: Remove superfluous call to setAccessible() if on PHP8.1+
      // see https://wiki.php.net/rfc/make-reflection-setaccessible-no-op
      $this->reflect->setAccessible(true);
      return $this->reflect->getValue($instance);
    } catch (ReflectionException $e) {
      throw new CannotAccess($this, $e);
    } catch (Throwable $e) {
      throw new AccessingFailed($this, $e);
    }
  }

  /**
   * Sets this property's value
   *
   * @param  ?object $instance
   * @param  var $value
   * @return var The given value
   * @throws lang.reflection.CannotAccess
   * @throws lang.reflection.AccessingFailed if setting raises an exception
   */
  public function set(?object $instance, $value) {
    try {

      // TODO: Remove superfluous call to setAccessible() if on PHP8.1+
      // see https://wiki.php.net/rfc/make-reflection-setaccessible-no-op
      $this->reflect->setAccessible(true);
      $this->reflect->setValue($instance, $value);
      return $value;
    } catch (ReflectionException $e) {
      throw new CannotAccess($this, $e);
    } catch (Throwable $e) {
      throw new AccessingFailed($this, $e);
    }
  }

  /** @return string */
  public function toString() {

    // Compile property type
    if (null === ($t= $this->reflect->getType())) {
      $name= Reflection::meta()->propertyType($this->reflect) ?? 'var';
    } else if ($t instanceof ReflectionUnionType) {
      $name= '';
      foreach ($t->getTypes() as $component) {
        $name.= '|'.$component->getName();
      }
      $name= substr($name, 1);
    } else {
      $name= $t->getName();
    }

    return Modifiers::namesOf($this->reflect->getModifiers()).' '.$name.' $'.$this->reflect->getName();
  }

  /**
   * Compares this member to another value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? VirtualProperty::compare($this->reflect, $value->reflect)
      : 1
    ;
  }
}