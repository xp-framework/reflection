<?php namespace lang\reflection;

use ArgumentCountError, TypeError, UnitEnum, ReflectionClass, ReflectionException, ReflectionFunction, Throwable;
use lang\{Reflection, Enum, XPClass, Value, VirtualProperty, IllegalArgumentException};

/**
 * Reflection for a value type: classes, interfaces, traits and enums
 *
 * @test lang.reflection.unittest.TypeTest
 */
class Type implements Annotated, Value {
  private $reflect;
  private $annotations= null;

  /** @param ReflectionClass $reflect */
  public function __construct($reflect) {
    $this->reflect= $reflect;
  }

  /** Returns type name (in dotted form) */
  public function name(): string { return strtr($this->reflect->name, '\\', '.'); }

  /** Returns declared name (without namespace) */
  public function declaredName(): string { return $this->reflect->getShortName(); }

  /** Returns type literal (in namespaced form) */
  public function literal(): string { return $this->reflect->name; }

  /** Returns corresponding lang.XPClass instance */
  public function class(): XPClass { return new XPClass($this->reflect); }

  /** Returns this type's modifiers */
  public function modifiers(): Modifiers {
    if (PHP_VERSION_ID >= 80200) {

      // PHP 8.2 introduced readonly classes, but its modifier bit is different from
      // the one that properties use (65536 vs. 128), map this to generic form.
      //
      // @codeCoverageIgnoreStart
      $r= $this->reflect->getModifiers();
      $r & ReflectionClass::IS_READONLY && $r^= ReflectionClass::IS_READONLY | Modifiers::IS_READONLY;
      // @codeCoverageIgnoreEnd
    } else {
      $r= $this->reflect->getModifiers();
    }

    $this->reflect->isInternal() && $r|= Modifiers::IS_NATIVE;
    return new Modifiers($r);
  }

  /** Returns type kind */
  public function kind(): Kind {
    if ($this->reflect->isInterface()) {
      return Kind::$INTERFACE;
    } else if ($this->reflect->isTrait()) {
      return Kind::$TRAIT;
    } else if ($this->reflect->isSubclassOf(Enum::class) || $this->reflect->isSubclassOf(UnitEnum::class)) {
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
   * Returns this type's package
   *
   * @return lang.reflection.Package
   */
  public function package() {
    return new Package($this->reflect->getNamespaceName());
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

  /**
   * Returns whether this type is instantiable via `new`. Only takes constructor
   * modifiers into account if `true` is passed as argument.
   */
  public function instantiable(bool $direct= false): bool {
    return $this->reflect->isSubclassOf(Enum::class) ? false : ($direct
      ? $this->reflect->isInstantiable()
      : !($this->reflect->isAbstract() || $this->reflect->isInterface() || $this->reflect->isTrait())
    );
  }

  /**
   * Returns an instantiation from a given initializer function
   *
   * @param  ?string|?Closure $function
   * @return ?lang.reflection.Initializer
   */
  public function initializer($function) {
    if (!$this->instantiable()) return null;

    if (null === $function) {
      return new Initializer($this->reflect);
    } else if ($function instanceof \Closure) {
      $reflect= new ReflectionFunction($function);
      return new Initializer($this->reflect, $reflect, function($instance, $args) use($function) {
        return $function->call($instance, ...$args);
      });
    } else if ($this->reflect->hasMethod($function)) {
      $reflect= $this->reflect->getMethod($function);
      return new Initializer($this->reflect, $reflect, function($instance, $args) use($reflect) {

        // TODO: Remove superfluous call to setAccessible() if on PHP8.1+
        // see https://wiki.php.net/rfc/make-reflection-setaccessible-no-op
        PHP_VERSION_ID < 80100 && $reflect->setAccessible(true);

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
        $pass= PHP_VERSION_ID < 80000 && $args ? Routine::pass($this->reflect->getMethod('__construct'), $args) : $args;
        return $this->reflect->newInstanceArgs($pass);
      } else {
        return $this->reflect->newInstance();
      }
    } catch (ReflectionException|ArgumentCountError|TypeError $e) {
      throw new CannotInstantiate($this, $e);
    } catch (Throwable $e) {
      if (0 === strpos($e->getMessage(), 'Unknown named parameter $')) {
        throw new CannotInstantiate($this, $e);
      } else if ($this->reflect->isInstantiable() && $constructor) {
        throw new InvocationFailed($this->constructor(), $e);
      } else {
        throw new CannotInstantiate($this);
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
    return $this->reflect->hasConstant($name)
      ? new Constant($this->reflect->getReflectionConstant($name))
      : null
    ;
  }

  /** @return ?lang.reflection.Property */
  public function property(string $name) {
    if ($this->reflect->hasProperty($name)) {
      return new Property($this->reflect->getProperty($name));
    } else if ($virtual= Reflection::meta()->virtualProperties($this->reflect)[$name] ?? null) {
      return new Property(new VirtualProperty($this->reflect, $name, $virtual));
    }
    return null;
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
  public function hashCode() { return md5($this->reflect->name); }

  /** @return string */
  public function toString() { return nameof($this).'<'.$this->name().'>'; }

  /**
   * Compares this member to another value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? $this->reflect->name <=> $value->reflect->name
      : 1
    ;
  }

  /** @return string */
  public function __toString() { return $this->reflect->name; }
}