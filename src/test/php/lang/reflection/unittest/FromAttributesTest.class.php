<?php namespace lang\reflection\unittest;

use ReflectionClass, StdClass as Dynamic;
use lang\meta\FromAttributes;
use test\verify\Runtime;
use test\{Assert, Test, Ignore as Skip};
use util\Comparison as WithComparison;

#[Runtime(php: '>=8.0')]
class FromAttributesTest {

  #[Test]
  public function can_create() {
    new FromAttributes();
  }

  #[Test]
  public function imports() {
    Assert::equals(
      [
        'ReflectionClass' => null,
        'FromAttributes'  => FromAttributes::class,
        'Dynamic'         => Dynamic::class,
        'Runtime'         => Runtime::class,
        'Assert'          => Assert::class,
        'Test'            => Test::class,
        'WithComparison'  => WithComparison::class,
        'Skip'            => Skip::class,
      ],
      (new FromAttributes())->imports(new ReflectionClass(self::class))
    );
  }

  #[Test]
  public function imports_from_eval() {
    $name= eval('class FromAttributesTest_eval { } return FromAttributesTest_eval::class;');
    Assert::equals([], (new FromAttributes())->imports(new ReflectionClass($name)));
  }

  #[Test]
  public function evaluate_constant() {
    Assert::equals(
      ReflectionClass::IS_FINAL,
      (new FromAttributes())->evaluate(new ReflectionClass(self::class), 'ReflectionClass::IS_FINAL')
    );
  }

  #[Test]
  public function evaluate_alias() {
    Assert::equals(
      new Dynamic(),
      (new FromAttributes())->evaluate(new ReflectionClass(self::class), 'new Dynamic()')
    );
  }
}