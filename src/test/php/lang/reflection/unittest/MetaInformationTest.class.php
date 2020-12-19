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
      2 => [
        'TEST' => $annotations
      ],
      0 => [
        'DEFAULT' => $annotations + [DETAIL_RETURNS => 'self'],
        'value'   => [DETAIL_ANNOTATIONS => [], DETAIL_RETURNS => 'function(): var']
      ],
      1 => [
        '__construct' => [
          DETAIL_ANNOTATIONS => ['annotated' => 'test'],
          DETAIL_TARGET_ANNO => ['annotated' => Annotated::class, '$value' => ['annotated' => 'test']],
          DETAIL_ARGUMENTS   => ['var'],
          DETAIL_RETURNS     => null
        ],
        'value' => [
          DETAIL_ANNOTATIONS => [],
          DETAIL_ARGUMENTS   => [],
          DETAIL_RETURNS     => 'function(): var'
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
  public function property_type() {
    $p= new \ReflectionProperty($this->reflect->name, 'value');
    Assert::equals(
      'function(): var',
      (new MetaInformation(null))->ofProperty($p)[DETAIL_RETURNS]
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
  public function method_return_type() {
    $m= new \ReflectionMethod($this->reflect->name, 'value');
    Assert::equals(
      'function(): var',
      (new MetaInformation(null))->ofMethod($m)[DETAIL_RETURNS]
    );
  }

  #[Test]
  public function parameter_annotations() {
    $method= new \ReflectionMethod($this->reflect->name, '__construct');
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->ofParameter($method, $method->getParameters()[0])[DETAIL_ANNOTATIONS]
    );
  }

  #[Test]
  public function parameter_type() {
    $method= new \ReflectionMethod($this->reflect->name, '__construct');
    Assert::equals(
      'var',
      (new MetaInformation(null))->ofParameter($method, $method->getParameters()[0])[DETAIL_RETURNS]
    );
  }
}