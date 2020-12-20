<?php namespace lang\reflection;

/** Indicates accessing a property failed because of preconditions */
class CannotAccess extends TargetException {
  const MESSAGE = 'Cannot invoke';
}