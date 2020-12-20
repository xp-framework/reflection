<?php namespace lang\reflection;

use lang\{ClassLoader, IClassLoader, Reflection, IllegalArgumentException};

/**
 * Represents a namespace, which may exist in various class loaders
 *
 * @test lang.reflection.unittest.PackageTest
 */
class Package {
  private $name;

  /**
   * Creates a new package from either a name or name components.
   * Optionally, a class loader instance can be passed - if omitted,
   * the default, composite class loader is used.
   *
   * @param  string... $components
   */
  public function __construct(... $components) {
    $this->name= rtrim(strtr(implode('.', $components), '\\', '.'), '.');
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

  /**
   * Returns a given type
   *
   * @param  string $name
   * @return lang.reflection.Type
   * @throws lang.IllegalArgumentException
   */
  public function type($name) {
    $type= strtr($name, '\\', '.');
    $p= strrpos($type, '.');

    if (false === $p) {
      return Reflection::of($this->name.'.'.$type);
    } else if (0 === strncmp($this->name, $type, $p)) {
      return Reflection::of($type);
    } else {
      throw new IllegalArgumentException('Given type '.$type.' is not in package '.$this->name);
    }
  }
}