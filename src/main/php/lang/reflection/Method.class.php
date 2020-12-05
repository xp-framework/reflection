<?php namespace lang\reflection;

use lang\{Reflection, Type};

class Method extends Member {
  private $annotations= null;

  public function invoke($instance, $args, $context= null) {

    // Verify context is an instance of class this method is declared in
    if ($context) {
      $this->reflect->setAccessible(true);
    }

    try {
      return $this->reflect->invokeArgs($instance, $args);
    } catch (\ReflectionException $e) {
      throw new CannotInvoke(strtr($this->reflect->class, '\\', '.').'::'.$this->reflect->name, $e);
    } catch (\Throwable $e) {
      throw new CannotInvoke(strtr($this->reflect->class, '\\', '.').'::'.$this->reflect->name, $e);
    }
  }

  public function annotations() {
    $this->annotations ?? $this->annotations= Reflection::parse()->ofMethod($this->reflect);
    return new Annotations($this->annotations);
  }

  /** @return ?lang.reflection.Annotation */
  public function annotation(string $type) {
    $this->annotations ?? $this->annotations= Reflection::parse()->ofMethod($this->reflect);

    $t= strtr($type, '.', '\\');
    if (isset($this->annotations[$t])) {
      return new Annotation($t, $this->annotations[$t]);
    }

    // Check lowercase version
    $n= lcfirst(false === ($p= strrpos($t, '\\')) ? $t : substr($t, $p + 1));
    return isset($this->annotations[$n]) ? new Annotation($n, $this->annotations[$n]) : null;
  }

  /** @return string */
  public function toString() {

    // Parse apidoc. FIXME!
    preg_match_all('/@(return|param)\s+(.+)/', $this->reflect->getDocComment(), $matches, PREG_SET_ORDER);
    $tags= [];
    foreach ($matches as $match) {
      $tags[$match[1]][]= $match[2];
    }

    // Compile signature
    $sig= '';
    foreach ($this->reflect->getParameters() as $i => $parameter) {
      if ($t= $parameter->getType()) {
        $type= strtr($t->getName(), '\\', '.').($parameter->isVariadic() ? '...' : '');
      } else if (isset($tags['param'][$i])) {
        preg_match('/([^ ]+)( \$?[a-z_]+)/i', $tags['param'][$i], $matches);
        $type= $matches[1] ?? $tags['param'][$i];
      } else {
        $type= 'var';
      }
      $sig.= ', '.$type.' $'.$parameter->name;
    }

    // Put together return type
    if ($t= $this->reflect->getReturnType()) {
      $returns= strtr($t->getName(), '\\', '.');
    } else if (isset($tags['return'][0])) {
      preg_match('/([^ ]+)( .+)?/i', $tags['return'][0], $matches);
      $returns= $matches[1] ?? $tags['return'][0];
    } else {
      $returns= 'var';
    }

    return 
      Modifiers::namesOf($this->reflect->getModifiers()).
      ' function '.$this->reflect->name.'('.substr($sig, 2).'): '.
      $returns
    ;
  }
}