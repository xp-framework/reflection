<?php namespace lang\annotations;

use lang\reflection\Annotation;

/**
 * PHP 8.0 attributes API
 *
 * @see  https://wiki.php.net/rfc/shorter_attribute_syntax
 */
trait FromAttributes {

  /** @return iterable */
  public function annotations() {
    foreach ($this->reflect->getAttributes() as $attribute) {
      yield new Annotation($attribute->getName(), $attribute->getArguments());
    }
  }
}