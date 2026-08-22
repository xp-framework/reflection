<?php namespace lang\reflection\unittest;

use lang\{Generic, Value};
use util\Objects;

#[Generic(self: 'T')]
class Sequence implements Value {
  private $elements;

  #[Generic(params: 'T...')]
  public function __construct(... $elements) {
    $this->elements= $elements;
  }

  #[Generic(params: 'T[]', return: 'self<T>')]
  public function extend($elements): self {
    foreach ($elements as $element) {
      $this->elements[]= $element;
    }
    return $this;
  }

  #[Generic(self: 'R', params: 'function(T): R', return: 'self<R>')]
  public function map($map) {
    return create("new self<$R>")->extend(array_map($map, $this->elements));
  }

  #[Generic(return: 'T[]')]
  public function elements() { return $this->elements; }

  public function hashCode() { return 'S'.Objects::hashOf($this->elements); }

  public function toString() { return nameof($this).'@'.Objects::stringOf($this->elements); }

  public function compareTo($value) {
    return $value instanceof self ? $this->elements <=> $value->elements : 1;
  }
}