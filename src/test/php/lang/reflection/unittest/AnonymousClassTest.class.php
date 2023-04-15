<?php namespace lang\reflection\unittest;

use lang\Reflection;
use test\{Assert, Test};

class AnonymousClassTest {

  #[Test]
  public function name() {
    Assert::equals('class@anonymous', strstr(Reflection::type(new class() { })->name(), "\0", true));
  }

  /** @see https://github.com/xp-framework/reflection/issues/33 */
  #[Test]
  public function annotation() {
    $type= Reflection::type(new class() {

      #[Test]
      public function fixture() { }
    });

    Assert::equals(Test::class, $type->method('fixture')->annotation(Test::class)->type());
  }
}