# Kache

A simple cache system with support for multiple drivers and a focus on performance. There is only a minimal wrapper between the exposed API and the underlying driver so as to keep the library as fast as possible.

# Installation

Either checkout the code and require `src/Kache.php`. Or use Composer:

    "require": { "laurent22/kache": "1.*" }

# Setup

## Using the file driver:

```php
Kache::setup(array(
	'driver' => 'file',
	'path' => '/path/to/cache_folder',
));
```

## Using the Redis driver:

```php
Kache::setup(array(
	'driver' => 'redis',
	'server' => array(
		'host' => '127.0.0.1',
		'port' => 6379,
		'dbindex' => 1,
	),
));
```

## Using the null driver (to disable caching):

```php
Kache::setup(array(
	'driver' => 'null',
));
```


# Usage

The class instance can be accessed either via `Konfig::instance()` or by the convenience method `k()`. For example:

```php
k()->set('somekey', 'somevalue', 120); // Cache for 2 minutes
var_dump(k()->get('somekey'));
$k()->delete('somekey');
```

You may also use the `getOrRun()` method which will either return the given key or, if it doesn't exist, will do the following:
- run the provided function.
- set the key to the value returned by the function.
This allows simplifying the boiler plate code needed when getting/setting cache values. For example, the following code:

```php
function getName() {
	$name = k()->get('name');
	if ($name !== null) return $name;
	$name = getNameFromDb();
	k()->set('name', $name, 600);
	return $name;
}
```

can be simplified to just this:

```php
function getName() {
	return k()->getOrRun('name', function() {
		return getNameFromDb();
	}, 600);
}
```
