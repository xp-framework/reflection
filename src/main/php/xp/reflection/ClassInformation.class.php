<?php namespace xp\reflection;

class ClassInformation extends TypeInformation {

  public function display($out) {
    $this->documentation($out, $this->type);
    $out->format(
      '%s class %s%s%s {',
      $this->type->modifiers(),
      $this->type->name(),
      $this->extends($this->type),
      $this->implements($this->type)
    );

    $properties= $this->partition($this->type->properties());
    $methods= $this->partition($this->type->methods());

    $section= 0;
    if ($properties['class']) {
      $section++ && $out->line();
      foreach ($properties['class'] as $property) {
        $this->member($out, $property);
      }
    }

    if ($properties['instance']) {
      $section++ && $out->line();
      foreach ($properties['instance'] as $property) {
        $this->member($out, $property);
      }
    }

    if ($constructor= $this->type->constructor()) {
      $section++ && $out->line();
      $this->member($out, $constructor);
    }

    if ($methods['class']) {
      $section++ && $out->line();
      foreach ($methods['class'] as $method) {
        $this->member($out, $method);
      }
    }

    if ($methods['instance']) {
      $section++ && $out->line();
      foreach ($methods['instance'] as $method) {
        $this->member($out, $method);
      }
    }

    $out->line('}');
  }
}