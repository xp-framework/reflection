<?php namespace lang\reflection;

/** Indicates invoking a method failed because it raised an exception */
class InvocationFailed extends TargetException {
  const MESSAGE = 'Failed invoking';
}