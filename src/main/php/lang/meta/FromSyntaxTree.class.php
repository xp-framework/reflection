<?php namespace lang\meta;

use lang\ast\nodes\{ArrayLiteral, FunctionDeclaration};
use lang\ast\{Language, Token, Tokens, Visitor, Code};
use lang\{IllegalArgumentException, IllegalStateException};

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

  /** Locates a declared partial of a given class */
  private function partial($tree, $class) {
    foreach ($class->getTraitNames() as $declared) {
      if ($type= $tree->type($declared)) return $type;
    }
    throw new IllegalStateException('No part of '.$class->name.' declared in tree');
  }

  /** Locates an anonymous class creation expression */
  private function anonymous($tree, $start, $end) {
    foreach ($tree->children() as $child) {
      yield from $this->anonymous($child, $start, $end);
      if ('newclass' === $child->kind && $child->line >= $start && $child->line <= $end) yield $child;
    }
  }

  /** Returns the syntax tree for a given type using a cache */
  private function treeOf($class, $file) {
    if (null === ($tree= $this->cache[$file] ?? null)) {
      $this->cache[$file]= $tree= self::$lang->parse(new Tokens(file_get_contents($file), $file))->tree();
      if (sizeof($this->cache) > self::CACHE_SIZE) unset($this->cache[key($this->cache)]);
    }

    return new SyntaxTree($tree, $class->isAnonymous()
      ? $this->anonymous($tree, $class->getStartLine(), $class->getEndLine())->current()->definition
      : $tree->type($class->name) ?? $this->partial($tree, $class)
    );
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
            case ')': $b--; if ($b < 0) break 2; else break;
            case '[': $c++; break;
            case ']': $c--; if ($c < 0) break 2; else break;
            case ',': if ($c <= 0 && $b <= 0) break 2; else break; // outside of arrays or argument lists
            case '$': $parse->forward(); $parse->token->value= '$'.$parse->token->value; break;
          }
          $code.= ' '.$parse->token->value;
          $parse->forward();
        } while (';' !== $parse->token->value);

        $params= '';
        foreach ($signature->parameters as $param) {
          $params.=
            ', '.
            ($param->type ? $param->type->literal() : '').
            ($param->variadic ? '...' : '').
            ($param->reference ? ' &$': ' $').
            $param->name.
            ($param->default ? '='.$param->default->expression : '')
          ;
        }
        $return= $signature->returns ? ':'.$signature->returns->literal() : '';
        if (0 === strncmp($code, ' throw ', 7)) {
          return new Code('function('.substr($params, 2).')'.$return.' {'.$code.'; }');
        } else {
          return new Code('function('.substr($params, 2).')'.$return.' { return'.$code.'; }');
        }
      });

      $function= function($parse, $token) {
        $signature= $this->signature($parse);

        // Parse body
        $code= '';
        $b= 0;
        do {
          if ('{' === $parse->token->value) {
            $b++;
          } else if ('}' === $parse->token->value) {
            if (0 === --$b) break;
          } else if ('$' === $parse->token->value) {
            $parse->forward();
            $parse->token->value= '$'.$parse->token->value;
          }
          $code.= ' '.$parse->token->value;
          $parse->forward();
        } while (null !== $parse->token->value);
        $parse->forward();

        $params= '';
        foreach ($signature->parameters as $param) {
          $params.=
            ', '.
            ($param->type ? $param->type->literal() : '').
            ($param->variadic ? '...' : '').
            ($param->reference ? ' &$': ' $').
            $param->name.
            ($param->default ? '='.$param->default->expression : '')
          ;
        }
        $return= $signature->returns ? ':'.$signature->returns->literal() : '';
        return new Code('function('.substr($params, 2).')'.$return.$code.' }');
      };

      // Function expressions and function expressions used as statement
      self::$parse->prefix('function', 0, $function);
      self::$parse->stmt('function', function($parse, $token) use($function) {
        if ('(' === $parse->token->value) return $function->call($this, $parse, $token);
        
        $name= $parse->token->value;
        $parse->forward();
        $signature= $this->signature($parse);
        $parse->expecting('{', 'function');
        $statements= $this->statements($parse);
        $parse->expecting('}', 'function');

        return new FunctionDeclaration($name, $signature, $statements, $token->line);
      });
    }
    return self::$parse->parse(new Tokens($code.';', '(evaluated)'), $resolver);
  }

  public function evaluate($arg, $code) {
    $tree= $arg instanceof SyntaxTree ? $arg : $this->treeOf($arg, $arg->getFileName());
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
  private function annotations($tree, $annotated) {
    if (null === $annotated->annotations) return [];

    $r= [];
    foreach ($annotated->annotations as $type => $args) {
      $ptr= &$r[$type];

      if (!isset($args['eval'])) {
        $ptr= [];
        foreach ($args as $name => $argument) {
          $ptr[$name]= $argument->visit($tree);
        }
      } else if ($args['eval'] instanceof ArrayLiteral) {
        $ptr= [];
        $i= 0;
        foreach ($args['eval']->values as list($key, $value)) {
          $ptr[$key ? $key->visit($tree) : $i++]= $this->evaluate($tree, $value->visit($tree).';');
        }
      } else {
        $ptr= [$this->evaluate($tree, $args['eval']->visit($tree).';')];
      }
    }
    return $r;
  }

  public function imports($reflect) {
    $resolver= $this->treeOf($reflect, $reflect->getFileName())->resolver();
    $imports= [];
    foreach ($resolver->imports as $alias => $type) {
      $imports[$alias]= ltrim($type, '\\');
    }
    return $imports;
  }

  /** @return iterable */
  public function ofType($reflect) {
    $tree= $this->treeOf($reflect, $reflect->getFileName());
    return $this->annotations($tree, $tree->type());      
  }

  /** @return iterable */
  public function ofConstant($reflect) {
    $class= $reflect->getDeclaringClass();
    $tree= $this->treeOf($class, $class->getFileName());
    return $this->annotations($tree, $tree->type()->constant($reflect->name));
  }

  /** @return iterable */
  public function ofProperty($reflect) {
    $class= $reflect->getDeclaringClass();
    $tree= $this->treeOf($class, $class->getFileName());
    return $this->annotations($tree, $tree->type()->property($reflect->name));
  }

  /** @return iterable */
  public function ofMethod($reflect) {
    $tree= $this->treeOf($reflect->getDeclaringClass(), $reflect->getFileName());
    return $this->annotations($tree, $tree->type()->method($reflect->name));
  }

  /** @return iterable */
  public function ofParameter($method, $reflect) {
    $class= $reflect->getDeclaringClass();
    $tree= $this->treeOf($class, $class->getMethod($method->name)->getFileName());
    return $this->annotations($tree, $tree->type()
      ->method($method->name)
      ->signature
      ->parameters[$reflect->getPosition()]
    );
  }
}