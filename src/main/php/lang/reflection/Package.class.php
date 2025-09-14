<?php namespace lang\reflection;

use lang\{ClassLoader, IClassLoader, Reflection, IllegalArgumentException, Value};

/**
 * Represents a namespace, which may exist in various class loaders
 *
 * @test lang.reflection.unittest.PackageTest
 */
class Package implements Value {
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

  /** Returns whether this is the global package */
  public function global(): bool { return '' === $this->name; }

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
   * Returns this package's parent. Returns NULL if this package refers to the
   * global package.
   *
   * @return ?self
   */
  public function parent() {
    if ('' === $this->name) return null;

    $p= strrpos($this->name, '.');
    return false === $p ? new Package() : new Package(substr($this->name, 0, $p));
  }

  /**
   * Returns a child package with a given name. Returns NULL if the child package
   * does not exist.
   *
   * @return ?self
   */
  public function child(string $name) {
    $child= ($this->name ? $this->name.'.' : '').strtr($name, '\\', '.');
    if (ClassLoader::getDefault()->providesPackage($child)) {
      return new self($child);
    } else {
      return null;
    }
  }

  /**
   * Returns this package's child packages
   *
   * @return iterable
   */
  public function children() {
    $base= $this->name ? $this->name.'.' : '';
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
    $base= $this->name ? $this->name.'.' : '';
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
    if ('' === $this->name) return Reflection::type($name);

    // Compare type package and this package
    $type= strtr($name, '\\', '.');
    $p= strrpos($type, '.');
    if (false === $p) {
      return Reflection::type($this->name.'.'.$type);
    } else if (0 === strncmp($this->name, $type, $p)) {
      return Reflection::type($type);
    }

    throw new IllegalArgumentException('Given type '.$type.' is not in package '.$this->name);
  }

  /** @return string */
  public function hashCode() { return md5($this->name); }

  /** @return string */
  public function toString() { return nameof($this).'<'.$this->name().'>'; }

  /**
   * Compares this member to another value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? $this->name <=> $value->name
      : 1
    ;
  }
}