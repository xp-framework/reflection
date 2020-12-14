<?php namespace lang\reflection;

use lang\XPException;

class InvocationFailed extends XPException {
  private $target;

  /** Creates a new instance */
  public function __construct(Routine $target, $cause= null) {
    parent::__construct('Failed invoking '.$target->compoundName(), $cause);
    $this->target= $target;
  }

  /** Returns the target whose invocation failed */
  public function target(): Routine { return $this->target; }
}