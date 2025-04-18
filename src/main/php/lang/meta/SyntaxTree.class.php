<?php namespace lang\meta;

use ReflectionClass;
use lang\IllegalAccessException;
use lang\ast\nodes\{Literal, Variable, InvokeExpression};
use lang\ast\{Visitor, Type};

class SyntaxTree extends Visitor {
  private $tree, $type;

  public function __construct($tree, $type) {
    $this->tree= $tree;
    $this->type= $type;
  }

  /** @return lang.ast.TypeDeclaration */
  public function type() { return $this->type; }

  public function resolver() { return $this->tree->scope(); }

  private function resolve($type) {
    $name= $type instanceof Type ? $type->literal() : $type;

    if ('self' === $name) {
      $resolved= $this->type->name;
    } else if ('parent' === $name) {
      $resolved= $this->tree->scope()->parent;
    } else {
      return $name;
    }

    return $resolved instanceof Type ? $resolved->literal() : $resolved;
  }

  /**
   * Evaluates code
   *
   * @param  lang.ast.Node $self
   * @return var
   */
  public function code($self) {
    return eval('return '.$self->value.';');
  }

  /**
   * Evaluates literals
   *
   * @param  lang.ast.Node $self
   * @return var
   */
  public function literal($self) {
    return eval('return '.$self->expression.';');
  }

  /**
   * Evaluates arrays
   *
   * @param  lang.ast.Node $self
   * @return var
   */
  public function array($self) {
    $r= [];
    foreach ($self->values as list($key, $value)) {
      if (null === $key) {
        $r[]= $value->visit($this);
      } else {
        $r[$key->visit($this)]= $value->visit($this);
      }
    }
    return $r;
  }

  /**
   * Evaluates new operator
   *
   * @param  lang.ast.Node $self
   * @return var
   */
  public function new($self) {
    $c= new ReflectionClass($this->resolve($self->type));
    $arguments= [];
    foreach ($self->arguments as $key => $node) {
      $arguments[$key]= $node->visit($this);
    }
    return $c->newInstance(...$arguments);
  }

  /**
   * Evaluates scope resolution operators
   *
   * @param  lang.ast.Node $self
   * @return var
   */
  public function scope($self) {
    $c= $this->resolve($self->type);

    // Use PHP reflection API to access members' runtime values
    if ($self->member instanceof Variable) {
      return (new ReflectionClass($c))->getStaticPropertyValue($self->member->pointer ?? $self->member->name);
    } else if ($self->member instanceof Literal) {
      return 'class' === $self->member->expression
        ? substr($c, 1)
        : (new ReflectionClass($c))->getConstant($self->member->expression)
      ;
    } else if ($self->member instanceof InvokeExpression) {
      $arguments= [];
      foreach ($self->member->arguments as $key => $node) {
        $arguments[$key]= $node->visit($this);
      }
      return (new ReflectionClass($c))->getMethod($self->member->expression)->invokeArgs(null, $arguments);
    } else {
      throw new IllegalAccessException('Cannot resolve '.$c.'::'.$self->member->kind);
    }
  }

  /**
   * Evaluates class constants
   *
   * @param  lang.ast.Node $self
   * @return var
   */
  public function const($self) {
    return $self->expression->visit($this);
  }

  /**
   * Evaluates properties
   *
   * @param  lang.ast.Node $self
   * @return var
   */
  public function property($self) {
    return $self->expression->visit($this);
  }

  /**
   * Evaluates unary prefix operators
   *
   * @param  lang.ast.Node $self
   * @return var
   */
  public function prefix($self) {
    switch ($self->operator) {
      case '+': return +$self->expression->visit($this);
      case '-': return -$self->expression->visit($this);
      case '~': return ~$self->expression->visit($this);
      case '!': return !$self->expression->visit($this);
    }
  }

  /**
   * Evaluates binary operators
   *
   * @param  lang.ast.Node $self
   * @return var
   */
  public function binary($self) {
    switch ($self->operator) {
      case '.': return $self->left->visit($this).$self->right->visit($this);
      case '+': return $self->left->visit($this) + $self->right->visit($this);
      case '-': return $self->left->visit($this) - $self->right->visit($this);
      case '*': return $self->left->visit($this) * $self->right->visit($this);
      case '/': return $self->left->visit($this) / $self->right->visit($this);
      case '%': return $self->left->visit($this) % $self->right->visit($this);
      case '^': return $self->left->visit($this) ^ $self->right->visit($this);
      case '|': return $self->left->visit($this) | $self->right->visit($this);
      case '&': return $self->left->visit($this) & $self->right->visit($this);
      case '**': return $self->left->visit($this) ** $self->right->visit($this);
      case '?:': return $self->left->visit($this) ?: $self->right->visit($this);
      case '??': return $self->left->visit($this) ?? $self->right->visit($this);
      case '<<': return $self->left->visit($this) << $self->right->visit($this);
      case '>>': return $self->left->visit($this) >> $self->right->visit($this);
      case '||': return $self->left->visit($this) || $self->right->visit($this);
      case '&&': return $self->left->visit($this) && $self->right->visit($this);
      case '==': return $self->left->visit($this) == $self->right->visit($this);
      case '!=': return $self->left->visit($this) != $self->right->visit($this);
      case '<': return $self->left->visit($this) < $self->right->visit($this);
      case '>': return $self->left->visit($this) > $self->right->visit($this);
      case '<=': return $self->left->visit($this) <= $self->right->visit($this);
      case '>=': return $self->left->visit($this) >= $self->right->visit($this);
      case '<=>': return $self->left->visit($this) <=> $self->right->visit($this);
      case '===': return $self->left->visit($this) === $self->right->visit($this);
      case '!==': return $self->left->visit($this) !== $self->right->visit($this);
    }
  }

  /**
   * Evaluates ternary operators
   *
   * @param  lang.ast.Node $self
   * @return var
   */
  public function ternary($self) {
    return $self->condition->visit($this) ? $self->expression->visit($this) : $self->otherwise->visit($this);
  }
}