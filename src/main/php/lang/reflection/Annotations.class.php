<?php namespace lang\reflection;

use IteratorAggregate, Traversable;
use lang\XPClass;

/**
 * Type and member annotations enumeration and lookup
 *
 * @test lang.reflection.unittest.AnnotationTest
 */
class Annotations implements IteratorAggregate {
  private $annotations;

  public function __construct($annotations) {
    $this->annotations= $annotations;
  }

  /** @return iterable */
  public function getIterator(): Traversable {
    foreach ($this->annotations as $type => $arguments) {
      yield $type => new Annotation($type, $arguments);
    }
  }

  /**
   * Returns whether a given annotation type is present
   *
   * @param  string $type
   * @return ?lang.reflection.Annotation
   */
  public function provides($type) {
    $t= strtr($type, '.', '\\');
    return isset($this->annotations[$t]);
  }

  /**
   * Returns an annotation for a given type
   *
   * @param  string $type
   * @return ?lang.reflection.Annotation
   */
  public function type($type) {
    $t= strtr($type, '.', '\\');
    return isset($this->annotations[$t]) ? new Annotation($t, $this->annotations[$t]) : null;
  }

  /**
   * Returns all annotation of a given type
   *
   * @param  string|lang.XPClass|lang.reflection.Type $type
   * @return iterable
   */
  public function all($type) {
    if ($type instanceof Type || $type instanceof XPClass) {
      $compare= $type->literal();
    } else {
      $compare= strtr($type, '.', '\\');
    }

    foreach ($this->annotations as $type => $arguments) {
      if ($type === $compare || is_subclass_of($type, $compare)) {
        yield $type => new Annotation($type, $arguments);
      }
    }
  }
}