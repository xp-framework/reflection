<?php namespace lang\reflection\unittest;

use lang\reflection\Modifiers;
use test\{Assert, Test, Values};

class ModifiersTest {

  /** @return iterable */
  private function cases() {
    yield [Modifiers::IS_STATIC, 'static'];
    yield [Modifiers::IS_ABSTRACT, 'abstract'];
    yield [Modifiers::IS_FINAL, 'final'];
    yield [Modifiers::IS_PUBLIC, 'public'];
    yield [Modifiers::IS_PROTECTED, 'protected'];
    yield [Modifiers::IS_PRIVATE, 'private'];
    yield [Modifiers::IS_NATIVE, 'native'];
    yield [Modifiers::IS_READONLY, 'readonly'];
    yield [Modifiers::IS_PRIVATE_SET, 'private(set)'];
    yield [Modifiers::IS_PROTECTED_SET, 'protected(set)'];
    yield [Modifiers::IS_PUBLIC_SET, 'public(set)'];
    yield [Modifiers::IS_FINAL | Modifiers::IS_PUBLIC, 'public final'];
    yield [Modifiers::IS_ABSTRACT | Modifiers::IS_PUBLIC, 'public abstract'];
    yield [Modifiers::IS_ABSTRACT | Modifiers::IS_PROTECTED, 'protected abstract'];
    yield [Modifiers::IS_STATIC | Modifiers::IS_PUBLIC, 'public static'];
    yield [Modifiers::IS_STATIC | Modifiers::IS_PROTECTED, 'protected static'];
    yield [Modifiers::IS_STATIC | Modifiers::IS_PRIVATE, 'private static'];
  }

  #[Test]
  public function can_create() {
    new Modifiers(MODIFIER_PUBLIC);
  }

  #[Test, Values(from: 'cases')]
  public function names_from_bits($bits, $names) {
    Assert::equals($names, (new Modifiers($bits, false))->names());
  }

  #[Test, Values(from: 'cases')]
  public function bits_from_names($bits, $names) {
    Assert::equals($bits, (new Modifiers($names, false))->bits());
  }

  #[Test, Values(from: 'cases')]
  public function bits_from_name_array($bits, $names) {
    Assert::equals($bits, (new Modifiers(explode(' ', $names), false))->bits());
  }

  #[Test, Values([['static', true], ['public static', true], ['public', false]])]
  public function isStatic($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isStatic());
  }

  #[Test, Values([['abstract', true], ['public abstract', true], ['public', false]])]
  public function isAbstract($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isAbstract());
  }

  #[Test, Values([['final', true], ['public final', true], ['public', false]])]
  public function isFinal($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isFinal());
  }

  #[Test, Values([['public', true], ['private', false]])]
  public function isPublic($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isPublic());
  }

  #[Test, Values([['protected', true], ['public', false]])]
  public function isProtected($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isProtected());
  }

  #[Test, Values([['private', true], ['public', false]])]
  public function isPrivate($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isPrivate());
  }

  #[Test, Values([['native', true], ['public', false]])]
  public function isNative($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isNative());
  }

  #[Test, Values([['readonly', true], ['public', false]])]
  public function isReadonly($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isReadonly());
  }

  #[Test, Values([['public(set)', true], ['public', true]])]
  public function isPublicGet($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isPublic('get'));
  }

  #[Test, Values([['protected(set)', false], ['protected', true]])]
  public function isProtectedGet($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isProtected('get'));
  }

  #[Test, Values([['private(set)', false], ['private', true]])]
  public function isPrivateGet($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isPrivate('get'));
  }

  #[Test, Values([['public(set)', true], ['public', false]])]
  public function isPublicSet($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isPublic('set'));
  }

  #[Test, Values([['protected(set)', true], ['protected', false]])]
  public function isProtectedSet($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isProtected('set'));
  }

  #[Test, Values([['private(set)', true], ['private', false]])]
  public function isPrivateSet($input, $expected) {
    Assert::equals($expected, (new Modifiers($input))->isPrivate('set'));
  }

  #[Test]
  public function public_modifier_default_no_arg() {
    Assert::true((new Modifiers())->isPublic());
  }

  #[Test, Values([[0], [''], [[]]])]
  public function public_modifier_default_for_empty($arg) {
    Assert::true((new Modifiers($arg))->isPublic());
  }

  #[Test, Values([['static'], ['final'], ['abstract'], ['native']])]
  public function public_modifier_default_if_no_visibility_included_in($arg) {
    Assert::true((new Modifiers($arg))->isPublic());
  }

  #[Test]
  public function compare_to_same() {
    Assert::equals(0, (new Modifiers('public'))->compareTo(new Modifiers('public')));
  }

  #[Test]
  public function compare_to_different() {
    Assert::equals(-1, (new Modifiers('public'))->compareTo(new Modifiers('private')));
  }

  #[Test]
  public function hash_code() {
    Assert::equals(
      'M['.(MODIFIER_PUBLIC | MODIFIER_STATIC),
      (new Modifiers('public static'))->hashCode()
    );
  }

  #[Test]
  public function string_representation() {
    Assert::equals(
      'lang.reflection.Modifiers<public static>',
      (new Modifiers('public static'))->toString()
    );
  }

  #[Test]
  public function string_cast() {
    Assert::equals('public static', (string)(new Modifiers('public static')));
  }
}