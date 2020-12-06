<?php namespace lang\reflection;

use lang\Type as XPType;
use lang\{XPClass, Reflection};

abstract class Member {
  protected $reflect;
  private $annotations= null;

  /** @param var $reflect */
  public function __construct($reflect) {
    $this->reflect= $reflect;
  }

  /**
   * Resolver handling `static`, `self` and `parent`.
   *
   * @return [:(function(string): lang.Type)]
   */
  protected function resolver() {
    return [
      'static' => function() { return new XPClass($this->reflect->class); },
      'self'   => function() { return new XPClass($this->reflect->getDeclaringClass()); },
      'parent' => function() { return new XPClass($this->reflect->getDeclaringClass()->getParentClass()); },
    ];
  }

  /** @return [:var] */
  protected abstract function getAnnotations();

  /** @return lang.reflection.Annotations */
  public function annotations() {
    $this->annotations ?? $this->annotations= $this->getAnnotations();
    return new Annotations($this->annotations);
  }

  /** @return ?lang.reflection.Annotation */
  public function annotation(string $type) {
    $this->annotations ?? $this->annotations= $this->getAnnotations();

    $t= strtr($type, '.', '\\');
    if (isset($this->annotations[$t])) return new Annotation($t, $this->annotations[$t]);

    // Check lowercase version
    $n= lcfirst(false === ($p= strrpos($t, '\\')) ? $t : substr($t, $p + 1));
    return isset($this->annotations[$n]) ? new Annotation($n, $this->annotations[$n]) : null;
  }

  /** @return string */
  public function name() { return $this->reflect->name; }

  /** @return lang.reflection.Modifiers */
  public function modifiers() { return new Modifiers($this->reflect->getModifiers()); }

  /** @return lang.reflection.Type */
  public function declaredIn() { return new Type($this->reflect->getDeclaringClass()); }

  public function evaluate($expression) {
    return Reflection::parse()->evaluate($this->reflect->getDeclaringClass(), $expression);
  }
}