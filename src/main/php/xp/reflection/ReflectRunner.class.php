<?php namespace xp\reflection;

use lang\{Reflect, Enum, ClassLoader};
use util\cmd\Console;

/**
 * Displays reflection information about types and packages
 * ========================================================================
 *
 * - Displays information about the `Value` interface
 *   ```sh
 *   $ xp reflect lang.Value
 *   ```
 * - Displays information about the `Person` class
 *   ```sh
 *   $ xp reflect org.example.Person
 *   ```
 * - Displays information about the `Kind` enum
 *   ```sh
 *   $ xp reflect src/main/php/org/example/Kind.class.php
 *   ```
 * - Displays information about the `org.example` namespace
 *   ```sh
 *   $ xp reflect org.example
 *   ```
 *
 * Pass the `-a` flag to include private and protected members.
 */
class ReflectRunner {

  /** 
   * Display information about files, directories, packages or types
   *
   * @param  string[] $args
   * @return int
   */
  public static function main($args) {
    $name= array_shift($args);
    if (null === $name || '' === $name) {
      Console::$err->writeLine('Error: no class or package name given');
      return 1;
    }

    $cl= ClassLoader::getDefault();
    if (strstr($name, \xp::CLASS_FILE_EXT)) {
      $information= Information::forClass($cl->loadUri(realpath($name)));
    } else if ($cl->providesClass($name)) {
      $information= Information::forClass($cl->loadClass($name));
    } else if ($cl->providesPackage($name)) {
      $information= Information::forPackage($name);
    } else {
      Console::$err->writeLine('Error: '.$name.' is neither a class nor a package');
      return 2;
    }

    $flags= 0;
    if (in_array('-a', $args) || in_array('--all', $args)) $flags |= Information::ALL;

    foreach ($information->sources() as $source) {
      Console::writeLine("\e[33m@", $source, "\e[0m");
    }

    $information->display($flags, new WithHighlighting(Console::$out, [
      '/(class|enum|trait|interface|package|directory|function) (.+)/'      => "\e[34m\$1\e[0m \$2",
      '/(extends|implements) ([^ ]+)/'                                      => "\e[34m\$1\e[0m \$2",
      '/\b(var|int|string|float|array|iterable|object|void|static|self)\b/' => "\e[36m\$1\e[0m",
      '/(public|private|protected|abstract|final|static)/'                  => "\e[1;35m\$1\e[0m",
      '/(\$[a-zA-Z0-9_]+)/'                                                 => "\e[1;31m\$1\e[0m",
    ]));
    return 0;
  }
}