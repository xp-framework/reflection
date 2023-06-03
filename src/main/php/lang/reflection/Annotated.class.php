<?php namespace lang\reflection;

interface Annotated {

  /** @return lang.reflection.Annotations */
  public function annotations();

  /** @return ?lang.reflection.Annotation */
  public function annotation(string $type);

}