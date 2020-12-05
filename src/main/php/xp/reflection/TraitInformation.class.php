<?php namespace xp\reflection;

class TraitInformation extends TypeInformation {

  public function sources() { return [$this->type->classLoader()]; }
 
  public function display($out) {
    $out->writeLinef(
      '%s trait %s%s {',
      $this->type->modifiers(),
      $this->type->name(),
      (($parent= $this->type->parent()) ? ' extends '.$parent->name() : '')
    );
    $properties= $this->partition($this->type->properties());
    $methods= $this->partition($this->type->methods());

    if ($properties['class']) {
      foreach ($properties['class'] as $property) {
        $out->writeLine('  ', $property->toString());
      }
      $out->writeLine();
    }

    if ($properties['instance']) {
      foreach ($properties['instance'] as $property) {
        $out->writeLine('  ', $property->toString());
      }
      $out->writeLine();
    }

    if ($constructor= $this->type->constructor()) {
      $out->writeLine('  ', $constructor->toString());
      $out->writeLine();
    }

    if ($methods['class']) {
      foreach ($methods['class'] as $method) {
        $out->writeLine('  ', $method->toString());
      }
      $out->writeLine();
    }

    if ($methods['instance']) {
      foreach ($methods['instance'] as $method) {
        $out->writeLine('  ', $method->toString());
      }
      $out->writeLine();
    }

    $out->writeLine('}');
  }
}