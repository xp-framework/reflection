<?php namespace xp\reflection;

use lang\{ClassLoader, Reflection};

class PackageInformation {
  private $package, $flags;

  /** @param string $package */
  public function __construct($package, $flags) {
    $this->package= Reflection::of($package);
    $this->flags= $flags;
  }

  /** @return iterable */
  public function sources() {
    yield from $this->package->classLoaders();
  }

  public function display($out) {
    $out->format('package %s {', $this->package->name());
    $i= 0;
    foreach ($this->package->children() as $package) {
      $out->line('  package '.$package->name());
      $i++;
    }

    // Compile types into a custom order
    $order= ['interface' => [], 'trait' => [], 'enum' => [], 'class' => []];
    foreach ($this->package->types() as $type) {
      $order[$type->kind()->name()][$type->name()]= $type;
    }
    foreach ($order as $type => $types) {
      if (empty($types)) continue;
      if ($i) $out->line();

      ksort($types);
      $i= 0;
      foreach ($types as $type) {
        if ($this->flags & Information::DOC && ($comment= $type->comment())) {
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

        $out->line('  ', $type->modifiers()->names(true).' '.$type->kind()->name().' '.$type->name());
        $i++;
      }
    }

    $out->line('}');
  }
}