<?php namespace xp\reflection;

use io\streams\Writer;

class WithHighlighting extends Writer {
  private $patterns= [];
  private $replacements= [];

  /**
   * Creates a highlighting instance
   *
   * @param  io.streams.OutputStreamWriter $out
   * @param  [:var] $replace
   */
  public function __construct($out, $replace= []) {
    parent::__construct($out->stream());
    foreach ($replace as $pattern => $replacement) {
      $this->patterns[]= $pattern;
      $this->replacements[]= $replacement;
    }
  }

  /**
   * Writes text
   *
   * @param  string $text
   * @return int
   */
  protected function write0($text) {
    $this->stream->write(preg_replace($this->patterns, $this->replacements, $text));
  }
}