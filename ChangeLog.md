XP Reflection ChangeLog
=======================

## ?.?.? / ????-??-??

* Added `lang.reflect.Methods::with()` method to enumerate all methods
  with a given annotation
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