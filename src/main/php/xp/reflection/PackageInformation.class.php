<?php namespace xp\reflection;

use lang\{ClassLoader, Reflection};

class PackageInformation {
  private $package, $flags;

  /** @param string $package */
  public function __construct($package, $flags) {
    $this->package= rtrim($package, '.');
    $this->flags= $flags;
  }

  /** @return iterable */
  public function sources() {
    foreach (ClassLoader::getLoaders() as $loader) {
      if ($loader->providesPackage($this->package)) yield $loader;
    }
  }

  public function display($out) {
    $out->format('package %s {', $this->package);

    $ext= strlen(\xp::CLASS_FILE_EXT);
    $order= [
      'interface' => [],
      'trait'     => [],
      'enum'      => [],
      'class'     => []
    ];

    // Child packages
    $loader= ClassLoader::getDefault();
    $base= $this->package.'.';
    $i= 0;
    foreach ($loader->packageContents($this->package) as $entry) {
      if ('/' === $entry[strlen($entry) - 1]) {
        $out->line('  package '.$base.substr($entry, 0, -1));
        $i++;
      } else if (0 === substr_compare($entry, \xp::CLASS_FILE_EXT, -$ext)) {
        $type= Reflection::of($loader->loadClass0($base.substr($entry, 0, -$ext)));
        $order[$type->kind()->name()][]= $type;
      }
    }

    // Enumerate types - ordered by type, then by name
    foreach ($order as $type => $types) {
      if (empty($types)) continue;
      if ($i) $out->line();

      usort($types, function($a, $b) { return $a->name() <=> $b->name(); });
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