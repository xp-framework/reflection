<?php namespace lang\reflection;

/** Indicates invoking a method failed because of preconditions */
class CannotInvoke extends TargetException {
  const MESSAGE = 'Cannot invoke';
}