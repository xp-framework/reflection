<?php namespace lang\reflection;

use lang\Type;

class Constraint {
  private $type, $present;

  public function __construct(Type $type, bool $present= true) {
    $this->type= $type;
    $this->present= $present;
  }

  /** @return lang.Type */
  public function type() { return $this->type; }

  /** @return bool */
  public function present() { return $this->present; }
}
