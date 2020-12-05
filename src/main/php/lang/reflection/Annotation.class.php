<?php namespace lang\reflection;

use lang\{Value, XPClass};
use util\Objects;

class Annotation implements Value {
  private $type, $arguments;

  public function __construct($type, $arguments) {
    $this->type= $type;
    $this->arguments= $arguments;
  }

  public function type() { return $this->type; }

  public function name() {
    return strtolower(false === ($p= strrpos($this->type, '\\'))
      ? $this->type
      : substr($this->type, $p + 1
    ));
  }

  public function arguments() { return $this->arguments; }

  public function argument($key) { return $this->arguments[$key] ?? null; }

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
  public function hashCode() { return 'A'.Objects::hashOf([$this->type, $this->arguments]); }

  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare([$this->type, $this->arguments], [$value->type, $value->arguments])
      : 1
    ;
  }
}
