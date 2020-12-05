<?php namespace xp\reflection;

abstract class TypeInformation {
  protected $type;

  public function __construct($type) {
    $this->type= $type;
  }

  protected function partition($members, $all= false) {
    $r= ['class' => [], 'instance' => []];
    foreach ($members as $member) {
      $m= $member->modifiers(); 
      if ($all ? true : $m->isPublic()) {
        $r[$member->modifiers()->isStatic() ? 'class' : 'instance'][]= $member;
      }
    }
    return $r;
  }
}