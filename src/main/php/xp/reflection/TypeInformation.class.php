<?php namespace xp\reflection;

abstract class TypeInformation {
  protected $type;

  public function __construct($type) {
    $this->type= $type;
  }

  public function sources() { return [$this->type->classLoader()]; }

  protected function extends($type) {
    $p= $type->parent();
    return $p ? ' extends '.$p->name() : '';
  }

  protected function implements($type) {
    $i= $type->interfaces();
    return $i ? ' implements '.implode(', ', array_map(function($t) { return $t->name(); }, $i)) : '';
  }

  protected function parents($type) {
    $i= $type->interfaces();
    return $i ? ' extends '.implode(', ', array_map(function($t) { return $t->name(); }, $i)) : '';
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