<?php namespace lang\reflection;

use lang\XPException;

/** Indicates instantiating a type failed because of preconditions */
class CannotInstantiate extends XPException {
  private $type;

  /** Creates a new instance */
  public function __construct(Type $type, $cause= null) {
    parent::__construct('Cannot instantiate '.$type->name(), $cause);
    $this->type= $type;
  }

  /** Returns the type */
  public function type(): Type { return $this->type; }

}