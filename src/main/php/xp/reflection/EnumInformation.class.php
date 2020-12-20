<?php namespace xp\reflection;

class EnumInformation extends TypeInformation {
 
  public function display($flags, $out) {
    $out->writeLinef(
      '%s enum %s%s%s {',
      $this->type->modifiers(),
      $this->type->name(),
      $this->extends($this->type),
      $this->implements($this->type)
    );

    $properties= $this->partition($this->type->properties(), $flags & Information::ALL);
    $methods= $this->partition($this->type->methods(), $flags & Information::ALL);

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