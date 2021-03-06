<?php namespace lang\meta;

use lang\ast\{Language, Tokens, Visitor, Code};

/**
 * Parses annotations from AST, using PHP language syntax.
 *
 * @see  https://github.com/xp-framework/ast
 */
class FromSyntaxTree {
  const CACHE_SIZE = 16;
  private static $lang;
  private $cache= [];

  static function __static() {
    self::$lang= Language::named('PHP');

    // Parse lambdas and closures into code
    self::$lang->prefix('fn', 0, function($parse, $token) {
      $signature= $this->signature($parse);
      $parse->expecting('=>', 'fn');

      // Parse rest
      $code= '';
      do {
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
    self::$lang->prefix('function', 0, function($parse, $token) {
      $signature= $this->signature($parse);

      // Parse rest
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

  public function evaluate($reflect, $code) {
    $tree= $this->tree($reflect->name);
    $parsed= self::$lang->parse(new Tokens($code.';', '(evaluated)'), $tree->resolver())->tree()->children();
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
  private function annotations($tree, $annotated) {
    $r= [];
    foreach ((array)$annotated->annotations as $type => $arguments) {
      if ('eval' === key($arguments)) {
        $parsed= self::$lang->parse(new Tokens($arguments['eval']->visit($tree).';', '(evaluated)'), $tree->resolver());
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
    $tree= $this->tree($reflect->name);
    return $this->annotations($tree, $tree->type());      
  }

  /** @return iterable */
  public function ofConstant($reflect) {
    $tree= $this->tree($reflect->getDeclaringClass()->name);
    return $this->annotations($tree, $tree->type()->constant($reflect->name));
  }

  /** @return iterable */
  public function ofProperty($reflect) {
    $tree= $this->tree($reflect->getDeclaringClass()->name);
    return $this->annotations($tree, $tree->type()->property($reflect->name));
  }

  /** @return iterable */
  public function ofMethod($reflect) {
    $tree= $this->tree($reflect->getDeclaringClass()->name);
    return $this->annotations($tree, $tree->type()->method($reflect->name));
  }

  /** @return iterable */
  public function ofParameter($method, $reflect) {
    $tree= $this->tree($method->getDeclaringClass()->name);
    return $this->annotations($tree, $tree->type()
      ->method($method->name)
      ->signature
      ->parameters[$reflect->getPosition()]
    );
  }
}