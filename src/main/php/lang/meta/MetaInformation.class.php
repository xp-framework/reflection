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

  public function tags($reflect) {
    preg_match_all('/@([a-z]+)\s+(.+)/', $reflect->getDocComment(), $matches, PREG_SET_ORDER);
    $tags= [];
    foreach ($matches as $match) {
      $tags[$match[1]][]= rtrim($match[2], ' */');
    }
    return $tags;
  }

  /**
   * Returns annotation map (type => arguments) for a given type
   *
   * @param  ReflectionClass $reflect
   * @return [:var[]]
   */
  public function typeAnnotations($reflect) {
    if ($meta= \xp::$meta[strtr($reflect->name, '\\', '.')]['class'] ?? null) {
      return $this->annotations($meta);
    } else {
      return $this->annotations->ofType($reflect);
    }
  }

  /**
   * Returns API doc comment for a given type
   *
   * @param  ReflectionClass $reflect
   * @return ?string
   */
  public function typeComment($reflect) {
    if ($meta= \xp::$meta[strtr($reflect->name, '\\', '.')]['class'] ?? null) {
      return $meta[DETAIL_COMMENT];
    } else if (false === ($c= $reflect->getDocComment())) {
      return null;
    } else {
      return trim(preg_replace('/\n\s+\* ?/', "\n", substr($c, 3, -2)));
    }
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

    return [
      DETAIL_ANNOTATIONS => $this->annotations->ofProperty($reflect),
      DETAIL_RETURNS     => $this->tags($reflect)['type'][0] ?? null
    ];
  }

  /** @return iterable */
  public function ofMethod($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][1][$reflect->name] ?? null) {
      return [DETAIL_ANNOTATIONS => $this->annotations($meta), DETAIL_RETURNS => $meta[DETAIL_RETURNS]];
    }

    return [
      DETAIL_ANNOTATIONS => $this->annotations->ofMethod($reflect),
      DETAIL_RETURNS     => $this->tags($reflect)['return'][0] ?? null
    ];
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

    if ($tag= $this->tags($method)['param'][$reflect->getPosition()] ?? null) {
      preg_match('/([^ ]+)( \$?[a-z_]+)/i', $tag, $matches);
      $type= $matches[1];
    } else {
      $type= null;
    }
    return [
      DETAIL_ANNOTATIONS => $this->annotations->ofParameter($method, $reflect),
      DETAIL_RETURNS     => $type
    ];
  }
}