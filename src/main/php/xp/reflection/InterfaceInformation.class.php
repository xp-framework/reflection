<?php namespace xp\reflection;

class InterfaceInformation extends TypeInformation {

  public function display($out) {
    $this->documentation($out, $this->type);
    $out->format(
      '%s interface %s%s {',
      $this->type->modifiers(),
      $this->type->name(),
      $this->parents($this->type)
    );

    $constants= iterator_to_array($this->type->constants());
    $properties= $this->partition($this->type->properties());
    $methods= $this->partition($this->type->methods());

    $section= 0;
    if ($constants) {
      $section++;
      foreach ($constants as $constant) {
        $this->member($out, $constant);
      }
    }

    if ($properties['class']) {
      $section++ && $out->line();
      foreach ($properties['class'] as $property) {
        $$this->member($out, $property);
      }
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