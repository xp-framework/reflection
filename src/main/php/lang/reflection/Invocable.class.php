<?php namespace lang\reflection;

use ReflectionException, ReflectionFunctionAbstract, ReflectionFunction, ArgumentCountError, Closure, TypeError, Throwable;
use lang\{Reflection, Type, Value};

class Invocable implements Target, Annotated, Value {
  private $reflect, $receiver;
  private $annotations= null;

  /**
   * Creates a new instance
   *
   * @param  callable|ReflectionFunction $arg
   * @param  ?object $receiver
   */
  public function __construct($arg, $receiver= null) {
    if ($arg instanceof ReflectionFunctionAbstract) {
      $this->reflect= $arg;
    } else if (is_array($arg)) {
      $this->reflect= new ReflectionFunction(Closure::fromCallable($arg));
    } else {
      $this->reflect= new ReflectionFunction($arg);
    }
    $this->receiver= $receiver;
  }

  /** Returns the name, null for anonymous functions */
  public function name(): ?string {
    if (PHP_VERSION_ID < 80200 ? strpos($this->reflect->name, '{closure}') : $this->reflect->isAnonymous()) {
      return null;
    } else {
      return strtr($this->reflect->name, '\\', '.');
    }
  }

  /** Returns type literal (in namespaced form) */
  public function literal(): ?string {
    if (PHP_VERSION_ID < 80200 ? strpos($this->reflect->name, '{closure}') : $this->reflect->isAnonymous()) {
      return null;
    } else {
      return $this->reflect->name;
    }
  }

  /** Returns declared name (without namespace) */
  public function declaredName(): ?string {
    if (PHP_VERSION_ID < 80200 ? strpos($this->reflect->name, '{closure}') : $this->reflect->isAnonymous()) {
      return null;
    } else {
      return $this->reflect->getShortName();
    }
  }

  /** Returns a compound name consisting of `[NAME]()` */
  public function compoundName(): string { return $this->name().'()'; }

  /**
   * Returns this invocable's package. Anonymous functions are always in the
   * top-level package
   *
   * @return lang.reflection.Package
   */
  public function package() {
    if (PHP_VERSION_ID < 80200) {
      $ns= false === strpos($this->reflect->name, '{closure}') ? $this->reflect->getNamespaceName() : '';
    } else if (PHP_VERSION_ID < 80400) {
      $ns= $this->reflect->isAnonymous() ? '' : $this->reflect->getNamespaceName();
    } else {
      $ns= $this->reflect->getNamespaceName();
    }
    return new Package($ns);
  }

  /**
   * Returns this functions's doc comment, or NULL if there is none.
   *
   * @return ?string
   */
  public function comment() {
    return false === ($c= $this->reflect->getDocComment())
      ? null
      : trim(preg_replace('/\n\s+\* ?/', "\n", substr($c, 3, -2)))
    ;
  }

  /** @return lang.reflection.Annotations */
  public function annotations() {
    $this->annotations ?? $this->annotations= Reflection::meta()->annotations->ofInvocable($this->reflect);
    return new Annotations($this->annotations);
  }

  /** @return ?lang.reflection.Annotation */
  public function annotation(string $type) {
    $this->annotations ?? $this->annotations= Reflection::meta()->annotations->ofInvocable($this->reflect);

    $t= strtr($type, '.', '\\');
    return isset($this->annotations[$t]) ? new Annotation($t, $this->annotations[$t]) : null;
  }

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

  /** Returns all parameters */
  public function parameters(): Parameters { return new Parameters($this->reflect); }

  /** @return lang.reflection.Constraint */
  public function returns() {
    $present= true;
    $t= Type::resolve($this->reflect->getReturnType(), [], function($set) use(&$present) {
      $present= false;
    });
    return new Constraint($t ?? Type::$VAR, $present);
  }

  /**
   * Invokes this closure
   *
   * @param  var[] $args
   * @return var
   * @throws lang.reflection.CannotInvoke if prerequisites to the invocation fail
   * @throws lang.reflection.InvocationFailed if invocation raises an exception
   */
  public function invoke(array $args= []) {
    try {
      $pass= PHP_VERSION_ID < 80000 && $args ? Routine::pass($this->reflect, $args) : $args;
      return $this->reflect instanceof ReflectionFunction
        ? $this->reflect->invokeArgs($pass)
        : $this->reflect->invokeArgs($this->receiver, $pass)
      ;
    } catch (ReflectionException|ArgumentCountError|TypeError $e) {
      throw new CannotInvoke($this, $e);
    } catch (Throwable $e) {

      // This really should be an ArgumentCountError...
      if (0 === strpos($e->getMessage(), 'Unknown named parameter $')) {
        throw new CannotInvoke($this, $e);
      }

      throw new InvocationFailed($this, $e);
    }
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
}