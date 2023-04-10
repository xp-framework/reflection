<?php namespace lang\meta;

Use lang\reflection\Modifiers;

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
      $qname= $meta[DETAIL_TARGET_ANNO][$name] ?? $name;
      $r[$qname]= isset($meta[DETAIL_TARGET_ANNO][$qname]) ? [$value] : (array)$value;
    }
    return $r;
  }

  /**
   * Parses tags from API documentation
   *
   * @param  \ReflectionClass|\ReflectionClassConstant|\ReflectionProperty|\ReflectionMethod $reflect
   * @return [:var]
   */
  private function tags($reflect) {
    preg_match_all('/@([a-z]+)\s+(.+)/', $reflect->getDocComment(), $matches, PREG_SET_ORDER);
    $tags= [];
    foreach ($matches as $match) {
      $tags[$match[1]][]= rtrim($match[2], ' */');
    }
    return $tags;
  }

  /**
   * Returns a list of imports from the scope the given class was declared in
   *
   * @param  \ReflectionClass $reflect
   * @return [:string]
   */
  public function scopeImports($reflect) {
    $meta= &\xp::$meta[strtr($reflect->name, '\\', '.')];
    return $meta['use'] ?? $meta['use']= $this->annotations->imports($reflect);
  }

  /**
   * Returns annotation map (type => arguments) for a given type
   *
   * @param  \ReflectionClass $reflect
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
   * @param  \ReflectionClass $reflect
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

  /**
   * Returns annotation map (type => arguments) for a given constant
   *
   * @param  \ReflectionClassConstant $reflect
   * @return [:var[]]
   */
  public function constantAnnotations($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][2][$reflect->name] ?? null) {
      return $this->annotations($meta);
    } else {
      return $this->annotations->ofConstant($reflect);
    }
  }

  /**
   * Returns type for a given constant
   *
   * @see    https://stackoverflow.com/questions/3892063/phpdoc-class-constants-documentation
   * @param  \ReflectionClassConstant $reflect
   * @return ?string
   */
  public function constantType($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][2][$reflect->name] ?? null) {
      return $meta[DETAIL_RETURNS];
    } else {
      $tags= $this->tags($reflect);
      return $tags['var'][0] ?? $tags['type'][0] ?? null;
    }
  }

  /**
   * Returns comment for a given constant
   *
   * @param  \ReflectionClassConstant $reflect
   * @return ?string
   */
  public function constantComment($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][2][$reflect->name] ?? null) {
      return $meta[DETAIL_COMMENT];
    } else if (false === ($c= $reflect->getDocComment())) {
      return null;
    } else {
      return trim(preg_replace('/\n\s+\* ?/', "\n", substr($c, 3, -2)));
    }
  }

  /**
   * Returns annotation map (type => arguments) for a given property
   *
   * @param  \ReflectionProperty $reflect
   * @return [:var[]]
   */
  public function propertyAnnotations($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][0][$reflect->name] ?? null) {
      return $this->annotations($meta);
    } else {
      return $this->annotations->ofProperty($reflect);
    }
  }

  /**
   * Returns type for a given property
   *
   * @param  \ReflectionProperty $reflect
   * @return ?string
   */
  public function propertyType($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][0][$reflect->name] ?? null) {
      return $meta[DETAIL_RETURNS];
    } else {
      $tags= $this->tags($reflect);
      return $tags['var'][0] ?? $tags['type'][0] ?? null;
    }
  }

  /**
   * Returns comment for a given property
   *
   * @param  \ReflectionProperty $reflect
   * @return ?string
   */
  public function propertyComment($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][0][$reflect->name] ?? null) {
      return $meta[DETAIL_COMMENT];
    } else if (false === ($c= $reflect->getDocComment())) {
      return null;
    } else {
      return trim(preg_replace('/\n\s+\* ?/', "\n", substr($c, 3, -2)));
    }
  }

  /**
   * Returns annotation map (type => arguments) for a given method
   *
   * @param  \ReflectionMethod $reflect
   * @return [:var[]]
   */
  public function methodAnnotations($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][1][$reflect->name] ?? null) {
      return $this->annotations($meta);
    } else {
      return $this->annotations->ofMethod($reflect);
    }
  }

  /**
   * Returns return type for a given method
   *
   * @param  \ReflectionMethod $reflect
   * @return ?string
   */
  public function methodReturns($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][1][$reflect->name] ?? null) {
      return $meta[DETAIL_RETURNS];
    } else {
      return $this->tags($reflect)['return'][0] ?? null;
    }
  }

  /**
   * Returns comment for a given method
   *
   * @param  \ReflectionMethod $reflect
   * @return ?string
   */
  public function methodComment($reflect) {
    $c= strtr($reflect->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][1][$reflect->name] ?? null) {
      return $meta[DETAIL_COMMENT];
    } else if (false === ($c= $reflect->getDocComment())) {
      return null;
    } else {
      return trim(preg_replace('/\n\s+\* ?/', "\n", substr($c, 3, -2)));
    }
  }

  /**
   * Returns parameter types for a given method
   *
   * @param  \ReflectionMethod $method
   * @return string[]
   */
  public function methodParameterTypes($method) {
    $c= strtr($method->getDeclaringClass()->name, '\\', '.');
    if ($meta= \xp::$meta[$c][1][$method->name][DETAIL_ARGUMENTS] ?? null) return $meta;

    $r= [];
    foreach ($this->tags($method)['param'] ?? [] as $tag) {
      $r[]= false === ($p= strpos($tag, ' $')) ? $tag : substr($tag, 0, $p);
    }
    return $r;
  }

  /**
   * Returns annotation map (type => arguments) for a given method
   *
   * @param  \ReflectionMethod $method
   * @param  \ReflectionParameter $reflect
   * @return [:var[]]
   */
  public function parameterAnnotations($method, $reflect) {
    $c= strtr($method->getDeclaringClass()->name, '\\', '.');
    if ($target= \xp::$meta[$c][1][$method->name][DETAIL_TARGET_ANNO] ?? null) {
      if ($param= $target['$'.$reflect->name] ?? null) {
        $r= [];
        foreach ($param as $name => $value) {
          $r[$target[$name] ?? $name]= (array)$value;
        }
        return $r;
      }
    }
    return $this->annotations->ofParameter($method, $reflect);
  }

  /**
   * Returns virtual properties for a given type
   *
   * @param  \ReflectionClass $reflect
   * @return [:var[]]
   */
  public function virtualProperties($reflect) {
    $r= [];
    do {

      // If meta information is already loaded, use property arguments
      if ($meta= \xp::$meta[strtr($reflect->name, '\\', '.')][0] ?? null) {
        foreach ($meta as $name => $property) {
          if ($arg= $property[DETAIL_ARGUMENTS] ?? null) {
            $r[$name]= [$arg[0], $property[DETAIL_RETURNS]];
          }
        }
        continue;
      }

      // Parse doc comment
      $comment= $reflect->getDocComment();
      if (null === $comment) continue;

      preg_match_all('/@property(\-read|\-write)? (.+) \$([^ ]+)/', $comment, $matches, PREG_SET_ORDER);
      $r= [];
      foreach ($matches as $match) {
        $r[$match[3]]= [Modifiers::IS_PUBLIC | ('-read' === $match[1] ? Modifiers::IS_READONLY : 0), $match[2]];
      }
    } while ($reflect= $reflect->getParentclass());

    return $r;
  }
}