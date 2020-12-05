<?php namespace lang\reflection;

class Annotations implements \IteratorAggregate {
  private $annotations;

  public function __construct($annotations) {
    $this->annotations= $annotations;
  }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->annotations as $type => $arguments) {
      yield $type => new Annotation($type, $arguments);
    }
  }

  public function provides($type) {
    $t= strtr($type, '.', '\\');
    if (isset($this->annotations[$t])) return true;

    $n= lcfirst(false === ($p= strrpos($t, '\\')) ? $t : substr($t, $p + 1));
    return isset($this->annotations[$n]);
  }

  /**
   * Returns an annotation for a given type
   *
   * @param  string $type
   * @return ?lang.reflection.Annotation
   */
  public function type($type) {
    $t= strtr($type, '.', '\\');
    if (isset($this->annotations[$t])) return new Annotation($t, $this->annotations[$t]);

    $n= lcfirst(false === ($p= strrpos($t, '\\')) ? $t : substr($t, $p + 1));
    return isset($this->annotations[$n]) ? new Annotation($n, $this->annotations[$n]) : null;
  }
}