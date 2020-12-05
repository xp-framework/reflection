<?php namespace xp\reflection;

use lang\{Reflect, Enum, ClassLoader};
use util\cmd\Console;

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
      $information= new PackageInformation($name);
    } else {
      Console::$err->writeLine('Error: '.$name.' is neither a class nor a package');
      return 2;
    }

    foreach ($information->sources() as $source) {
      Console::writeLine("\e[33m@", $source, "\e[0m");
    }

    $information->display(new WithHighlighting(Console::$out, [
      '/(class|enum|trait|interface|package|directory|function) (.+)/'      => "\e[34m\$1\e[0m \$2",
      '/(extends|implements) (.+)/'                                         => "\e[34m\$1\e[0m \$2",
      '/\b(var|int|string|float|array|iterable|object|void|static|self)\b/' => "\e[36m\$1\e[0m",
      '/(public|private|protected|abstract|final|static)/'                  => "\e[1;35m\$1\e[0m",
      '/(\$[a-zA-Z0-9_]+)/'                                                 => "\e[1;31m\$1\e[0m",
    ]));
    return 0;
  }
}