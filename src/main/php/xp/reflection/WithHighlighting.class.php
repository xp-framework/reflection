<?php namespace xp\reflection;

use util\Objects;

class WithHighlighting {
  private $stream;
  private $patterns= [];
  private $replacements= [];

  /**
   * Creates a highlighting instance
   *
   * @param  io.streams.OutputStreamWriter $out
   * @param  [:var] $replace
   */
  public function __construct($out, $replace= []) {
    $this->stream= $out->stream();
    foreach ($replace as $pattern => $replacement) {
      $this->patterns[]= $pattern;
      $this->replacements[]= $replacement;
    }
  }

  public function format($format, ... $args) {
    $this->stream->write(preg_replace($this->patterns, $this->replacements, vsprintf($format, $args))."\n");
  }

  public function line(... $args) {
    $line= '';
    foreach ($args as $arg) {
      if (is_string($arg)) {
        $line.= $arg;
      } else {
        $line.= Objects::stringOf($arg);
      }
    }
    $this->stream->write(preg_replace($this->patterns, $this->replacements, $line)."\n");
  }

  public function documentation($text, $indent= '') {
    $code= false;
    foreach (explode("\n", $text) as $line) {
      if (0 === strncmp($line, '```', 3)) $code= !$code;
      if ($code) {
        $this->stream->write("$indent\e[1;32m// \e[0m".preg_replace($this->patterns, $this->replacements, $line)."\n");
      } else {
        $this->stream->write("$indent\e[1;32m// $line\e[0m\n");
      }
    }
  }
}