<?php namespace lang\meta;

/**
 * Returns annotations from `xp::$meta` if it present there, delegating
 * it to another source otherwise.
 */
class MetaInformation {
  private $annotations;

  public function __construct($annotations) {
    $this->annotations= $annotations;
  }

  public function evaluate($reflect, $code) {
    return $this->annotations->evaluate($reflect, $code);
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
    if ($meta= \xp::$meta[strtr($reflect->name, '\\', '.')]['class'] ?? null) {
      return [DETAIL_ANNOTATIONS => $this->annotations($meta)];
    }

    return [DETAIL_ANNOTATIONS => $this->annotations->ofType($reflect)];
  }

  /** @return iterable */
  public function ofConstant($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][2][$reflect->name] ?? null) {
      return [DETAIL_ANNOTATIONS => $this->annotations($meta)];
    }

    return [DETAIL_ANNOTATIONS => $this->annotations->ofConstant($reflect)];
  }

  /** @return iterable */
  public function ofProperty($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][0][$reflect->name] ?? null) {
      return [DETAIL_ANNOTATIONS => $this->annotations($meta), DETAIL_RETURNS => $meta[DETAIL_RETURNS]];
    }

    return [DETAIL_ANNOTATIONS => $this->annotations->ofProperty($reflect)];
  }

  /** @return iterable */
  public function ofMethod($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][1][$reflect->name] ?? null) {
      return [DETAIL_ANNOTATIONS => $this->annotations($meta), DETAIL_RETURNS => $meta[DETAIL_RETURNS]];
    }

    return [DETAIL_ANNOTATIONS => $this->annotations->ofMethod($reflect)];
  }

  /** @return iterable */
  public function ofParameter($method, $reflect) {
    $c= strtr($method->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][1][$method->name] ?? null) {
      if ($param= $meta[DETAIL_TARGET_ANNO]['$'.$reflect->name] ?? null) {
        $r= [];
        foreach ($param as $name => $value) {
          $r[$meta[DETAIL_TARGET_ANNO][$name] ?? $name]= (array)$value;
        }
        return [DETAIL_ANNOTATIONS => $r, DETAIL_RETURNS => $meta[DETAIL_ARGUMENTS][$reflect->getPosition()]];
      }
    }
    return [DETAIL_ANNOTATIONS => $this->annotations->ofParameter($method, $reflect)];
  }
}