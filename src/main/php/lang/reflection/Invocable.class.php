<?php namespace lang\reflection;

use ReflectionException, ReflectionFunction, ArgumentCountError, Closure, TypeError, Throwable;
use lang\{Reflection, Type};

class Invocable implements Target {
  private $reflect;

  /** @param callable|ReflectionFunction $arg */
  public function __construct($arg) {
    if ($arg instanceof ReflectionFunction) {
      $this->reflect= $arg;
    } else if (is_array($arg)) {
      $this->reflect= new ReflectionFunction(Closure::fromCallable($arg));
    } else {
      $this->reflect= new ReflectionFunction($arg);
    }
  }

  /** Returns the name, null for anonymous functions */
  public function name(): ?string {
    if (PHP_VERSION_ID < 80200 ? strpos($this->reflect->name, '{closure}') : $this->reflect->isAnonymous()) {
      return null;
    } else {
      return $this->reflect->name;
    }
  }

  /** Returns a compound name consisting of `[NAME]()` */
  public function compoundName(): string { return $this->reflect->name.'()'; }

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

  /** @return lang.reflection.Constraint */
  public function returns() {
    $present= true;
    $absent= function($set) use(&$present) { $present= false; };

    $t= Type::resolve($this->reflect->getReturnType(), [], $absent);
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
  public function invoke($args= []) {
    try {
      $pass= PHP_VERSION_ID < 80000 && $args ? Routine::pass($this->reflect, $args) : $args;
      return $this->reflect->invokeArgs($pass);
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
}