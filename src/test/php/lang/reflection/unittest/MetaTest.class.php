<?php namespace lang\reflection\unittest;

use lang\meta\Cached;
use unittest\{Assert, Before, After, Test};

class MetaTest {
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
      1 => ['__construct' => [
        DETAIL_ANNOTATIONS => ['annotated' => 'test'],
        DETAIL_TARGET_ANNO => ['annotated' => Annotated::class, '$value' => ['annotated' => 'test']]
      ]]
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
      (new Cached(null))->ofType($this->reflect)
    );
  }

  #[Test]
  public function constant_annotations() {
    Assert::equals(
      [Annotated::class => ['test']],
      (new Cached(null))->ofConstant(new \ReflectionClassConstant($this->reflect->name, 'TEST'))
    );
  }

  #[Test]
  public function property_annotations() {
    Assert::equals(
      [Annotated::class => ['test']],
      (new Cached(null))->ofProperty(new \ReflectionProperty($this->reflect->name, 'DEFAULT'))
    );
  }

  #[Test]
  public function method_annotations() {
    Assert::equals(
      [Annotated::class => ['test']],
      (new Cached(null))->ofMethod(new \ReflectionMethod($this->reflect->name, '__construct'))
    );
  }

  #[Test]
  public function parameter_annotations() {
    $method= new \ReflectionMethod($this->reflect->name, '__construct');
    Assert::equals(
      [Annotated::class => ['test']],
      (new Cached(null))->ofParameter($method, $method->getParameters()[0])
    );
  }
}