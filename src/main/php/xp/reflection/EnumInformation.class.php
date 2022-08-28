<?php namespace xp\reflection;

class EnumInformation extends TypeInformation {
 
  public function display($out) {
    $this->documentation($out, $this->type);
    $out->format(
      '%s enum %s%s%s {',
      $this->type->modifiers(),
      $this->type->name(),
      $this->extends($this->type),
      $this->implements($this->type)
    );

    $constants= iterator_to_array($this->type->constants());
    $properties= $this->partition($this->type->properties());
    $methods= $this->partition($this->type->methods());

    // List enum properties
    $props= '';
    foreach ($properties['class'] as $property) {
      if ($property->modifiers()->isStatic()) $props.= ', $'.$property->name();
    }
    $out->line('  ', substr($props, 2), ';');

    if ($constants) {
      $out->line();
      foreach ($constants as $constant) {
        $this->member($out, $constant);
      }
    }

    // List instance properties, if any
    if ($properties['instance']) {
      $out->line();
      foreach ($properties['instance'] as $property) {
        $this->member($out, $property);
      }
    }

    // List static methods, if any
    if ($methods['class']) {
      $out->line();
      foreach ($methods['class'] as $method) {
        $this->member($out, $method);
      }
    }

    // List instance methods, if any
    if ($methods['instance']) {
      $out->line();
      foreach ($methods['instance'] as $method) {
        $this->member($out, $method);
      }
    }

    $out->line('}');
  }
}