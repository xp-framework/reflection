<?php namespace xp\reflection;

use lang\{ClassLoader, Reflection};

class PackageInformation extends TypeListing {
  private $package;

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

    $this->list($out, $i, $this->package->types());
    $out->line('}');
  }
}