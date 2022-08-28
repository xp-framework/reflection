<?php namespace xp\reflection;

class TraitInformation extends TypeInformation {

  public function sources() { return [$this->type->classLoader()]; }
 
  public function display($out) {
    $out->format(
      '%s trait %s%s {',
      $this->type->modifiers(),
      $this->type->name(),
      (($parent= $this->type->parent()) ? ' extends '.$parent->name() : '')
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