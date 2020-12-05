<?php namespace lang\annotations;

/**
 * Returns annotations from `xp::$meta` if it present there, delegating
 * it to another source otherwise.
 */
class FromMeta {
  private $delegate;

  public function __construct($delegate) {
    $this->delegate= $delegate;
  }

  public function evaluate($reflect, $code) {
    return $this->delegate->evaluate($reflect, $code);
  }

  /**
   * Constructs annotations from meta information
   *
   * @param  [:var] $meta
   * @return [:var]
   */
  private function annotations($meta) {
    $r= [];
    foreach ($meta[DETAIL_ANNOTATIONS] as $name => $value) {
      $r[$meta[DETAIL_TARGET_ANNO][$name] ?? $name]= (array)$value;
    }
    return $r;
  }

  /** @return iterable */
  public function ofType($reflect) {
    $meta= \xp::$meta[strtr($reflect->name, '\\', '.')]['class'] ?? null;
    return $meta ? $this->annotations($meta) : $this->delegate->ofType($reflect);
  }

  /** @return iterable */
  public function ofConstant($reflect) {
    $meta= \xp::$meta[strtr($reflect->getDeclaringClass()->name, '\\', '.')]['class'][2][$reflect->name] ?? null;
    return $meta ? $this->annotations($meta) : $this->delegate->ofConstant($reflect);
  }

  /** @return iterable */
  public function ofProperty($reflect) {
    $meta= \xp::$meta[strtr($reflect->getDeclaringClass()->name, '\\', '.')]['class'][0][$reflect->name] ?? null;
    return $meta ? $this->annotations($meta) : $this->delegate->ofProperty($reflect);
  }

  /** @return iterable */
  public function ofMethod($reflect) {
    $meta= \xp::$meta[strtr($reflect->getDeclaringClass()->name, '\\', '.')]['class'][1][$reflect->name] ?? null;
    return $meta ? $this->annotations($meta) : $this->delegate->ofMethod($reflect);
  }
}