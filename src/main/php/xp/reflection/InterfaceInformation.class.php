<?php namespace xp\reflection;

class InterfaceInformation extends TypeInformation {

  public function display($out) {
    $out->writeLinef(
      '%s interface %s%s {',
      $this->type->modifiers(),
      $this->type->name(),
      $this->parents($this->type)
    );

    $properties= $this->partition($this->type->properties());
    $methods= $this->partition($this->type->methods());

    $section= 0;
    if ($properties['class']) {
      $section++ && $out->writeLine();
      foreach ($properties['class'] as $property) {
        $out->writeLine('  ', $property->toString());
      }
    }

    if ($methods['class']) {
      $section++ && $out->writeLine();
      foreach ($methods['class'] as $method) {
        $out->writeLine('  ', $method->toString());
      }
    }

    if ($methods['instance']) {
      $section++ && $out->writeLine();
      foreach ($methods['instance'] as $method) {
        $out->writeLine('  ', $method->toString());
      }
    }

    $out->writeLine('}');
  }
}