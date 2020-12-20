<?php namespace lang\reflection;

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

  /** @return object */
  public function newInstance() { return new $this->type(...$this->arguments); }

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
