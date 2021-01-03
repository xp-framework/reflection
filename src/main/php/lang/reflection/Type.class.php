<?php namespace lang\reflection;

use lang\{Reflection, Enum, XPClass, IllegalArgumentException};

/**
 * Reflection for a value type: classes, interfaces, traits and enums
 *
 * @test lang.reflection.unittest.TypeTest
 */
class Type {
  private $reflect;
  private $annotations= null;

  /** @param ReflectionClass $reflect */
  public function __construct($reflect) {
    $this->reflect= $reflect;
  }

  /** Returns type name (in dotted form) */
  public function name(): string { return strtr($this->reflect->name, '\\', '.'); }

  /** Returns type literal (in namespaced form) */
  public function literal(): string { return $this->reflect->name; }

  /** Returns corresponding lang.XPClass instance */
  public function class(): XPClass { return new XPClass($this->reflect); }

  /** Returns this type's modifiers */
  public function modifiers(): Modifiers {
    if (PHP_VERSION_ID < 70400) {

      // PHP 7.4 made type and member modifiers consistent. For versions before that,
      // map PHP reflection modifiers to generic form
      //
      // @codeCoverageIgnoreStart
      $m= $this->reflect->getModifiers();
      $r= 0;
      $m & \ReflectionClass::IS_EXPLICIT_ABSTRACT && $r |= Modifiers::IS_ABSTRACT;
      $m & \ReflectionClass::IS_IMPLICIT_ABSTRACT && $r |= Modifiers::IS_ABSTRACT;
      $m & \ReflectionClass::IS_FINAL && $r |= Modifiers::IS_FINAL;
      // @codeCoverageIgnoreEnd
    } else {
      $r= $this->reflect->getModifiers();
    }

    $this->reflect->isInternal() && $r |= Modifiers::IS_NATIVE;
    return new Modifiers($r);
  }

  /** Returns type kind */
  public function kind(): Kind {
    if ($this->reflect->isInterface()) {
      return Kind::$INTERFACE;
    } else if ($this->reflect->isTrait()) {
      return Kind::$TRAIT;
    } else if ($this->reflect->isSubclassOf(Enum::class)) {
      return Kind::$ENUM;
    } else {
      return Kind::$CLASS;
    }
  }

  /**
   * Returns whether a given value is an instance of this type
   *
   * @param var $value
   */
  public function isInstance($value): bool { return $this->reflect->isInstance($value); }

  /**
   * Returns whether this type is either a subclass or equals the given type,
   * much like the `instanceof` operator.
   *
   * @param self|lang.XPClass|string $type
   */
  public function is($type): bool {
    if ($type instanceof self) {
      $compare= $type->reflect->name;
    } else if ($type instanceof XPClass) {
      $compare= $type->literal();
    } else {
      $compare= strtr($type, '.', '\\');
    }
    return $this->reflect->name === $compare || $this->reflect->isSubclassOf($compare);
  }

  /**
   * Returns this type's package, or NULL if this type is in the global namespace.
   *
   * @return ?lang.reflection.Package
   */
  public function package() {
    $name= $this->reflect->getNamespaceName();
    return $name ? new Package($name) : null;
  }

  /**
   * Returns this type's parent, if any
   *
   * @return ?self
   */
  public function parent() {
    return ($parent= $this->reflect->getParentClass()) ? new self($parent) : null;
  }

  /**
   * Returns implementations of classes, or parents of interfaces, respectively.
   *
   * @return self[]
   */
  public function interfaces() {
    $r= [];
    foreach ($this->reflect->getInterfaces() as $interface) {
      $r[]= new self($interface);
    }
    return $r;
  }

  /**
   * Returns traits used by this type
   *
   * @return self[]
   */
  public function traits() {
    $r= [];
    foreach ($this->reflect->getTraits() as $interface) {
      $r[]= new self($interface);
    }
    return $r;
  }

  /** @return ?lang.IClassLoader */
  public function classLoader() {
    $name= strtr($this->reflect->name, '\\', '.');
    if (isset(\xp::$cl[$name])) {
      sscanf(\xp::$cl[$name], '%[^:]://%[^$]', $cl, $argument);
      $instanceFor= [literal($cl), 'instanceFor'];
      return $instanceFor($argument);
    }
    return null; // Internal class, e.g.
  }

  /** Returns whether this type is instantiable via `new` */
  public function instantiable(): bool { return $this->reflect->isInstantiable(); }

  /**
   * Returns an instantiation from a given initializer function
   *
   * @param  ?string|?Closure $initializer
   * @return ?lang.reflection.Instantiation
   */
  public function instantiation($initializer) {
    if (!$this->reflect->isInstantiable()) return null;

    if (null === $initializer) {
      return new Instantiation($this->reflect, new \ReflectionFunction(function() { }));
    } else if ($initializer instanceof \Closure) {
      $reflect= new \ReflectionFunction($initializer);
      return new Instantiation($this->reflect, $reflect, function($instance, $args) use($initializer) {
        return $initializer->call($instance, ...$args);
      });
    } else if ($this->reflect->hasMethod($initializer)) {
      $reflect= $this->reflect->getMethod($initializer);
      return new Instantiation($this->reflect, $reflect, function($instance, $args) use($reflect) {
        return $reflect->invokeArgs($instance, $args);
      });
    }

    return null;
  }

  /**
   * Returns this type's constructor, if present
   *
   * @return ?lang.reflection.Constructor
   */
  public function constructor() {
    return $this->reflect->hasMethod('__construct') ? new Constructor($this->reflect) : null;
  }

  /**
   * Instantiates a new instance of the underlying type. If the type does not
   * have any constructor, any given arguments are ignored.
   *
   * @param  var... $args
   * @return object
   * @throws lang.IllegalArgumentException if the type is not instantiable
   * @throws lang.reflect.CannotInstantiate if instantiation raised an exception
   */
  public function newInstance(... $args) {
    $constructor= $this->reflect->hasMethod('__construct');
    try {
      if ($constructor) {
        return $this->reflect->newInstanceArgs($args);
      } else {
        return $this->reflect->newInstance();
      }
    } catch (\ReflectionException $e) {
      throw new CannotInstantiate($this->reflect->name, $e);
    } catch (\Throwable $e) {
      if ($this->reflect->isInstantiable() && $constructor) {
        throw new InvocationFailed($this->constructor(), $e);
      } else {
        throw new CannotInstantiate($this->reflect->name);
      }
    }
  }

  /** @return lang.reflection.Annotations */
  public function annotations() {
    $this->annotations ?? $this->annotations= Reflection::meta()->typeAnnotations($this->reflect);
    return new Annotations($this->annotations);
  }

  /** @return ?lang.reflection.Annotation */
  public function annotation(string $type) {
    $this->annotations ?? $this->annotations= Reflection::meta()->typeAnnotations($this->reflect);

    $t= strtr($type, '.', '\\');
    return isset($this->annotations[$t]) ? new Annotation($t, $this->annotations[$t]) : null;
  }

  /** @return lang.reflection.Constants */
  public function constants() { return new Constants($this->reflect); }

  /** @return ?lang.reflection.Constant */
  public function constant($name) {

    // Cannot use getReflectionConstant(), which does not exist in PHP 7.0.
    // Instantiate the polyfilled ReflectionClassConstant class directly in
    // order to make this compatible will all versions.
    return $this->reflect->hasConstant($name)
      ? new Constant(new \ReflectionClassConstant($this->reflect->name, $name))
      : null
    ;
  }

  /** @return ?lang.reflection.Property */
  public function property(string $name) {
    return $this->reflect->hasProperty($name)
      ? new Property($this->reflect->getProperty($name))
      : null
    ;
  }

  /** @return lang.reflection.Properties */
  public function properties() { return new Properties($this->reflect); }

  /** @return ?lang.reflection.Method */
  public function method($name) {
    return $this->reflect->hasMethod($name)
      ? new Method($this->reflect->getMethod($name))
      : null
    ;
  }

  /** @return lang.reflection.Methods */
  public function methods() { return new Methods($this->reflect); }

  /**
   * Returns this type's doc comment, or NULL if there is none.
   *
   * @return ?string
   */
  public function comment() {
    return Reflection::meta()->typeComment($this->reflect);
  }

  /**
   * Evaluates a given expression in this type's context
   *
   * @param  string $expression
   * @return var
   */
  public function evaluate($expression) {
    return Reflection::meta()->evaluate($this->reflect, $expression);
  }

  /** @return string */
  public function __toString() { return $this->reflect->name; }
}