<?php namespace lang\reflection;

use ReflectionMethod, ReflectionClass;
use lang\{Reflection, Value};

/** @test lang.reflection.unittest.SourceTest */
class Source implements Value {
  private $reflect;

  /** @param ReflectionClass|ReflectionMethod $reflect */
  public function __construct($reflect) {
    $this->reflect= $reflect;
  }

  /** @return string */
  public function fileName() { return $this->reflect->getFileName(); }

  /** @return int */
  public function startLine() { return $this->reflect->getStartLine(); }

  /** @return int */
  public function endLine() { return $this->reflect->getEndLine(); }

  /** @return string */
  public function hashCode() {
    return "S{$this->reflect->getFileName()}:{$this->reflect->getStartLine()}-{$this->reflect->getEndLine()}";
  }

  /** @return [:[:string]] */
  public function imports() {
    return Reflection::meta()->scopeImports($this->reflect instanceof ReflectionClass
      ? $this->reflect
      : $this->reflect->getDeclaringClass()
    );
  }

  /** @return string */
  public function toString() {
    return sprintf(
      '%s(file: %s, lines: %d .. %d)',
      nameof($this),
      $this->reflect->getFileName(),
      $this->reflect->getStartLine(),
      $this->reflect->getEndLine()
    );
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    if ($value instanceof self) {
      return Objects::compare(
        [$this->reflect->getFileName(), $this->reflect->getStartLine(), $this->reflect->getEndLine()],
        [$value->reflect->getFileName(), $value->reflect->getStartLine(), $value->reflect->getEndLine()]
      );
    }
    return 1;
  }
}