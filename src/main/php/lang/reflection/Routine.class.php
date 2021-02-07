<?php namespace lang\reflection;

use lang\{Reflection, Type, TypeUnion};

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
      $nullable= '';
      if (null === $t) {
        $type= $types[$i] ?? ($parameter->isVariadic() ? 'var...' : 'var');
      } else if ($t instanceof \ReflectionUnionType) {
        $name= '';
        foreach ($t->getTypes() as $component) {
          if ('null' === ($c= $component->getName())) {
            $nullable= '?';
          } else {
            $name.= '|'.$c;
          }
        }
        $type= substr($name, 1);
      } else {
        $type= strtr(PHP_VERSION_ID >= 70100 ? $t->getName() : $t->__toString(), '\\', '.');
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

  /** Returns whether a method accepts a given argument list */
  public function accepts(array $arguments): bool {

    // Only fetch api doc types if necessary
    $api= function() use(&$i, &$types) {
      $types ?? $types= Reflection::meta()->methodParameterTypes($this->reflect);
      return $types[$i] ?? null;
    };

    $context= $this->resolver();
    foreach ($this->reflect->getParameters() as $i => $parameter) {

      // If a given value is missing check whether parameter is optional
      if (!array_key_exists($i, $arguments)) return $parameter->isOptional();

      // A value is present for this parameter, now check type
      if (null === ($type= Type::resolve($parameter->getType(), $context, $api))) continue;

      // For variadic parameters, verify rest of arguments
      if ($parameter->isVariadic()) {
        for ($s= sizeof($arguments); $i < $s; $i++) {
          if (!$type->isInstance($arguments[$i])) return false;
        }
        return true;
      }

      // ...otherwise, verify this argument and continue to next
      if (!$type->isInstance($arguments[$i])) return false;
    }
    return true;
  }
}