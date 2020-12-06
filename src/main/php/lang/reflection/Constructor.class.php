<?php namespace lang\reflection;

use lang\{Reflection, Type, IllegalArgumentException};

class Constructor extends Member {
  private $class;

  /** @param ReflectionClass $reflect */
  public function __construct($reflect) {
    parent::__construct($reflect->getMethod('__construct'));
    $this->class= $reflect;
  }

  protected function getAnnotations() { return Reflection::parse()->ofMethod($this->reflect); }

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
    return new Parameters($this->reflect->getParameters(), $this->reflect);
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