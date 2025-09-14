<?php namespace lang\reflection;

use Error, ReflectionException, ReflectionUnionType, ReflectionIntersectionType;
use lang\{Reflection, Type, TypeUnion};

/**
 * Base class for methods and constructors
 *
 * @test  lang.reflection.unittest.ArgumentPassingTest
 */
abstract class Routine extends Member {

  /** @return [:var] */
  protected function meta() { return Reflection::meta()->methodAnnotations($this->reflect); }

  /**
   * Compiles signature
   *
   * @param  lang.meta.MetaInformation $meta
   * @return string
   */
  protected function signature($meta) {
    $types= $meta->methodParameterTypes($this->reflect);
    $r= '';
    foreach ($this->reflect->getParameters() as $i => $parameter) {
      $t= $parameter->getType();
      $nullable= '';
      if (null === $t) {
        $type= $types[$i] ?? ($parameter->isVariadic() ? 'var...' : 'var');
      } else if ($t instanceof ReflectionUnionType) {
        $name= '';
        foreach ($t->getTypes() as $component) {
          if ('null' === ($c= $component->getName())) {
            $nullable= '?';
          } else {
            $name.= '|'.$c;
          }
        }
        $type= substr($name, 1);
      } else if ($t instanceof ReflectionIntersectionType) {
        $name= '';
        foreach ($t->getTypes() as $component) {
          $name.= '&'.$component->getName();
        }
        $type= substr($name, 1);
      } else {
        $type= strtr($t->getName(), '\\', '.');
        $parameter->isVariadic() && $type.= '...';
        $t->allowsNull() && $nullable= '?';
      }
      $r.= ', '.$nullable.$type.' $'.$parameter->name;
    }
    return substr($r, 2);
  }

  /**
   * Returns this routines's doc comment, or NULL if there is none.
   *
   * @return ?string
   */
  public function comment() { return Reflection::meta()->methodComment($this->reflect); }

  /** Returns a compound name consisting of `[CLASS]::[NAME]()`  */
  public function compoundName(): string { return strtr($this->reflect->class, '\\', '.').'::'.$this->reflect->name.'()'; }

  /**
   * Looks up a parameter
   *
   * @param  int|string $arg Either a position or a name
   * @return ?lang.reflection.Parameter
   */
  public function parameter($arg) {
    if (is_int($arg)) {
      $p= $this->reflect->getParameters()[$arg] ?? null;
    } else {
      $p= null;
      foreach ($this->reflect->getParameters() as $parameter) {
        if ($arg !== $parameter->name) continue;
        $p= $parameter;
        break;
      }
    }
    return null === $p ? null : new Parameter($p, $this->reflect);
  }

  /**
   * Returns all parameters
   *
   * @return lang.reflection.Parameters
   */
  public function parameters(): Parameters {
    return new Parameters($this->reflect);
  }

  /** Support named arguments for PHP 7.X */
  public static function pass($reflect, $args) {
    $pass= [];
    foreach ($reflect->getParameters() as $i => $param) {
      if ($param->isVariadic()) {
        while ($args) $pass[]= array_shift($args);
        break;
      } else if (isset($args[$param->name])) {
        $pass[]= $args[$param->name];
        unset($args[$param->name]);
      } else if (isset($args[$i])) {
        $pass[]= $args[$i];
        unset($args[$i]);
      } else if ($param->isOptional()) {
        $pass[]= $param->getDefaultValue();
      } else {
        throw new ReflectionException('Missing parameter $'.$param->name);
      }
    }

    // Check for excess named parameters
    if ($args && is_string($excess= key($args))) {
      throw new Error('Unknown named parameter $'.$excess);
    }

    return $pass;
  }
}