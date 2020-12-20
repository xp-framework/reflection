<?php namespace xp\reflection;

use lang\{ClassLoader, Reflection};

class PackageInformation {
  private $package;

  /** @param string $package */
  public function __construct($package) {
    $this->package= rtrim($package, '.');
  }

  /** @return iterable */
  public function sources() {
    foreach (ClassLoader::getLoaders() as $loader) {
      if ($loader->providesPackage($this->package)) yield $loader;
    }
  }

  public function display($flags, $out) {
    $out->writeLinef('package %s {', $this->package);

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
        $out->writeLine('  package '.$base.substr($entry, 0, -1));
        $i++;
      } else if (0 === substr_compare($entry, \xp::CLASS_FILE_EXT, -$ext)) {
        $type= Reflection::of($loader->loadClass($base.substr($entry, 0, -$ext)));
        $order[$type->kind()->name()][]= $type;
      }
    }

    // Enumerate types - ordered by type, then by name
    foreach ($order as $type => $types) {
      if (empty($types)) continue;
      if ($i) $out->writeLine();

      usort($types, function($a, $b) { return $a->name() <=> $b->name(); });
      $i= 0;
      foreach ($types as $type) {
        $out->writeLine('  ', $type->modifiers()->names(true).' '.$type->kind()->name().' '.$type->name());
        $i++;
      }
    }

    $out->writeLine('}');
  }
}