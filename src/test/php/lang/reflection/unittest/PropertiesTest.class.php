<?php namespace lang\reflection\unittest;

use lang\reflection\Modifiers;
use unittest\{Assert, Test};

class PropertiesTest {
  use TypeDefinition;

  #[Test]
  public function name() {
    Assert::equals('fixture', $this->type('{ public $fixture; }')->property('fixture')->name());
  }

  #[Test]
  public function modifiers() {
    Assert::equals(
      new Modifiers('private'),
      $this->type('{ private $fixture; }')->property('fixture')->modifiers()
    );
  }

  #[Test]
  public function named() {
    $type= $this->type('{ public $fixture; }');
    Assert::equals($type->property('fixture'), $type->properties()->named('fixture'));
  }

  #[Test]
  public function get_instance() {
    $type= $this->type('{ public $fixture = "Test"; }');
    Assert::equals('Test', $type->properties()->named('fixture')->get($type->newInstance()));
  }

  #[Test]
  public function get_static() {
    $type= $this->type('{ public static $fixture = "Test"; }');
    Assert::equals('Test', $type->properties()->named('fixture')->get(null));
  }

  #[Test]
  public function set_instance() {
    $type= $this->type('{ public $fixture = "Test"; }');
    $instance= $type->newInstance();
    $type->properties()->named('fixture')->set($instance, 'Modified');

    Assert::equals('Modified', $instance->fixture);
  }

  #[Test]
  public function set_static() {
    $type= $this->type('{ public static $fixture = "Test"; }');
    $class= $type->literal();
    $type->properties()->named('fixture')->set(null, 'Modified');

    Assert::equals('Modified', $class::$fixture);
  }

  #[Test]
  public function non_existant() {
    $type= $this->type('{ }');
    Assert::null($type->properties()->named('fixture'));
  }

  #[Test]
  public function without_properties() {
    Assert::equals([], iterator_to_array($this->type('{ }')->properties()));
  }

  #[Test]
  public function properties() {
    $type= $this->type('{ public $one, $two; }');
    Assert::equals(
      ['one' => $type->property('one'), 'two' => $type->property('two')],
      iterator_to_array($type->properties())
    );
  }
}