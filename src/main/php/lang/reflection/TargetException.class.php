<?php namespace lang\reflection;

use lang\XPException;

abstract class TargetException extends XPException {
  private $target;

  /** Creates a new instance */
  public function __construct(Member $target, $cause= null) {
    parent::__construct(static::MESSAGE.' '.$target->compoundName(), $cause);
    $this->target= $target;
  }

  /** Returns the target member */
  public function target(): Member { return $this->target; }

}