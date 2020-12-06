<?php namespace lang\reflection;

use lang\{Reflection, Type, IllegalArgumentException};

class Constructor extends Routine {
  private $class;

  /** @param ReflectionClass $reflect */
  public function __construct($reflect) {
    parent::__construct($reflect->getMethod('__construct'));
    $this->class= $reflect;
  }

  /** @return string */
  public function toString() {

    // Parse apidoc. FIXME!
    preg_match_all('/@(param)\s+(.+)/', $this->reflect->getDocComment(), $matches, PREG_SET_ORDER);
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

    return Modifiers::namesOf($this->reflect->getModifiers()).' function __construct('.substr($sig, 2).')';
  }

  public function newInstance($args) {
    if (!$this->class->isInstantiable()) {
      throw new IllegalArgumentException('Cannot instantiate '.strtr($this->class->getName(), '\\', '.'));
    }

    try {
      return $this->class->newInstanceArgs($args);
    } catch (\Throwable $e) {
      throw new CannotInstantiate($this->class->name, $e);
    }
  }
}