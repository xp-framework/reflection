<?php namespace xp\reflection;

use lang\{ClassLoader, FileSystemClassLoader, Reflection, IllegalArgumentException};

class DirectoryInformation {
  const PRIMARY = 0;

  private $base, $flags;
  private $loaders= [self::PRIMARY => null];

  /**
   * Creates a new directory information instance
   *
   * @param  string $dir
   * @param  int $flags
   * @throws lang.IllegalArgumentException if directory is not in class path
   */
  public function __construct($dir, $flags) {
    $target= rtrim(realpath($dir), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    $this->flags= $flags;

    // Locate directory in class path
    foreach (ClassLoader::getLoaders() as $loader) {
      if (!($loader instanceof FileSystemClassLoader)) {
        $this->loaders[]= $loader;
      } else if (!$this->loaders[self::PRIMARY] && 0 === strncasecmp($target, $loader->path, $l= strlen($loader->path))) {
        $this->loaders[self::PRIMARY]= $loader;
        $this->base= $l === strlen($target) ? null : strtr(substr($target, $l, -1), [DIRECTORY_SEPARATOR => '.']);
      }
    }

    if (!$this->loaders[self::PRIMARY]) {
      throw new IllegalArgumentException('Directory '.$dir.' is not in class path');
    }
  }

  /** @return iterable */
  public function sources() {
    yield $this->loaders[self::PRIMARY];
  }

  /**
   * Returns types in a given base package. Asks the primary class loader,
   * then all the others.
   * 
   * @param  string $base
   * @return iterable
   */
  private function typesIn($base) {
    $ext= strlen(\xp::CLASS_FILE_EXT);
    foreach ($this->loaders as $loader) {
      foreach ($loader->packageContents($base) as $entry) {
        if (0 === substr_compare($entry, \xp::CLASS_FILE_EXT, -$ext)) {
          yield Reflection::of($loader->loadClass0($base.substr($entry, 0, -$ext)));
        }
      }
    }
  }

  public function display($out) {
    if (null === $this->base) {
      $out->format('package {');
      $base= '';
    } else {
      $out->format('package %s {', $this->base);
      $base= $this->base.'.';
    }

    $i= 0;
    foreach ($this->loaders[self::PRIMARY]->packageContents($this->base) as $entry) {
      if ('/' === $entry[strlen($entry) - 1]) {
        $out->line('  package '.$base.substr($entry, 0, -1));
        $i++;
      }
    }

    // Compile types into a custom order
    $order= ['interface' => [], 'trait' => [], 'enum' => [], 'class' => []];
    foreach ($this->typesIn($base) as $type) {
      $order[$type->kind()->name()][$type->name()]= $type;
    }

    foreach ($order as $type => $types) {
      if (empty($types)) continue;
      $separator= $out->separator($i);

      ksort($types);
      $i= 0;
      foreach ($types as $type) {
        if ($this->flags & Information::DOC) {
          $out->separator(!$separator) || $separator= false;

          $comment= $type->comment() ?? '(Undocumented)';
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