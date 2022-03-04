<?php namespace xp\reflection;

use lang\{ClassLoader, FileSystemClassLoader, Reflection, IllegalArgumentException};

class DirectoryInformation {
  private $loader, $base, $flags;

  /**
   * Creates a new directory information instance
   *
   * @param  string $dir
   * @param  int $flags
   */
  public function __construct($dir, $flags) {
    $target= rtrim(realpath($dir), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    $this->flags= $flags;

    // Locate directory in class path
    foreach (ClassLoader::getLoaders() as $loader) {
      if (!($loader instanceof FileSystemClassLoader)) continue;

      $l= strlen($loader->path);
      if (0 === strncasecmp($target, $loader->path, $l)) {
        $this->loader= $loader;

        if ($l === strlen($target)) {
          $this->base= null;
        } else {
          $this->base= strtr(substr($target, $l, -1), [DIRECTORY_SEPARATOR => '.']);
        }
        return;
      }
    }

    throw new IllegalArgumentException('Directory '.$dir.' is not in class path');
  }

  /** @return iterable */
  public function sources() {
    yield $this->loader;
  }

  /** @return iterable */
  private function types() {
    $base= null === $this->base ? '' : $this->base.'.';
    $ext= strlen(\xp::CLASS_FILE_EXT);
    foreach ($this->loader->packageContents($this->base) as $entry) {
      if (0 === substr_compare($entry, \xp::CLASS_FILE_EXT, -$ext)) {
        yield Reflection::of($this->loader->loadClass0($base.substr($entry, 0, -$ext)));
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
    foreach ($this->loader->packageContents($this->base) as $entry) {
      if ('/' === $entry[strlen($entry) - 1]) {
        $out->line('  package '.$base.substr($entry, 0, -1));
        $i++;
      }
    }

    // Compile types into a custom order
    $order= ['interface' => [], 'trait' => [], 'enum' => [], 'class' => []];
    foreach ($this->types() as $type) {
      $order[$type->kind()->name()][$type->name()]= $type;
    }
    foreach ($order as $type => $types) {
      if (empty($types)) continue;
      if ($i) $out->line();

      ksort($types);
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