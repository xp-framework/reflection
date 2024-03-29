<?php namespace lang\reflection\unittest;

use ReflectionClass, ReflectionClassConstant, ReflectionProperty, ReflectionMethod;
use lang\meta\MetaInformation;
use test\{After, Assert, Before, Test, Values};

class MetaInformationTest {
  private $reflect;

  #[Before]
  public function initialize() {
    $annotations= [
      DETAIL_ANNOTATIONS => ['annotated' => 'test'],
      DETAIL_TARGET_ANNO => ['annotated' => Annotated::class],
      DETAIL_COMMENT     => 'Test'
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
          DETAIL_RETURNS     => 'function(): var',
          DETAIL_COMMENT     => 'Test'
        ]
      ]
    ];
    $this->reflect= new ReflectionClass(Fixture::class);
  }

  #[After]
  public function finalize() {
    unset(\xp::$meta['lang.reflection.unittest.Fixture']);
  }

  #[Test]
  public function type_comment() {
    Assert::equals('Test', (new MetaInformation(null))->typeComment($this->reflect));
  }

  #[Test]
  public function type_annotations() {
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->typeAnnotations($this->reflect)
    );
  }

  #[Test]
  public function constant_comment() {
    $c= new ReflectionClassConstant($this->reflect->name, 'TEST');
    Assert::equals('Test', (new MetaInformation(null))->constantComment($c));
  }

  #[Test]
  public function constant_annotations() {
    $c= new ReflectionClassConstant($this->reflect->name, 'TEST');
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->constantAnnotations($c)
    );
  }

  #[Test]
  public function property_comment() {
    $p= new ReflectionProperty($this->reflect->name, 'DEFAULT');
    Assert::equals('Test', (new MetaInformation(null))->propertyComment($p));
  }

  #[Test]
  public function property_annotations() {
    $p= new ReflectionProperty($this->reflect->name, 'DEFAULT');
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->propertyAnnotations($p)
    );
  }

  #[Test]
  public function property_type() {
    $p= new ReflectionProperty($this->reflect->name, 'value');
    Assert::equals(
      'function(): var',
      (new MetaInformation(null))->propertyType($p)
    );
  }

  #[Test]
  public function method_comment() {
    $m= new ReflectionMethod($this->reflect->name, 'value');
    Assert::equals('Test', (new MetaInformation(null))->methodComment($m));
  }


  #[Test]
  public function method_annotations() {
    $m= new ReflectionMethod($this->reflect->name, '__construct');
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->methodAnnotations($m)
    );
  }

  #[Test]
  public function method_return_type() {
    $m= new ReflectionMethod($this->reflect->name, 'value');
    Assert::equals(
      'function(): var',
      (new MetaInformation(null))->methodReturns($m)
    );
  }


  #[Test]
  public function method_parameter_types() {
    $method= new ReflectionMethod($this->reflect->name, '__construct');
    Assert::equals(
      ['var'],
      (new MetaInformation(null))->methodParameterTypes($method, $method->getParameters()[0])
    );
  }

  #[Test]
  public function parameter_annotations() {
    $method= new ReflectionMethod($this->reflect->name, '__construct');
    Assert::equals(
      [Annotated::class => ['test']],
      (new MetaInformation(null))->parameterAnnotations($method, $method->getParameters()[0])
    );
  }

  #[Test, Values([[null, null, []], [null, [true], [null]], [1, null, [1]], [1, [true], [1]], [[1], null, [1]], [[1], true, [[1]]]])]
  public function map_member_value_to_arguments($value, $flag, $arguments) {
    $meta= &\xp::$meta['lang.reflection.unittest.Fixture'][0]['DEFAULT'];
    $meta[DETAIL_ANNOTATIONS]['annotated']= $value;
    $meta[DETAIL_TARGET_ANNO][Annotated::class]= $flag;

    $p= new ReflectionProperty($this->reflect->name, 'DEFAULT');
    Assert::equals(
      [Annotated::class => $arguments],
      (new MetaInformation(null))->propertyAnnotations($p)
    );
  }

  #[Test, Values([[null, null, []], [null, ['value' => true], [null]], [1, null, [1]], [1, ['value' => true], [1]], [[1], null, [1]], [[1], ['value' => true], [[1]]]])]
  public function map_param_value_to_arguments($value, $flag, $arguments) {
    $meta= &\xp::$meta['lang.reflection.unittest.Fixture'][1]['__construct'][DETAIL_TARGET_ANNO];
    $meta['$value']['annotated']= $value;
    $meta[Annotated::class]= $flag;

    $m= new ReflectionMethod($this->reflect->name, '__construct');
    Assert::equals(
      [Annotated::class => $arguments],
      (new MetaInformation(null))->parameterAnnotations($m, $m->getParameters()[0])
    );
  }
}