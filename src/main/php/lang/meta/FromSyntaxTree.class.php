<?php namespace lang\meta;

use lang\IllegalArgumentException;
use lang\ast\{Language, Tokens, Visitor, Code};

/**
 * Parses annotations from AST, using PHP language syntax.
 *
 * @see  https://github.com/xp-framework/ast
 */
class FromSyntaxTree {
  const CACHE_SIZE= 16;

  private static $lang;
  private static $parse= null;
  private $cache= [];

  static function __static() {
    self::$lang= Language::named('PHP');
  }

  private function tree($name) {
    if (!isset($this->cache[$name])) {
      $class= strtr($name, '\\', '.');
      sscanf(\xp::$cl[$class], '%[^:]://%[^$]', $cl, $argument);
      $instanceFor= [literal($cl), 'instanceFor'];
      $bytes= $instanceFor($argument)->loadClassBytes($class);

      // Limit cache
      $this->cache[$name]= new SyntaxTree(self::$lang->parse(new Tokens($bytes, $class))->tree(), $name);
      if (sizeof($this->cache) > self::CACHE_SIZE) unset($this->cache[key($this->cache)]);
    }
    return $this->cache[$name];
  }

  private function parse($code, $resolver) {
    if (null === self::$parse) {

      // Parse lambdas and closures into code
      self::$parse= clone self::$lang;
      self::$parse->prefix('fn', 0, function($parse, $token) {
        $signature= $this->signature($parse);
        $parse->expecting('=>', 'fn');

        // Parse expression
        $code= '';
        $b= $c= 0;
        do {
          switch ($parse->token->value) {
            case '(': $b++; break;
            case ')': $b--; if ($b < 0) break 2;
            case '[': $c++; break;
            case ']': $c--; if ($c < 0) break 2;
          }
          $code.= ' '.$parse->token->value;
          $parse->forward();
        } while (';' !== $parse->token->value);

        $params= '';
        foreach ($signature->parameters as $param) {
          $params.= ', $'.$param->name;
        }
        if (0 === strncmp($code, ' throw ', 7)) {
          return new Code('function('.substr($params, 2).') {'.$code.'; }');
        } else {
          return new Code('function('.substr($params, 2).') { return'.$code.'; }');
        }
      });
      self::$parse->prefix('function', 0, function($parse, $token) {
        $signature= $this->signature($parse);

        // Parse body
        $code= '';
        $b= 0;
        do {
          if ('{' === $parse->token->value) {
            $b++;
          } else if ('}' === $parse->token->value) {
            if (0 === --$b) break;
          }
          $code.= ' '.$parse->token->value;
          $parse->forward();
        } while (null !== $parse->token->value);
        $parse->forward();

        $params= '';
        foreach ($signature->parameters as $param) {
          $params.= ', $'.$param->name;
        }
        return new Code('function('.substr($params, 2).')'.$code.' }');
      });
    }
    return self::$parse->parse(new Tokens($code.';', '(evaluated)'), $resolver);
  }

  public function evaluate($reflect, $code) {
    $tree= $this->tree($reflect->name);
    $parsed= self::parse($code, $tree->resolver())->tree()->children();
    if (1 === sizeof($parsed)) {
      return $parsed[0]->visit($tree);
    }

    throw new IllegalArgumentException('Given code must be a single expression');
  }

  /**
   * Evaluates annotation values, including special-case handling for the
   * named argument `eval`.
   *
   * @param  lang.annotations.SyntaxTree $tree
   * @param  lang.ast.nodes.Annotated $annotated
   * @return [:var]
   */
  private function treeAnnotations($tree, $annotated) {
    if (null === $annotated->annotations) return [];

    $r= [];
    foreach ($annotated->annotations as $type => $arguments) {
      if ('eval' === key($arguments)) {
        $parsed= self::parse($arguments['eval']->visit($tree).';', $tree->resolver());
        $r[$type]= [$parsed->tree()->children()[0]->visit($tree)];
      } else {
        $p= &$r[$type];
        $p= [];
        foreach ($arguments as $name => $argument) {
          $p[$name]= $argument->visit($tree);
        }
      }
    }
    return $r;
  }

  /**
   * Constructs annotations from meta information
   *
   * @param  [:var] $meta
   * @return [:var]
   */
  private function metaAnnotations($meta) {
    $r= [];
    foreach ($meta[DETAIL_ANNOTATIONS] as $name => $value) {
      $r[$meta[DETAIL_TARGET_ANNO][$name] ?? $name]= (array)$value;
    }
    return $r;
  }

  public function imports($reflect) {
    $resolver= $this->tree($reflect->name)->resolver();
    $imports= [];
    foreach ($resolver->imports as $alias => $type) {
      $imports[$alias]= ltrim($type, '\\');
    }
    return $imports;
  }

  /** @return iterable */
  public function ofType($reflect) {
    if ($meta= \xp::$meta[strtr($reflect->name, '\\', '.')]['class'] ?? null) {
      return $this->metaAnnotations($meta);
    } else {
      $tree= $this->tree($reflect->name);
      return $this->treeAnnotations($tree, $tree->type());
    }
  }

  /** @return iterable */
  public function ofConstant($reflect) {
    $type= $reflect->getDeclaringClass()->name;
    if ($meta= \xp::$meta[strtr($type, '\\', '.')][2][$reflect->name] ?? null) {
      return $this->metaAnnotations($meta);
    } else {
      $tree= $this->tree($type);
      return $this->treeAnnotations($tree, $tree->type()->constant($reflect->name));
    }
  }

  /** @return iterable */
  public function ofProperty($reflect) {
    $type= $reflect->getDeclaringClass()->name;
    if ($meta= \xp::$meta[strtr($type, '\\', '.')][0][$reflect->name] ?? null) {
      return $this->metaAnnotations($meta);
    } else {
      $tree= $this->tree($type);
      return $this->treeAnnotations($tree, $tree->type()->property($reflect->name));
    }
  }

  /** @return iterable */
  public function ofMethod($reflect) {
    $type= $reflect->getDeclaringClass()->name;
    if ($meta= \xp::$meta[strtr($type, '\\', '.')][1][$reflect->name] ?? null) {
      return $this->metaAnnotations($meta);
    } else {
      $tree= $this->tree($type);
      return $this->treeAnnotations($tree, $tree->type()->method($reflect->name));
    }
  }

  /** @return iterable */
  public function ofParameter($method, $reflect) {
    $type= $reflect->getDeclaringClass()->name;

    if ($target= \xp::$meta[strtr($type, '\\', '.')][1][$method->name][DETAIL_TARGET_ANNO] ?? null) {
      $r= [];
      foreach ($target['$'.$reflect->name] ?? [] as $name => $value) {
        $r[$target[$name] ?? $name]= (array)$value;
      }
      return $r;
    } else {
      $tree= $this->tree($type);
      return $this->treeAnnotations($tree, $tree->type()
        ->method($method->name)
        ->signature
        ->parameters[$reflect->getPosition()]
      );
    }
  }
}