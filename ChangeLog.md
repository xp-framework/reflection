XP Reflection ChangeLog
=======================

## ?.?.? / ????-??-??

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