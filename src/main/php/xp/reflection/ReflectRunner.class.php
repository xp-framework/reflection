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
    $cl= ClassLoader::getDefault();

    $flags= 0;
    $name= null;
    foreach ($args as $arg) {
      if ('-a' === $arg || '--all' === $arg) {
        $flags |= Information::ALL;
      } else if ('-d' === $arg || '--doc' === $arg) {
        $flags |= Information::DOC;
      } else if ('' === $arg || '-' === $arg[0]) {
        Console::$err->writeLine('Error: unknown argument "'.$arg.'"');
        return 1;
      } else {
        $name= $arg;
      }
    }

    if (null === $name) {
      Console::$err->writeLine('Error: no class or package name given');
      return 1;
    } else if (strstr($name, \xp::CLASS_FILE_EXT)) {
      $information= Information::forClass($cl->loadUri(realpath($name)), $flags);
    } else if ($cl->providesClass($name)) {
      $information= Information::forClass($cl->loadClass($name), $flags);
    } else if ($cl->providesPackage($name)) {
      $information= Information::forPackage($name, $flags);
    } else {
      Console::$err->writeLine('Error: "'.$name.'" neither refers to an existing class or package');
      return 2;
    }

    foreach ($information->sources() as $source) {
      Console::writeLine("\e[33m@", $source, "\e[0m");
    }
    $information->display(new WithHighlighting(Console::$out, [
      '/(class|enum|trait|interface|package|directory|function) (.+)/'      => "\e[34m\$1\e[0m \$2",
      '/(extends|implements) ([^ ]+)/'                                      => "\e[34m\$1\e[0m \$2",
      '/\b(var|int|string|float|array|iterable|object|void|static|self)\b/' => "\e[36m\$1\e[0m",
      '/(public|private|protected|abstract|final|static) /'                 => "\e[1;35m\$1 \e[0m",
      '/(\$[a-zA-Z0-9_]+)/'                                                 => "\e[1;31m\$1\e[0m",
      '/\'([^\']+)\'/'                                                      => "\e[32m'\$1'\e[0m"
    ]));
    return 0;
  }
}