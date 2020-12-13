XP Reflection
=============

[![Build status on GitHub](https://github.com/xp-framework/reflection/workflows/Tests/badge.svg)](https://github.com/xp-framework/reflection/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/reflection/version.png)](https://packagist.org/packages/xp-framework/reflection)

Reflection library

```php
use lang\Reflection;
use org\example\{Base, Inject, Fixture};

$type= Reflection::of(Fixture::class);

$type->name();                // org.example.Fixture
$type->literal();             // Fixture::class
$type->modifiers();           // Modifiers<public>
$type->class();               // lang.XPClass instance
$type->classLoader();         // lang.ClassLoader instance
$type->parent();              // Type or NULL
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
}

$type->annotation(Inject::class); // Annotation or NULL
```

The constructor can be accessed via `constructor()`. Like members, it provides accessors for modifiers and annotations, as well as its declaring type.

```php
$type->constructor();                      // Constructor or NULL

if ($constructor= $type->constructor()) {
  $constructor->name();                    // 'POWER'
  $constructor->modifiers();               // Modifiers<public>
  $constructor->annotations();             // Annotations
  $constructor->annotation(Inject::class); // Annotation or NULL
  $constructor->declaredIn();              // Type
  $constructor->parameters();              // Parameters
  $constructor->parameter(0);              // Parameter or NULL
  $constructor->newInstance([]);           // (instance of the type)
}
```

All members (constants, properties and methods) can be accessed by iterating or by a shorthand lookup by name. Members provide accessors for modifiers and annotations, as well as their declaring type.

```php
$type->constant('POWER');                  // Constant or NULL
$type->property('value');                  // Property or NULL
$type->method('fixture');                  // Method or NULL

foreach ($type->constants() as $name => $constant) {
  $constant->name();                       // 'POWER'
  $constant->value();                      // 6100
  $constant->modifiers();                  // Modifiers<public>
  $constant->annotations();                // Annotations
  $constant->annotation(Inject::class);    // Annotation or NULL
  $constant->declaredIn();                 // Type
}

foreach ($type->properties() as $name => $property) {
  $property->name();                       // 'value'
  $property->modifiers();                  // Modifiers<public>
  $property->annotations();                // Annotations
  $property->annotation(Inject::class);    // Annotation or NULL
  $property->declaredIn();                 // Type
  $property->get($instance);               // (property value)
  $property->set($instance, $value);       // (value)
}

foreach ($type->methods() as $name => $method) {
  $method->name();                         // 'fixture'
  $method->modifiers();                    // Modifiers<public>
  $method->annotations();                  // Annotations
  $method->annotation(Inject::class);      // Annotation or NULL
  $method->declaredIn();                   // Type
  $method->returns();                      // TypeHint
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

foreach ($method->parameters() as $name => $parameter) {
  $parameter->position();                 // 0
  $parameter->name();                     // 'arg'
  $parameter->variadic();                 // false
  $parameter->optional();                 // true
  $parameter->default();                  // (parameter default value)
  $parameter->constraint();               // TypeHint
  $parameter->annotations();              // Annotations
  $parameter->annotation(Inject::class)   // Annotation or NULL
}
```