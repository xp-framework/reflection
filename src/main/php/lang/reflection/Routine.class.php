<?php namespace lang\reflection;

use lang\Reflection;

/** Base class for methods and constructors */
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
      if (null === $t) {
        $type= $types[$i] ?? ($parameter->isVariadic() ? 'var...' : 'var');
      } else if ($t instanceof \ReflectionUnionType) {
        $name= '';
        foreach ($t->getTypes() as $component) {
          $name.= '|'.$component->getName();
        }
        $type= substr($name, 1);
      } else {
        $type= strtr(PHP_VERSION_ID >= 70100 ? $t->getName() : $t->__toString(), '\\', '.');
        $parameter->isVariadic() && $type.= '...';
      }
      $r.= ', '.$type.' $'.$parameter->name;
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
  public function parameters() {
    return new Parameters($this->reflect);
  }
}