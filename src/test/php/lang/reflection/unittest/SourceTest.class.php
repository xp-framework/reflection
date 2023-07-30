<?php namespace lang\reflection\unittest;

use io\File;
use io\streams\LinesIn;
use lang\{ClassLoader, Reflection};
use test\{Assert, Before, Test};

class SourceTest {
  private $start, $end;

  #[Before]
  public function lines() {
    $i= 0;
    foreach (new LinesIn(new File(__FILE__)) as $line) {
      $i++;
      if (0 === strncmp($line, 'class ', 5)) $this->start= $i;
    }
    $this->end= $i;
  }

  #[Test]
  public function this_type_source() {
    $source= Reflection::type($this)->source();

    Assert::equals(__FILE__, $source->fileName());
    Assert::equals($this->start, $source->startLine());
    Assert::equals($this->end, $source->endLine());
  }

  #[Test]
  public function anonymous_type_source() {
    $source= Reflection::type(new class() { })->source();
    $line= __LINE__ - 1;

    Assert::equals(__FILE__, $source->fileName());
    Assert::equals($line, $source->startLine());
    Assert::equals($line, $source->endLine());
  }

  #[Test]
  public function defined_type_source() {
    $type= ClassLoader::defineType(
      'lang.reflection.unittest.SourceTest_Defined',
      ['kind' => 'class', 'extends' => [], 'implements' => [], 'use' => []],
      []
    );
    $source= Reflection::type($type)->source();

    Assert::equals('dyn://lang.reflection.unittest.SourceTest_Defined', $source->fileName());
    Assert::equals(1, $source->startLine());
    Assert::equals(1, $source->endLine());
  }

  #[Test]
  public function method_source() {
    $source= Reflection::type($this)->method(__FUNCTION__)->source();
    $start= __LINE__ - 2;
    $end= __LINE__ + 5;

    Assert::equals(__FILE__, $source->fileName());
    Assert::equals($start, $source->startLine());
    Assert::equals($end, $source->endLine());
  }

  #[Test]
  public function trait_method_source() {
    $type= ClassLoader::defineType(
      'lang.reflection.unittest.SourceTest_Trait',
      ['kind' => 'class', 'extends' => [], 'implements' => [], 'use' => [WithMethod::class]],
      []
    );
    $source= Reflection::type($type)->method('fixture')->source();

    Assert::equals('WithMethod.class.php', basename($source->fileName()));
    Assert::equals(5, $source->startLine());
    Assert::equals(7, $source->endLine());
  }
}