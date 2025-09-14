XP Reflection ChangeLog
=======================

## ?.?.? / ????-??-??

## 3.5.0 / 2025-09-14

* Merged PR #45: Add `Reflection::package()` utility method - @thekid
* Merged PR #46: Add `Package::child()` to return child packages - @thekid

## 3.4.1 / 2025-08-15

* Fixed PHP 8.5 compatibility, see xp-framework/test#24 - @thekid

## 3.4.0 / 2025-04-05

* Merged PR #44: Implement support for final properties added in PHP 8.4
  (@thekid)

## 3.3.0 / 2024-11-02

* Added support for static method calls in constant expressions in PHP 7.
  This allows using first-class callables as annotation arguments.
  (@thekid)

## 3.2.0 / 2024-08-26

* Merged PR #43: Add support for asymmetric visibility for properties, see
  https://wiki.php.net/rfc/asymmetric-visibility-v2, targeted for PHP 8.4.
  (@thekid)

## 3.1.0 / 2024-03-28

* Merged PR #41: Add `lang.reflection.Type::declaredName()` which returns
  the class name without the namespace, if any
  (@thekid)
* Merged PR #40: Add support for the global package, fixing issue #39
  (@thekid)

## 3.0.0 / 2024-03-23

* Merged PR #38: Remove context from properties, methods, constructors and
  initializers
  (@thekid)
* Made this library compatible with XP 12, droppping support for all but
  the latest PHP 7 version. Minimum PHP version required is now **7.4**!
  (@thekid)

## 2.15.0 / 2024-08-27

* Backport PR #43, asymmetric visibility for properties - @thekid
* Backport PR #41, `lang.reflection.Type::declaredName()` - @thekid

## 2.14.1 / 2023-09-30

* Fixed `FromAttributes::imports()` for classes created inside `eval`
  (@thekid)

## 2.14.0 / 2023-09-23

* Added support for partial meta information - @thekid

## 2.13.6 / 2023-06-25

* Fixed missing `xp::$sn` lookup when reflecting generics - @thekid

## 2.13.5 / 2023-06-25

* Fixed parameter default values, by-reference and variadic markers,
  and parameter and return types being swallowed for functions inside
  annotations when using PHP 7
  (@thekid)

## 2.13.4 / 2023-06-25

* Fixed parsing global imports and grouped imports containing aliases,
  which surfaced as either *Syntax error, unexpected token `as`* or
  *Cannot use object of type PhpToken as array* errors
  (@thekid)

## 2.13.3 / 2023-06-04

* Fixed `lang.reflection.Constant::modifiers()` return type - @thekid

## 2.13.2 / 2023-06-04

* Fixed reading and writing non-public virtual properties - @thekid

## 2.13.1 / 2023-06-04

* Fixed parsing arrow functions inside arrays - @thekid

## 2.13.0 / 2023-06-04

* Merged PR #36: Make Type, Member and Parameter classes implement the
  `lang.reflection.Annotated` interface
  (@thekid)
* Fixed value to argument mapping for parameter annotations - @thekid

## 2.12.0 / 2023-06-03

* Merged PR #35: Add support for multiple arguments using arrays in eval
  (@thekid)

## 2.11.0 / 2023-04-17

* Merged PR #32: Add support for class constants types. For this PHP 8.3,
  see https://wiki.php.net/rfc/typed_class_constants
  (@thekid)

## 2.10.1 / 2023-04-15

* Fixed issue #33: Undefined index: class@anonymous - @thekid

## 2.10.0 / 2023-04-08

* Merged PR #31: Add forward compatibility with xp-framework/ast - @thekid

## 2.9.1 / 2023-02-12

* Merged PR #30: Migrate to new testing library - @thekid
* Fixed function expressions inside annotations in PHP 7.x - @thekid
* Fixed handling reflection on generics in PHP 7.x - @thekid

## 2.9.0 / 2023-01-29

* Merged PR #29: Add `lang.reflection.CannotInstantiate::type()` accessor
  (@thekid)

## 2.8.1 / 2023-01-29

* Merged PR #27: Fix ambiguous annotation values by checking an additional
  flag, see https://github.com/xp-framework/compiler/releases/tag/v8.8.1
  (@thekid)

## 2.8.0 / 2023-01-21

* Merged PR #26: Invocation exceptions consistency - @thekid

## 2.7.0 / 2023-01-15

* Merged PR #25: Add `Annotations::all()` with returns all annotations
  of a given type (or subtype).
  (@thekid)

## 2.6.0 / 2023-01-15

* Merged PR #24: Add support for named annotation arguments in PHP 7
  (@thekid)

## 2.5.0 / 2023-01-02

* Fixed parsing arrow functions (`fn() => E`) in annotations - @thekid
* Merged PR #23: Add Reflection::type() to return type instances - @thekid

## 2.4.1 / 2022-12-08

* Fixed compatibility with `xp-framework/ast` version 9+ - @thekid

## 2.4.0 / 2022-08-28

* Merged PR #22: Include type constants in xp reflect output - @thekid

## 2.3.0 / 2022-08-28

* Merged PR #21: Support `readonly` modifier on classes. Implements 8.2
  feature suggested in #20, see https://wiki.php.net/rfc/readonly_classes
  (@thekid)

## 2.2.0 / 2022-08-28

* Merged PR #19: Include type modifiers in ordering - @thekid

## 2.1.0 / 2022-03-04

* Merged PR #18: Also accept directories on command line - @thekid

## 2.0.1 / 2022-02-25

* Fixed #17: Call to undefined method WithHighlighting::writeLinef()
  (@thekid)

## 2.0.0 / 2022-01-09

* Changed (named) argument handling to be consistent during method
  invocations. Missing or incorrectly typed arguments now raise
  `lang.reflection.CannotInvoke` in all PHP versions 7.0...8.2
  (@thekid)
* Made this library compatible with `xp-framework/ast` version 8.0
  (@thekid)
* Fixed `lang.reflection.Parameter::default()` to parse parameter
  annotations correctly
  (@thekid)

## 1.9.1 / 2021-11-01

* Fixed excess named arguments not raising exceptions in PHP 7 - @thekid

## 1.9.0 / 2021-10-25

* Merged PR #16: Add support for named arguments in PHP 7 - @thekid

## 1.8.1 / 2021-10-21

* Made library compatible with XP 11 - @thekid

## 1.8.0 / 2021-09-12

* Merged PR #14: Implement PHP 8.1 readonly properties - @thekid

## 1.7.0 / 2021-08-05

* Added support for PHP 8.1 intersection types, implementing #12. See
  https://wiki.php.net/rfc/pure-intersection-types
  (@thekid)

## 1.6.0 / 2021-08-03

* Fixed `Method::invoke()`, `Property::get()` and `Property::set()` not
  raising exceptions in PHP 8.1, an incompatibility created by the RFC
  https://wiki.php.net/rfc/make-reflection-setaccessible-no-op
  (@thekid)
* Fixed warnings in PHP 8.1 about `getIterator()` compatibility, see
  https://wiki.php.net/rfc/internal_method_return_types
  (@thekid)
* Added support for reflection on native types, e.g. the `Countable`
  interface or the `DOMDocument` class
  (@thekid)

## 1.5.0 / 2021-04-25

* Merged PR #11: Add support for `never` return type - @thekid

## 1.4.0 / 2021-03-13

* Merged PR #10: Support PHP 8.1 native enums - @thekid

## 1.3.0 / 2021-03-06

* Added support for reflective access to non-constant expressions for
  parameter defaults, see xp-framework/compiler#104
  (@thekid)

## 1.2.0 / 2021-02-07

* Merged PR #8 and PR #9, adding `Parameters::accept()` to check whether
  given arguments would be accepted
  (@thekid)
* Added support for `@var` for properties (*and kept `@type` for BC*),
  see https://docs.phpdoc.org/3.0/guide/references/phpdoc/tags/var.html
  (@thekid)
* Merged PR #7: Add Parameters::at() and Parameters::named() - @thekid

## 1.1.0 / 2021-01-03

* Merged PR #6: Implement Type::initializer() for custom instantiation,
  implementing usecases suggested in #5
  (@thekid)

## 1.0.1 / 2020-12-25

* Fixed issue #4: Class loader is null when using backslashes - @thekid

## 1.0.0 / 2020-12-21

* Merged PR #3: Add reflection on packages (a.k.a. namespaces) - @thekid
* Added `native` modifier to internal classes - @thekid

## 0.9.0 / 2020-12-20

* Wrapped exceptions from `closure()` in `lang.IllegalArgumentException`
  (@thekid)
* Fixed method string representation when using PHP 8 native union types
  (@thekid)
* Included type in `lang.reflection.Property`'s string representation
  (@thekid)
* Added support for showing type documentation and members' purpose (its
  documentation's first sentence) by passing the `-d` / `--doc` flag to
  the `xp reflect` subcommand.
  (@thekid)
* Added `comment()` accessor to `Type` and `Member` classes, returning
  their respective api doc commments without `/` and `*`s, if any.
  (@thekid)
* Added support for including private and protected members by passing
  the `-a` / `--all` flag to the `xp reflect` subcommand.
  (@thekid)

## 0.8.0 / 2020-12-20

* Added `lang.reflection.Type::traits()` to enumerate used traits.
  (@thekid)
* Added `lang.reflection.Type::interfaces()` to enumerate implemented
  interfaces of a class or interface parents, respectively.
  (@thekid)

## 0.7.0 / 2020-12-19

* Added method `lang.reflection.Parameters::size()` which returns number
  of parameters
  (@thekid)
* Changed `Reflection::of()` to raise `lang.ClassNotFoundException` when
  a type name is passed as a string and loading the type fails.
  (@thekid)

## 0.6.0 / 2020-12-19

* Added `lang.reflection.Methods::annotated()` method to enumerate all
  methods with a given annotation
  (@thekid)
* Merged PR #2: Use meta information (*not only for annotations, but also
  for properties as well as method return and parameter types*)
  (@thekid)
* Fixed accessing meta data for type members via `xp::$meta` cache
  (@thekid)

## 0.5.0 / 2020-12-14

* Passed property to `AccessingFailed` and `CannotAccess` exceptions,
  and make it accessible via their `target()` method
  (@thekid)
* Made `Reflection::of()` also accept `lang.reflection.Type` instances
  (@thekid)
* Added `compoundName()` accessor to constants, properties and methods
  (@thekid)
* Passed method or constructor to `InvocationFailed` and `CannotInvoke`
  exceptions, and make it accessible via their `target()` method
  (@thekid)

## 0.4.0 / 2020-12-14

* Removed lookup for lowercase XP annotations, the AST library does not
  support them any longer
  (@thekid)
* Added `lang.reflection.Annotation::newInstance()` method - @thekid

## 0.3.1 / 2020-12-13

* Fixed syntax error when using type annotations with `eval` argument
  (@thekid)

## 0.3.0 / 2020-12-12

* Added `lang.reflection.Method::closure()` - @thekid
* Implemented context type check when reading and writing properties as
  well as when invoking methods and constructors
  (@thekid)

## 0.2.0 / 2020-12-12

* Changed code to attribute reflection instead of parsing the code in
  PHP 8 - @thekid

## 0.1.1 / 2020-12-12

* Fixed `Type` class to consistently raise CannotInstantiate exceptions
  for interfaces and abstract classes
  (@thekid)

## 0.1.0 / 2020-12-07

* Hello World! First release - @thekid