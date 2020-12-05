<?php namespace lang\reflection;

use lang\{Reflection, Type, IllegalArgumentException};

class Constructor extends Member {
  protected $method;

  public function __construct($reflect) {
    parent::__construct($reflect);
    $this->method= $reflect->getConstructor();
  }

  public function annotations() {
    return Reflection::parse()->methodAnnotations($this->method);
  }

  /** @return string */
  public function toString() {

    // Parse apidoc. FIXME!
    preg_match_all('/@(param)\s+(.+)/', $this->method->getDocComment(), $matches, PREG_SET_ORDER);
    $tags= [];
    foreach ($matches as $match) {
      $tags[$match[1]][]= $match[2];
    }

    // Compile signature
    $sig= '';
    foreach ($this->method->getParameters() as $i => $parameter) {
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

    return Modifiers::namesOf($this->method->getModifiers()).' function __construct('.substr($sig, 2).')';
  }

  public function newInstance($args) {
    if (!$this->reflect->isInstantiable()) {
      throw new IllegalArgumentException('Cannot instantiate '.strtr($this->reflect->getName(), '\\', '.'));
    }

    try {
      return $this->reflect->newInstanceArgs($args);
    } catch (\ReflectionException $e) {
      throw new CannotInvoke($this->reflect->name, $e);
    } catch (\Throwable $e) {
      throw new CannotInvoke($this->reflect->name, $e);
    }
  }
}