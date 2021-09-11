<?php namespace lang\reflection\unittest;

use lang\Reflection;
use lang\reflection\{Modifiers, AccessingFailed};
use unittest\{Assert, Values, Expect, Test};

class VirtualPropertiesTest {
  use TypeDefinition;

  /** @return iterable */
  private function fixtures() {
    yield [Reflection::of(VirtualProperty::class)];

    $t= $this->declare('{ use WithReadonly; }');
    \xp::$meta[$t->name()][0]['fixture']= [
      DETAIL_RETURNS   => 'string',
      DETAIL_ARGUMENTS => [Modifiers::IS_PUBLIC | Modifiers::IS_READONLY]
    ];

    yield $t;
    if (PHP_VERSION_ID >= 80100) {
      yield $this->declare('{ public readonly string $fixture; }');
    }
  }

  #[Test, Values('fixtures')]
  public function readonly_modifier_shown_in_string_representation($type) {
    Assert::equals('public readonly string $fixture', $type->property('fixture')->toString());
  }

  #[Test, Values('fixtures')]
  public function virtual_property_included_in_list($type) {
    Assert::equals(
      ['fixture' => 'public readonly'],
      array_map(function($p) { return $p->modifiers()->names(); }, iterator_to_array($type->properties()))
    );
  }

  #[Test, Values('fixtures')]
  public function named_virtual($type) {
    Assert::equals($type->property('fixture'), $type->properties()->named('fixture'));
  }

  #[Test, Values('fixtures')]
  public function initializing_readonly_allowed($type) {
    $property= $type->property('fixture');
    $instance= $type->newInstance();

    $property->set($instance, 'Test');
  }

  #[Test, Values('fixtures')]
  public function reading_readonly($type) {
    $property= $type->property('fixture');
    $instance= $type->newInstance();

    $property->set($instance, 'Test');
    Assert::equals('Test', $property->get($instance));
  }

  #[Test, Expect(AccessingFailed::class), Values('fixtures')]
  public function overwriting_readonly_not_allowed($type) {
    $property= $type->property('fixture');
    $instance= $type->newInstance();

    $property->set($instance, 'Test');
    $property->set($instance, 'Modified');
  }
}