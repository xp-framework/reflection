<?php namespace xp\reflection;

abstract class TypeInformation {
  protected $type, $flags;

  public function __construct($type, $flags= 0) {
    $this->type= $type;
    $this->flags= $flags;
  }

  public function sources() { return [$this->type->classLoader()]; }

  protected function documentation($out, $element) {
    if ($this->flags & Information::DOC && ($comment= $element->comment())) {
      $out->documentation($comment);
    }
  }

  protected function member($out, $member) {
    if ($this->flags & Information::DOC && ($comment= $member->comment())) {

      // Handle short-form @param / @return doc comments; otherwise extract
      // first sentence.
      if (0 === strncmp($comment, '@param', 6)) {
        $out->line();
        $out->documentation('Accepts '.substr($comment, 7), '  ');
      } else if (0 === strncmp($comment, '@return', 7)) {
        $out->line();
        $out->documentation('Returns '.substr($comment, 8), '  ');
      } else if ('@' === $comment[0]) {
        $out->line();
        $out->documentation(ucfirst(substr($comment, 1)));
      } else {
        $out->line();
        $p= strpos($comment, "\n\n");
        $s= min(strpos($comment, '. ') ?: $p, strpos($comment, ".\n") ?: $p);

        if (false === $s || $s > $p) {
          $purpose= false === $p ? trim($comment) : substr($comment, 0, $p);
        } else {
          $purpose= substr($comment, 0, $s);
        }
        $out->documentation(str_replace(["\n", '  '], [' ', ' '], trim($purpose)), '  ');
      }
    }
    $out->line('  ', $member);
  }

  protected function extends($type) {
    $p= $type->parent();
    return $p ? ' extends '.$p->name() : '';
  }

  protected function implements($type) {
    $i= $type->interfaces();
    return $i ? ' implements '.implode(', ', array_map(fn($t) => $t->name(), $i)) : '';
  }

  protected function parents($type) {
    $i= $type->interfaces();
    return $i ? ' extends '.implode(', ', array_map(fn($t) => $t->name(), $i)) : '';
  }

  protected function partition($members) {
    $r= ['class' => [], 'instance' => []];
    foreach ($members as $member) {
      $m= $member->modifiers(); 
      if ($this->flags & Information::ALL || $m->isPublic()) {
        $r[$m->isStatic() ? 'class' : 'instance'][]= $member;
      }
    }
    return $r;
  }
}