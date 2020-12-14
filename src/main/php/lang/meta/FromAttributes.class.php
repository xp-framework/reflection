<?php namespace lang\meta;

use lang\reflection\Annotation;

/**
 * PHP 8.0 attributes API
 *
 * @see  https://wiki.php.net/rfc/shorter_attribute_syntax
 */
class FromAttributes {

  private function annotations($reflect, $context) {
    $r= [];
    foreach ($reflect->getAttributes() as $attribute) {
      $args= $attribute->getArguments();
      if ('eval' === key($args)) {
        $r[$attribute->getName()]= [$this->evaluate($context, $args['eval'])];
      } else {
        $r[$attribute->getName()]= $args;
      }
    }
    return $r;
  }

  /** @return iterable */
  public function ofType($reflect) {
    return $this->annotations($reflect, $reflect);
  }

  /** @return iterable */
  public function ofConstant($reflect) {
    return $this->annotations($reflect, $reflect->getDeclaringClass());
  }

  /** @return iterable */
  public function ofProperty($reflect) {
    return $this->annotations($reflect, $reflect->getDeclaringClass());
  }

  /** @return iterable */
  public function ofMethod($reflect) {
    return $this->annotations($reflect, $reflect->getDeclaringClass());
  }

  /** @return iterable */
  public function ofParameter($method, $reflect) {
    return $this->annotations($reflect, $method->getDeclaringClass());
  }

  public function evaluate($reflect, $code) {
    static $break= [T_ATTRIBUTE => 1, T_DOC_COMMENT => 1, T_CLASS => 1, T_INTERFACE => 1, T_TRAIT => 1];

    // Parse namespace and imports from file
    $ns= '';
    $tokens= \PhpToken::tokenize(file_get_contents($reflect->getFileName()));
    foreach ($tokens as $t) {
      if (isset($break[$t->id])) break;
      if (T_OPEN_TAG === $t->id) continue;
      $ns.= $t->text;
    }

    // Create, then bind closure to reflected class
    $f= eval($ns.' return static function() { return '.$code.'; };');
    return $f->bindTo(null, $reflect->name)();
  }
}