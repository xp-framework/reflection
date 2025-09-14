XP Reflection
=============

[![Build status on GitHub](https://github.com/xp-framework/reflection/workflows/Tests/badge.svg)](https://github.com/xp-framework/reflection/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/reflection/version.svg)](https://packagist.org/packages/xp-framework/reflection)

This library provides a replacement for the XP Framework's reflection API.

Features
--------
**Concise**: This library aims at reducing code noise of the form `if (hasX(...)) { getX() }` by simply returning NULL from its accessor methods. Null handling has improved with the introduction of the null-coalesce operator `??` in PHP 7 and the null-safe invocation operator `?->` in PHP 8 (and in [XP Compiler](https://github.com/xp-framework/compiler)).

**PHP 7 & 8**: This library handles PHP 8 attributes in both PHP 7 (*using the compiler's AST library*) and PHP 8 (*using its native reflection API*).

**Subcommand**: This library provides an [RFC #0303 integration](https://github.com/xp-framework/rfc/issues/303) and offers a "reflect" subcommand for the new XP runners. See `xp help reflect` on how to use it.

API
---
The entry point class is the `lang.Reflection` class. It can be constructed by passing either objects, type literals (e.g. `Date::class`), fully qualified class names (e.g. `util.Date`) or `lang.XPClass` or PHP's `ReflectionClass` instances.

```php
use lang\Reflection;
use org\example\{Base, Inject, Fixture};

$type= Reflection::type(Fixture::class);

$type->name();                // org.example.Fixture
$type->declaredName();        // Fixture
$type->literal();             // Fixture::class
$type->modifiers();           // Modifiers<public>
$type->comment();             // (api doc comment)
$type->class();               // lang.XPClass instance
$type->classLoader();         // lang.ClassLoader instance
$type->package();             // Package or NULL
$type->parent();              // Type or NULL
$type->interfaces();          // Type[]
$type->traits();              // Type[]
$type->kind();                // Kind::$INTERFACE, Kind::$TRAIT, Kind::$CLASS, Kind::$ENUM
$type->is(Base::class);       // true

if ($type->instantiable()) {
  $instance= $type->newInstance('Testing');
}

$type->isInstance($instance); // true
```

Annotations can be accessed by iterating over `annotations()` or by the shorthand method `annotation()`.

```php
foreach ($type->annotations() as $annotation) {
  $annotation->type();            // Author::class
  $annotation->name();            // 'author'
  $annotation->arguments();       // ['Test', test => true]
  $annotation->argument(0);       // 'Test'
  $annotation->argument('test');  // true
  $annotation->newInstance();     // Author class instance
}

$type->annotation(Inject::class); // Annotation or NULL
```

The constructor can be accessed via `constructor()`. Like members, it provides accessors for modifiers and annotations, as well as its declaring type.

```php
$type->constructor();                      // Constructor or NULL

if ($constructor= $type->constructor()) {
  $constructor->name();                    // '__construct'
  $constructor->compoundName();            // 'org.example.Fixture::__construct()'
  $constructor->modifiers();               // Modifiers<public>
  $constructor->comment();                 // (api doc comment)
  $constructor->annotations();             // Annotations
  $constructor->annotation(Inject::class); // Annotation or NULL
  $constructor->declaredIn();              // Type
  $constructor->parameters();              // Parameters
  $constructor->parameter(0);              // Parameter or NULL
  $constructor->newInstance([]);           // (instance of the type)
}
```

Type instantiation can be controlled by using `initializer()`. It accepts either closures or named references to instance methods.

```php
// Instantiates type without invoking a constructor
// Any passed arguments are discarded silently
$instance= $type->initializer(null)->newInstance();

// Instantiates type by providing a constructor, regardless of whether one exists or not
// Arguments are passed on to the initializer function, which has access to $this
$instance= $type->initializer(function($name) { $this->name= $name; })->newInstance(['Test']);

// Instantiates type by selecting an instance method as an initializer
// The unserialize callback is invoked with ['name' => 'Test']
if ($unserialize= $type->initializer('__unserialize')) {
  $instance= $unserialize->newInstance([['name' => 'Test']]);
}
```

All members (constants, properties and methods) can be accessed by iterating or by a shorthand lookup by name. Members provide accessors for modifiers and annotations, as well as their declaring type.

```php
$type->constant('POWER');                  // Constant or NULL
$type->property('value');                  // Property or NULL
$type->method('fixture');                  // Method or NULL

foreach ($type->constants() as $name => $constant) {
  $constant->name();                       // 'POWER'
  $constant->compoundName();               // 'org.example.Fixture::POWER'
  $constant->value();                      // 6100
  $constant->modifiers();                  // Modifiers<public>
  $constant->comment();                    // (api doc comment)
  $constant->annotations();                // Annotations
  $constant->annotation(Inject::class);    // Annotation or NULL
  $constant->declaredIn();                 // Type
}

foreach ($type->properties() as $name => $property) {
  $property->name();                       // 'value'
  $property->compoundName();               // 'org.example.Fixture::$value'
  $property->modifiers();                  // Modifiers<public>
  $property->comment();                    // (api doc comment)
  $property->annotations();                // Annotations
  $property->annotation(Inject::class);    // Annotation or NULL
  $property->declaredIn();                 // Type
  $property->constraint();                 // Constraint
  $property->get($instance);               // (property value)
  $property->set($instance, $value);       // (value)
}

foreach ($type->methods() as $name => $method) {
  $method->name();                         // 'fixture'
  $method->compoundName();                 // 'org.example.Fixture::fixture()'
  $method->comment();                      // (api doc comment)
  $method->modifiers();                    // Modifiers<public>
  $method->annotations();                  // Annotations
  $method->annotation(Inject::class);      // Annotation or NULL
  $method->declaredIn();                   // Type
  $method->returns();                      // Constraint
  $method->parameters();                   // Parameters
  $method->parameter(0);                   // Parameter or NULL
  $method->closure($instance);             // Closure instance
  $method->invoke($instance, []);          // (method return value)
}
```

Method and constructor parameters can be retrieved by iterating via `parameters()`, by offset `parameter($position)` or by name `parameter($name)`.

```php
$method->parameter(0);                     // Parameter or NULL
$method->parameter('arg');                 // Parameter or NULL

$parameters= $method->parameters();        // Parameters instance
$parameters->at(0);                        // Parameter or NULL
$parameters->named('arg');                 // Parameter or NULL

$args= ['test'];
if ($parameters->accept($args)) {
  $method->invoke(null, $args);
}

foreach ($parameters as $name => $parameter) {
  $parameter->position();                  // 0
  $parameter->name();                      // 'arg'
  $parameter->variadic();                  // false
  $parameter->optional();                  // true
  $parameter->default();                   // (parameter default value)
  $parameter->constraint();                // Constraint
  $parameter->annotations();               // Annotations
  $parameter->annotation(Inject::class)    // Annotation or NULL
}
```

Packages can be reflected upon by passing namespace names to `Reflection::of()`.

```php
use lang\Reflection;

$package= Reflection::of('org.example');

$package->name();                          // org.example
$package->literal();                       // 'org\example'
$package->type('Fixture');                 // Type instance
$package->implementation($t, 'Fixture');   // Type instance (subclass of $t)
$package->types();                         // iterable with Type instances
$package->parent()                         // Package or NULL
$package->child('impl')                    // Child package "org.example.impl" or NULL
$package->children();                      // iterable with Package instances
$package->classLoaders();                  // iterable with lang.ClassLoader instances
```
