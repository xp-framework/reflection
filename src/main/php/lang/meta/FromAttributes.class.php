<?php namespace lang\meta;

use PhpToken;
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
      $ptr= &$r[$attribute->getName()];

      if (!isset($args['eval'])) {
        $ptr= $args;
      } else if (is_array($args['eval'])) {
        $ptr= [];
        foreach ($args['eval'] as $key => $value) {
          $ptr[$key]= $this->evaluate($context, $value);
        }
      } else {
        $ptr= [$this->evaluate($context, $args['eval'])];
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

  /**
   * Returns imports used in the class file the given class was declared in
   *
   * @param  ReflectionClass $reflect
   * @return [:string]
   */
  public function imports($reflect) {
    static $break= [T_CLASS => true, T_INTERFACE => true, T_TRAIT => true, 372 /* T_ENUM */ => true];
    static $types= [T_WHITESPACE => true, 44 => true, 59 => true, 123 => true];

    // Exclude classes declared inside eval(), their declaration is not accessible
    $file= $reflect->getFileName();
    if (false !== strpos($file, ': eval')) return [];

    $tokens= PhpToken::tokenize(file_get_contents($file));
    $imports= [];
    for ($i= 0, $s= sizeof($tokens); $i < $s; $i++) {
      if (isset($break[$tokens[$i]->id])) break;
      if (T_USE !== $tokens[$i]->id) continue;

      do {
        $type= '';
        for ($i+= 2; $i < $s, !isset($types[$tokens[$i]->id]); $i++) {
          $type.= $tokens[$i]->text;
        }

        // Skip over whitespace
        if (T_WHITESPACE === $tokens[$i]->id) $i++;

        // use `lang\{Type, Primitive as P}` vs. `use lang\Primitive as P;` vs. `use lang\Primitive`
        if (123 === $tokens[$i]->id) {
          $alias= null;
          $group= '';
          for ($i+= 1; $i < $s; $i++) {
            if (44 === $tokens[$i]->id) {
              $imports[$alias ?? $group]= $type.$group;
              $alias= null;
              $group= '';
            } else if (125 === $tokens[$i]->id) {
              $imports[$alias ?? $group]= $type.$group;
              break;
            } else if (T_AS === $tokens[$i]->id) {
              $i+= 2;
              $alias= $tokens[$i]->text;
            } else if (T_WHITESPACE !== $tokens[$i]->id) {
              $group.= $tokens[$i]->text;
            }
          }
        } else if (T_AS === $tokens[$i]->id) {
          $i+= 2;
          $imports[$tokens[$i]->text]= $type;
        } else if (false === ($p= strrpos($type, '\\'))) {
          $imports[$type]= null;
        } else {
          $imports[substr($type, strrpos($type, '\\') + 1)]= $type;
        }

        // Skip over whitespace
        if (T_WHITESPACE === $tokens[$i]->id) $i++;
      } while (44 === $tokens[$i]->id);
    }
    return $imports;
  }

  public function evaluate($reflect, $code) {
    $header= '';
    if ($namespace= $reflect->getNamespaceName()) {
      $header.= 'namespace '.$namespace.';';
    }
    foreach ($this->imports($reflect) as $import => $type) {
      $header.= $type ? "use {$type} as {$import};" : "use {$import};";
    }

    $f= eval($header.' return static function() { return '.$code.'; };');
    return $f->bindTo(null, $reflect->name)();
  }
}