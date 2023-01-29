<?php namespace lang\reflection;

use ReflectionClass;
use lang\XPException;

/** Indicates instantiating a type failed because of preconditions */
class CannotInstantiate extends XPException {
  private $type;

  /**
   * Creates a new instance
   *
   * @param  string|ReflectionClass|lang.reflection.Type $type
   * @param  ?lang.Throwable $cause
   */
  public function __construct($type, $cause= null) {
    if ($type instanceof Type) {
      $this->type= $type;
    } else if ($type instanceof ReflectionClass) {
      $this->type= new Type($type);
    } else {
      $this->type= new Type(new ReflectionClass($type));
    }

    parent::__construct('Cannot instantiate '.$this->type->name(), $cause);
  }

  /** Returns the type */
  public function type(): Type { return $this->type; }

}