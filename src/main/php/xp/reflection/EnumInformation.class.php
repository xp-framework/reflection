<?php namespace xp\reflection;

class EnumInformation extends TypeInformation {

  public function sources() { return [$this->type->classLoader()]; }
 
  public function display($out) {
    $out->writeLinef(
      '%s enum %s%s {',
      $this->type->modifiers(),
      $this->type->name(),
      (($parent= $this->type->parent()) ? ' extends '.$parent->name() : '')
    );
    $properties= $this->partition($this->type->properties());
    $methods= $this->partition($this->type->methods());

    // List enum properties
    $props= '';
    foreach ($properties['class'] as $property) {
      if ($property->modifiers()->isStatic()) $props.= ', $'.$property->name();
    }
    $out->writeLine('  ', substr($props, 2), ';');

    // List static methods, if any
    if ($methods['class']) {
      $out->writeLine();
      foreach ($methods['class'] as $method) {
        $out->writeLine('  ', $method->toString());
      }
    }

    // List instance methods, if any
    if ($methods['instance']) {
      $out->writeLine();
      foreach ($methods['instance'] as $method) {
        $out->writeLine('  ', $method->toString());
      }
    }

    $out->writeLine('}');
  }
}