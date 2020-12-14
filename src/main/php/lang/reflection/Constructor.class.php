<?php namespace lang\reflection;

use lang\Reflection;

class Constructor extends Routine {
  private $class;

  /** @param ReflectionClass $reflect */
  public function __construct($reflect) {
    parent::__construct($reflect->getMethod('__construct'));
    $this->class= $reflect;
  }

  /** @return string */
  public function toString() {

    // Parse apidoc. FIXME!
    preg_match_all('/@(param)\s+(.+)/', $this->reflect->getDocComment(), $matches, PREG_SET_ORDER);
    $tags= [];
    foreach ($matches as $match) {
      $tags[$match[1]][]= $match[2];
    }

    // Compile signature
    $sig= '';
    foreach ($this->reflect->getParameters() as $i => $parameter) {
      if ($t= $parameter->getType()) {
        $type= strtr(PHP_VERSION_ID >= 70100 ? $t->getName() : $t->__toString(), '\\', '.');
        $parameter->isVariadic() && $type.= '...';
      } else if (isset($tags['param'][$i])) {
        preg_match('/([^ ]+)( \$?[a-z_]+)/i', $tags['param'][$i], $matches);
        $type= $matches[1] ?? $tags['param'][$i];
      } else {
        $type= 'var';
      }
      $sig.= ', '.$type.' $'.$parameter->name;
    }

    return Modifiers::namesOf($this->reflect->getModifiers() & ~0x1fb7f008).' function __construct('.substr($sig, 2).')';
  }

  /**
   * Creates a new instance of the type this constructor belongs to
   *
   * @param  var[] $args
   * @param  ?string|?lang.XPClass|?lang.reflection.Type $context
   * @return object
   * @throws lang.reflection.InvocationFailed
   * @throws lang.reflection.CannotInstantiate
   */
  public function newInstance($args= [], $context= null) {
    try {

      // Workaround for non-public constructors: Set accessible, then manually
      // invoke after creating an instance without invoking the constructor.
      if ($context && !$this->reflect->isPublic()) {
        $t= $context instanceof Type ? $context : Reflection::of($context);
        if ($t->is($this->class->name)) {
          $instance= $this->class->newInstanceWithoutConstructor();
          $this->reflect->setAccessible(true);
          $this->reflect->invokeArgs($instance, $args);
          return $instance;
        }
      }

      return $this->class->newInstanceArgs($args);
    } catch (\ReflectionException $e) {
      throw new CannotInstantiate($this->class->name, $e);
    } catch (\Throwable $e) {
      throw new InvocationFailed($this, $e);
    }
  }
}