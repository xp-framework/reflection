<?php namespace lang\reflection;

use lang\{ClassLoader, IClassLoader, Reflection};

/** Represents a namespace, which may exist in various class loaders */
class Package {
  private $name;

  /**
   * Creates a new package from either a name or name components.
   * Optionally, a class loader instance can be passed - if omitted,
   * the default, composite class loader is used.
   *
   * @param  string|string[] $arg
   */
  public function __construct($arg) {
    if (is_array($arg)) {
      $this->name= rtrim(strtr(implode('.', $arg), '\\', '.'), '.');
    } else {
      $this->name= rtrim(strtr((string)$arg, '\\', '.'), '.');
    }
  }

  /** Returns this package's name (in dotted form) */
  public function name(): string { return $this->name; }

  /** Returns type literal (in namespaced form) */
  public function literal(): string { return strtr($this->name, '.', '\\'); }

  /**
   * Returns all class loaders providing this package
   *
   * @return iterable
   */
  public function classLoaders() {
    foreach (ClassLoader::getLoaders() as $loader) {
      if ($loader->providesPackage($this->name)) yield $loader;
    }
  }

  /**
   * Returns this package's parent, if any
   *
   * @return ?self
   */
  public function parent() {
    $p= strrpos($this->name, '.');
    return false === $p ? null : new Package(substr($this->name, 0, $p));
  }

  /**
   * Returns this package's child packages
   *
   * @return iterable
   */
  public function children() {
    $base= $this->name.'.';
    $loader= ClassLoader::getDefault();
    foreach ($loader->packageContents($this->name) as $entry) {
      if ('/' === $entry[strlen($entry) - 1]) {
        yield new self($base.substr($entry, 0, -1));
      }
    }
  }

  /**
   * Returns all types in this package
   *
   * @return iterable
   */
  public function types() {
    $ext= strlen(\xp::CLASS_FILE_EXT);
    $base= $this->name.'.';
    $loader= ClassLoader::getDefault();
    foreach ($loader->packageContents($this->name) as $entry) {
      if (0 === substr_compare($entry, \xp::CLASS_FILE_EXT, -$ext)) {
        yield Reflection::of($loader->loadClass0($base.substr($entry, 0, -$ext)));
      }
    }
  }
}