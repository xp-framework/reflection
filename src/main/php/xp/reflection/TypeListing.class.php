<?php namespace xp\reflection;

use lang\reflection\Modifiers;

abstract class TypeListing {
  protected $flags;

  protected function list($out, $separator, $types) {
    $order= [
      'public interface'      => [],
      'public trait'          => [],
      'public abstract enum'  => [],
      'public enum'           => [],
      'public abstract class' => [],
      'public class'          => [],
    ];

    // List readonly and final types alongside others
    $join= MODIFIER_READONLY | MODIFIER_FINAL;
    foreach ($types as $type) {
      $names= Modifiers::namesOf($type->modifiers()->bits() & ~$join);
      $order[$names.' '.$type->kind()->name()][$type->name()]= $type;
    }

    foreach ($order as $type => $byName) {
      if (empty($byName)) continue;
      $line= $out->separator($separator);

      ksort($byName);
      $separator= 0;
      foreach ($byName as $type) {
        if ($this->flags & Information::DOC) {
          $out->separator(!$line) || $line= false;

          $comment= $type->comment() ?? '(Undocumented)';
          $p= strpos($comment, "\n\n");
          $s= min(strpos($comment, '. ') ?: $p, strpos($comment, ".\n") ?: $p);

          if (false === $s || $s > $p) {
            $purpose= false === $p ? trim($comment) : substr($comment, 0, $p);
          } else {
            $purpose= substr($comment, 0, $s);
          }
          $out->documentation(str_replace(["\n", '  '], [' ', ' '], trim($purpose)), '  ');
        }

        $out->line('  ', $type->modifiers()->names(true).' '.$type->kind()->name().' '.$type->name());
        $separator++;
      }
    }
  }
}