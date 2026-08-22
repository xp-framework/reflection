<?php namespace lang\reflection;

use ReflectionClass;
use lang\{Reflection, Generic, Type, XPClass};

class GenericMethod extends Method {
  private $arguments;

  public function __construct($reflect, array $arguments) {
    parent::__construct($reflect);
    $this->arguments= $arguments;
  }

  /** Returns whether this type is generic */
  public function generic() { return true; }

  /** @return lang.reflection.Method */
  public function definition() { return new Method($this->reflect); }

  /** @return lang.Type[] */
  public function arguments() { return $this->arguments; }

  /**
   * Returns context for `Type::resolve()`
   *
   * @return [:function(?string): Type]
   */
  public function resolve() {
    $declared= $this->reflect->getDeclaringClass();
    $resolve= [
      'static' => fn() => new XPClass($this->definitionOf($this->reflect->class)),
      'self'   => fn() => new XPClass($this->definitionOf($declared->name)),
      'parent' => fn() => new XPClass(get_parent_class($this->definitionOf($declared->name))),
      '*'      => function($type) use($declared) {
        $imports= Reflection::meta()->scopeImports($declared);
        return XPClass::forName($imports[$type] ?? $declared->getNamespaceName().'\\'.$type);
      },
    ];

    // Add generic type parameters
    $generic= Reflection::meta()->methodAnnotations($this->reflect)[Generic::class]['self'];
    foreach (Type::split($generic) as $p => $arg) {
      $resolve[$arg]= fn() => $this->arguments[$p];
    }
    return $resolve;
  }

  /**
   * Returns a closure
   *
   * @param  ?object $instance
   * @return Closure
   * @throws lang.IllegalArgumentException for incorrect or missing instances
   */
  public function closure(?object $instance= null) {
    return fn(... $args) => parent::closure($instance)($this->arguments, ...$args);
  }

  /**
   * Invokes this method
   *
   * @param  ?object $instance
   * @param  var[] $args
   * @return var
   * @throws lang.reflection.CannotInvoke if prerequisites to the invocation fail
   * @throws lang.reflection.InvocationFailed if invocation raises an exception
   */
  public function invoke(?object $instance, $args= []) {
    return parent::invoke($instance, [$this->arguments, ...$args]);
  }
}