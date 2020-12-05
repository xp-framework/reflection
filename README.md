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
use lang\{Reflection, IllegalStateException};

$type= Reflection::of(IllegalStateException::class);

$type->name();      // lang.IllegalStateException
$type->literal();   // lang\IllegalStateException
$type->modifiers(); // Modifiers<public>

if ($type->instantiable()) {
  $instance= $type->newInstance('Testing');
}

$type->annotations();
$type->annotation(Author::class);

$type->constants();
$type->constant('CONST');

$type->properties();
$type->property('value');

$type->methods();
$type->method('toString');
```