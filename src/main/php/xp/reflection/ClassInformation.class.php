<?php namespace xp\reflection;

class ClassInformation extends TypeInformation {

  public function display($flags, $out) {
    $out->writeLinef(
      '%s class %s%s%s {',
      $this->type->modifiers(),
      $this->type->name(),
      $this->extends($this->type),
      $this->implements($this->type)
    );

    $properties= $this->partition($this->type->properties(), $flags & Information::ALL);
    $methods= $this->partition($this->type->methods(), $flags & Information::ALL);

    $section= 0;
    if ($properties['class']) {
      $section++ && $out->writeLine();
      foreach ($properties['class'] as $property) {
        $out->writeLine('  ', $property->toString());
      }
    }

    if ($properties['instance']) {
      $section++ && $out->writeLine();
      foreach ($properties['instance'] as $property) {
        $out->writeLine('  ', $property->toString());
      }
    }

    if ($constructor= $this->type->constructor()) {
      $section++ && $out->writeLine();
      $out->writeLine('  ', $constructor->toString());
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