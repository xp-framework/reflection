<?php namespace lang\reflection\unittest;

use lang\{Type, ArrayType, MapType, FunctionType, TypeUnion, Primitive, IllegalStateException, IllegalArgumentException};
use unittest\actions\RuntimeVersion;
use unittest\{Assert, Expect, Test, Action, Values};

class MethodsTest {
  use TypeDefinition;

  /** @return iterable */
  private function returnTypes() {
    yield ['%s()', false, Type::$VAR];
    yield ['%s(): array', true, Type::$ARRAY];
    yield ['%s(): callable', true, Type::$CALLABLE];
    yield ['%s(): string', true, Primitive::$STRING];
    yield ['/** @return string */ %s()', false, Primitive::$STRING];
    yield ['/** @return function(): int */ %s()', false, new FunctionType([], Primitive::$INT)];
    yield ['/** @return int|string */ %s()', false, new TypeUnion([Primitive::$STRING, Primitive::$INT])];
    yield ['/** @return array<string> */ %s()', false, new ArrayType(Primitive::$STRING)];
  }

  #[Test]
  public function name() {
    Assert::equals('fixture', $this->declare('{ public function fixture() { } }')->method('fixture')->name());
  }

  #[Test]
  public function compoundName() {
    $t= $this->declare('{ public function fixture() { } }');
    Assert::equals($t->name().'::fixture()', $t->method('fixture')->compoundName());
  }

  #[Test]
  public function declaredIn() {
    $t= $this->declare('{ public function fixture() { } }');
    Assert::equals($t, $t->method('fixture')->declaredIn());
  }

  #[Test]
  public function named() {
    $type= $this->declare('{ public function fixture() { } }');
    Assert::equals($type->method('fixture'), $type->methods()->named('fixture'));
  }

  #[Test]
  public function annotated() {
    $type= $this->declare('{
      #[Annotated]
      public function a() { }

      #[Annotated]
      public function b() { }

      public function c() { }
    }');

    Assert::equals(
      ['a' => $type->method('a'), 'b' => $type->method('b')],
      iterator_to_array($type->methods()->annotated(Annotated::class))
    );
  }

  #[Test]
  public function non_existant() {
    $type= $this->declare('{ }');
    Assert::null($type->methods()->named('fixture'));
  }

  #[Test]
  public function without_methods() {
    Assert::equals([], iterator_to_array($this->declare('{ }')->methods()));
  }

  #[Test]
  public function methods() {
    $type= $this->declare('{ public function one() { } public function two() { } }');
    Assert::equals(
      ['one' => $type->method('one'), 'two' => $type->method('two')],
      iterator_to_array($type->methods())
    );
  }

  #[Test, Values('returnTypes')]
  public function returns($decl, $present, $expected) {
    $returns= $this->declare('{ '.sprintf($decl, 'function fixture').' { } }')->method('fixture')->returns();
    Assert::equals([$present, $expected], [$returns->present(), $returns->type()]);
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=7.1")')]
  public function returns_void() {
    $returns= $this->declare('{ function fixture(): void { } }')->method('fixture')->returns();
    Assert::equals(Type::$VOID, $returns->type());
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0")')]
  public function returns_type_union() {
    $returns= $this->declare('{ function fixture(): string|int { } }')->method('fixture')->returns();
    Assert::equals(new TypeUnion([Primitive::$STRING, Primitive::$INT]), $returns->type());
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.1")')]
  public function returns_never() {
    $returns= $this->declare('{ function fixture(): never { exit(); } }')->method('fixture')->returns();
    Assert::equals(Type::$NEVER, $returns->type());
  }

  #[Test]
  public function returns_self() {
    $type= $this->declare('{ function fixture(): self { } }');
    Assert::equals($type->class(), $type->method('fixture')->returns()->type());
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=7.1")')]
  public function return_never_more_specific_than_void() {
    $t= $this->declare('{ /** @return never */ function fixture(): void { exit(); } }');
    Assert::equals(Type::$NEVER, $t->method('fixture')->returns()->type());
  }

  #[Test]
  public function return_string_array_more_specific_than_array() {
    $t= $this->declare('{ /** @return string[] */ function fixture(): array { exit(); } }');
    Assert::equals(new ArrayType(Primitive::$STRING), $t->method('fixture')->returns()->type());
  }

  #[Test]
  public function return_string_map_more_specific_than_array() {
    $t= $this->declare('{ /** @return [:string] */ function fixture(): array { exit(); } }');
    Assert::equals(new MapType(Primitive::$STRING), $t->method('fixture')->returns()->type());
  }

  #[Test]
  public function return_function_more_specific_than_calable() {
    $t= $this->declare('{ /** @return function(): int */ function fixture(): callable { exit(); } }');
    Assert::equals(new FunctionType([], Primitive::$INT), $t->method('fixture')->returns()->type());
  }

  #[Test]
  public function annotations() {
    $t= $this->declare('{
      #[Author("Test")]
      public function fixture() { }
    }');
    $method= $t->method('fixture');

    Assert::equals(
      [Author::class => $method->annotation(Author::class)],
      iterator_to_array($method->annotations())
    );
  }

  #[Test]
  public function annotation() {
    $t= $this->declare('{
      #[Author("Test")]
      public function fixture() { }
    }');

    Assert::equals(['Test'], $t->method('fixture')->annotation(Author::class)->arguments());
  }

  #[Test]
  public function without_comment() {
    $t= $this->declare('{ public function fixture() { } }');
    Assert::null($t->method('fixture')->comment());
  }

  #[Test]
  public function with_single_line_comment() {
    $t= $this->declare('{ /** Returns the fixture */ public function fixture() { } }');
    Assert::equals('Returns the fixture', $t->method('fixture')->comment());
  }

  #[Test]
  public function with_multi_line_comment() {
    $t= $this->declare('{
      /**
       * Returns the fixture
       * or NULL.
       */
      public function fixture() { }
    }');
    Assert::equals("Returns the fixture\nor NULL.", $t->method('fixture')->comment());
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function closure_with_missing_instance() {
    $this->declare('{ public function fixture() { } }')->method('fixture')->closure(null);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function closure_with_incorrect_instance() {
    $this->declare('{ public function fixture() { } }')->method('fixture')->closure($this);
  }

  #[Test]
  public function hash_code() {
    $t= $this->declare('{ public function fixture() { } }');
    Assert::equals($t->name().'::fixture()', $t->method('fixture')->hashCode());
  }

  #[Test]
  public function string_representation_with_typed_parameter() {
    $t= $this->declare('{ public function fixture(array $s): string { } }');
    Assert::equals(
      'public function fixture(array $s): string',
      $t->method('fixture')->toString()
    );
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0")')]
  public function string_representation_with_union_typed_parameter() {
    $t= $this->declare('{ public function fixture(string|int $s): string { } }');
    Assert::equals(
      'public function fixture(string|int $s): string',
      $t->method('fixture')->toString()
    );
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0")')]
  public function string_representation_with_nullable_union_typed_parameter() {
    $t= $this->declare('{ public function fixture(string|int|null $s): string { } }');
    Assert::equals(
      'public function fixture(?string|int $s): string',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_with_apidoc_parameter() {
    $t= $this->declare('{
      /** @param array<string> $s */
      public function fixture($s): string { }
    }');
    Assert::equals(
      'public function fixture(array<string> $s): string',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_with_unconstrained_parameter() {
    $t= $this->declare('{ public function fixture($s): string { } }');
    Assert::equals(
      'public function fixture(var $s): string',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_with_typed_return() {
    $t= $this->declare('{ public function fixture(): string { } }');
    Assert::equals(
      'public function fixture(): string',
      $t->method('fixture')->toString()
    );
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0")')]
  public function string_representation_with_union_typed_return() {
    $t= $this->declare('{ public function fixture(): string|int { } }');
    Assert::equals(
      'public function fixture(): string|int',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_with_apidoc_return() {
    $t= $this->declare('{
      /** @return array<string> */
      public function fixture() { }
    }');
    Assert::equals(
      'public function fixture(): array<string>',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function string_representation_without_return() {
    $t= $this->declare('{ public function fixture() { } }');
    Assert::equals(
      'public function fixture(): var',
      $t->method('fixture')->toString()
    );
  }

  #[Test]
  public function compare_to_self() {
    $type= $this->declare('{ public function a() { } }');
    Assert::equals(0, $type->method('a')->compareTo($type->method('a')));
  }

  #[Test]
  public function compare_to_other_method() {
    $type= $this->declare('{ public function a() { } public function b() { } }');
    Assert::equals(-1, $type->method('a')->compareTo($type->method('b')));
  }

  #[Test]
  public function compare_to_other_value() {
    $type= $this->declare('{ public function a() { } }');
    Assert::equals(1, $type->method('a')->compareTo(null));
  }
}