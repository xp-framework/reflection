<?php namespace lang\reflection;

use ArgumentCountError, ReflectionMethod, ReflectionClass, TypeError, Error, Throwable;
use lang\{Value, XPClass};
use util\Objects;

/**
 * Reflection for a single annotation
 *
 * @test lang.reflection.unittest.AnnotationTest
 */
class Annotation implements Value {
  private $type, $arguments;

  public function __construct($type, $arguments) {
    $this->type= $type;
    $this->arguments= $arguments;
  }

  public function type(): string { return $this->type; }

  /** Returns lowercase name */
  public function name(): string {
    return strtolower(false === ($p= strrpos($this->type, '\\'))
      ? $this->type
      : substr($this->type, $p + 1
    ));
  }

  /** Returns all arguments */
  public function arguments(): array { return $this->arguments; }

  /**
   * Returns a given argument, or NULL if it doesn't exist
   *
   * @param  string|int $key
   * @return var
   */
  public function argument($key) { return $this->arguments[$key] ?? null; }

  /**
   * Creates a new instance of this annotation
   *
   * @return object
   * @throws lang.reflection.CannotInstantiate if prerequisites to the instantiation fail
   * @throws lang.reflection.InvocationFailed if instantiaton raises an exception
   */
  public function newInstance() {

    // Support named arguments for PHP 7.X
    if (PHP_VERSION_ID < 80000 && is_string(key($this->arguments))) {
      $ctor= new ReflectionMethod($this->type, '__construct');
      $pass= [];
      $args= $this->arguments;
      foreach ($ctor->getParameters() as $param) {
        $pass[]= $args[$param->name] ?? ($param->isOptional() ? $param->getDefaultValue() : null);
        unset($args[$param->name]);
      }
      if ($args) {
        throw new CannotInstantiate($this->type, new Error('Unknown named parameter $'.key($args)));
      }
    } else {
      $pass= $this->arguments;
    }

    try {
      return new $this->type(...$pass);
    } catch (ArgumentCountError $e) {
      throw new CannotInstantiate($this->type, $e);
    } catch (TypeError $e) {
      throw new CannotInstantiate($this->type, $e);
    } catch (Throwable $e) {

      // This really should be an ArgumentCountError...
      if (0 === strpos($e->getMessage(), 'Unknown named parameter $')) {
        throw new CannotInstantiate($this->type, $e);
      }

      throw new InvocationFailed(new Constructor(new ReflectionClass($this->type)), $e);
    }
  }

  /**
   * Checks whether this annotation is of a given type
   *
   * @param  lang.XPClass|lang.reflection.Type $type
   * @return bool
   */
  public function is($type) {
    if ($type instanceof Type || $type instanceof XPClass) {
      $compare= $type->literal();
    } else {
      $compare= strtr($type, '.', '\\');
    }
    return $this->type === $compare || is_subclass_of($this->type, $compare);
  }

  /** @return string */
  public function toString() { return nameof($this).'<'.$this->type.'('.Objects::stringOf($this->arguments).')>'; }

  /** @return string */
  public function hashCode() { return 'A'.md5(Objects::hashOf([$this->type, $this->arguments])); }

  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare([$this->type, $this->arguments], [$value->type, $value->arguments])
      : 1
    ;
  }
}
