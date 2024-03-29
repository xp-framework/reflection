<?php namespace lang\reflection;

/** Interface implemented by both constructors and initializers */
interface Instantiation {

  /**
   * Creates a new instance of the type this constructor belongs to
   *
   * @param  var[] $args
   * @return object
   * @throws lang.reflection.InvocationFailed
   * @throws lang.reflection.CannotInstantiate
   */
  public function newInstance(array $args= []);
}