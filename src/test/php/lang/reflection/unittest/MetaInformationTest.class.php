<?php namespace lang\reflection\unittest;

use lang\meta\MetaInformation;
use unittest\{Assert, Before, After, Test};

class MetaInformationTest {
  private $reflect;

  #[Before]
  public function initialize() {
    $annotations= [
      DETAIL_ANNOTATIONS => ['annotated' => 'test'],
      DETAIL_TARGET_ANNO => ['annotated' => Annotated::class]
    ];
    \xp::$meta['lang.reflection.unittest.Fixture']= [
      'class' => $annotations,
      2 => ['TEST' => $annotations],
      0 => ['DEFAULT' => $annotations],
      1 => [
        '__construct' => [
          DETAIL_ANNOTATIONS => ['annotated' => 'test'],
          DETAIL_TARGET_ANNO => ['annotated' => Annotated::class, '$value' => ['annotated' => 'test']]
        ]
      ]
    ];
    $this->reflect= new \ReflectionClass(Fixture::class);
  }

  #[After]
  public function finalize() {
    unset(\xp::$meta['lang.reflection.unittest.Fixture']);
  }

  #[Test]
  public function type_annotations() {
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->ofType($this->reflect)[DETAIL_ANNOTATIONS]
    );
  }

  #[Test]
  public function constant_annotations() {
    $c= new \ReflectionClassConstant($this->reflect->name, 'TEST');
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->ofConstant($c)[DETAIL_ANNOTATIONS]
    );
  }

  #[Test]
  public function property_annotations() {
    $p= new \ReflectionProperty($this->reflect->name, 'DEFAULT');
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->ofProperty($p)[DETAIL_ANNOTATIONS]
    );
  }

  #[Test]
  public function method_annotations() {
    $m= new \ReflectionMethod($this->reflect->name, '__construct');
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->ofMethod($m)[DETAIL_ANNOTATIONS]
    );
  }

  #[Test]
  public function parameter_annotations() {
    $method= new \ReflectionMethod($this->reflect->name, '__construct');
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->ofParameter($method, $method->getParameters()[0])
    );
  }
}