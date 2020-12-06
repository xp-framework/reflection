<?php namespace lang\reflection;

use lang\{Reflection, Type, TypeUnion, XPClass};

class Parameter {
  private $reflect, $method;

  public function __construct($reflect) {
    $this->reflect= $reflect;
    $this->method= $reflect->getDeclaringFunction();
  }

  /** @return string */
  public function name() { return $this->reflect->name; }

  /** @return int */
  public function position() { return $this->reflect->getPosition(); }

  /**
   * Resolver handling `static`, `self` and `parent`.
   *
   * @return [:(function(string): lang.Type)]
   */
  protected function resolver() {
    return [
      'static' => function() { return new XPClass($this->method->class); },
      'self'   => function() { return new XPClass($this->method->getDeclaringClass()); },
      'parent' => function() { return new XPClass($this->method->getDeclaringClass()->getParentClass()); },
    ];
  }

  /** @return lang.reflection.TypeHint */
  public function constraint() {
    $t= $this->reflect->getType();
    if (null === $t) {
      $present= false;

      // Check for type in api documentation, defaulting to `var`
      $t= Type::$VAR;
    } else if ($t instanceof \ReflectionUnionType) {
      $union= [];
      foreach ($t->getTypes() as $component) {
        $union[]= Type::resolve($component->getName(), $this->resolver());
      }
      return new TypeHint(new TypeUnion($union));
    } else {
      $name= PHP_VERSION_ID >= 70100 ? $t->getName() : $t->__toString();

      // Check array, self and callable for more specific types, e.g. `string[]`,
      // `static` or `function(): string` in api documentation
      if ('array' === $name) {
        $t= Type::$ARRAY;
      } else if ('callable' === $name) {
        $t= Type::$CALLABLE;
      } else if ('self' === $name) {
        $t= new XPClass($this->reflect->getDeclaringClass());
      } else {
        return new TypeHint(Type::resolve($name, $this->resolver()));
      }
      $present= true;
    }

    // Parse apidoc. FIXME!
    preg_match_all('/@(return|param)\s+(.+)/', $this->method->getDocComment(), $matches, PREG_SET_ORDER);
    $tags= [];
    foreach ($matches as $match) {
      $tags[$match[1]][]= rtrim($match[2], ' */');
    }

    $p= $this->reflect->getPosition();
    if (isset($tags['param'][$p])) {
      preg_match('/([^ ]+)( \$?[a-z_]+)/i', $tags['param'][$p], $matches);
      return new TypeHint(Type::resolve($matches[1], $this->resolver()), $present);
    }

    return new TypeHint($t, $present);
  }
}