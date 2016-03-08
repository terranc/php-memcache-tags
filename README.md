[![MIT license](http://img.shields.io/badge/license-MIT-brightgreen.svg)](http://opensource.org/licenses/MIT)

# MemcachedTags v1.0.0 for PHP >= 5.5

## CLASS IS IN DEVELOPMENT

## About
MemcachedTags for PHP is a mechanism for adding tags to keys in Memcached. It is very useful, if you need to select or delete some keys by tags. And tags are really useful for group invalidation.
## Usage

```php
<?php
require ('./vendor/autoload.php');
use MemcachedTags\MemcachedTags;

// Example 1. Create new Instance

$Memcached = new \Memcached();
$Memcached->addServer('127.0.0.1', '11211');
$Memcached->flush();

$MemcachedTags = new MemcachedTags($Memcached, 'tag_');

// Example 2. Adding some tags to key

// some test data
$Memcached->set('user:1', '{"name": "Alexander", "sex": "m", "country": "UK",     "city": "London"}');
$Memcached->set('user:2', '{"name": "Irina",     "sex": "f", "country": "UK",     "city": "London"}');
$Memcached->set('user:3', '{"name": "Ilya",      "sex": "m", "country": "Russia", "city": "Petersburg"}');
$Memcached->set('user:4', '{"name": "Dima",      "sex": "m", "country": "Russia", "city": "Murmansk"}');
$Memcached->set('user:5', '{"name": "Dom",       "sex": "m", "country": "UK",     "city": "London"}');

$MemcachedTags->addTags(['city:London', 'country:UK'], ['user:1', 'user:2', 'user:5']);
$MemcachedTags->addTags(['city:Murmansk', 'country:Russia'], 'user:4');
$MemcachedTags->addTags(['city:Petersburg', 'country:Russia'], 'user:3');

$MemcachedTags->addTags('sex:m', ['user:1', 'user:3', 'user:4', 'user:5']);
$MemcachedTags->addTags('sex:f', 'user:2');

$MemcachedTags->addTags('all', ['user:1','user:2', 'user:3', 'user:4', 'user:5']);

// Example 3. Get keys by tags

// Get users with tag <all>
var_dump(
    $MemcachedTags->getKeysByTag('all')
);
//    array(2) {
//      [0]=> string(6) "user:1"
//      [1]=> string(6) "user:2"
//      [2]=> string(6) "user:3"
//      [3]=> string(6) "user:4"
//      [4]=> string(6) "user:5"
//    }

// Get users with tag <country:UK>
var_dump(
    $MemcachedTags->getKeysByTag('country:UK')
);
//    array(2) {
//      [0]=> string(6) "user:1"
//      [1]=> string(6) "user:2"
//      [2]=> string(6) "user:5"
//    }

// Get users with tag <city:Petersburg> OR <city:Murmansk>
var_dump(
    $MemcachedTags->getKeysByTags(['city:Petersburg', 'city:Murmansk'], MemcachedTags::COMPILATION_OR)
);
//    array(2) {
//      [0]=> string(6) "user:3"
//      [1]=> string(6) "user:4"
//    }

// Get users with tags <country:UK> AND <sex:m>
var_dump(
    $MemcachedTags->getKeysByTags(['country:UK', 'sex:m'], MemcachedTags::COMPILATION_AND)
);
//    array(3) {
//      [0]=> string(6) "user:1"
//      [1]=> string(6) "user:5"
//    }

// Get users with tag <country:UK> AND WITHOUT <sex:m>
var_dump(
    $MemcachedTags->getKeysByTags(['country:UK', 'sex:m'], MemcachedTags::COMPILATION_XOR)
);
//    array(3) {
//      [0]=> string(6) "user:2"
//    }

// Example 4. Delete keys by tags

// Delete keys with tag <city:Murmansk>
var_dump(
    $MemcachedTags->deleteKeysByTag('city:Murmansk')
);
// int(1) - Count of deleted keys

// Delete keys with tag <city:London> WITHOUT <sex:f>
var_dump(
    $MemcachedTags->deleteKeysByTags(['city:London', 'sex:f'], MemcachedTags::COMPILATION_XOR)
);
// int(1) - Count of deleted keys

```

## Methods

#### MemcachedTags :: __construct ( `\Memcached` **$Memcached** , `string` **$prefix** = 'mtag' )
---
Create a new instance of MemcachedTags.

##### Method Pameters

1. \Memcached **$Memcached** - Instance of [Memcached](http://php.net/manual/en/book.memcached.php)
2. string **$prefix**, default = 'mtag' - is a prefix for service keys in Memcached storage.
3. int **$flags**, default = 0

##### Example

```php
$Lock = new MemcachedTags($Memcached);
// or
$Lock = new MemcachedTags($Memcached, 'some');

```

#### `bool` MemcachedTags :: addTags ( `string|string[]` **$tags** , `string|string[]` **$keys** )
---
Adds each key specified tags. Returns `true` on success or `false` on failure.

##### Method Pameters

1. string|string[] **$tags** - Tag or tags that will be added to each key.
2. string|string[] **$keys** - Existing keys in Memcached for tags

##### Example

```php
$MemcachedTags->addTags(['city:London', 'country:UK'], ['user:1', 'user:2', 'user:5']);
$MemcachedTags->addTags(['city:Murmansk', 'country:Russia'], 'user:4');
$MemcachedTags->addTags(['big', 'red'], 'apple');
$MemcachedTags->addTags(['green', 'tasty'], 'orange');
```

#### `int` MemcachedTags :: deleteKeysByTag ( `string` **$tag** )
---
Delete keys by tag. Returns count of deleted keys.

##### Method Pameters
1. string **tag** - Name of tag

##### Example

```php
$MemcachedTags->deleteKeysByTag('city:London');
or
$MemcachedTags->deleteKeysByTag('sql');
```

#### `int` MemcachedTags :: deleteKeysByTags ( `string[]` **$tags** [, `int` **$compilation** = MemcachedTags::COMPILATION_ALL] )
---
Delete keys by several tags. Returns count of deleted keys.

##### Method Pameters
1. string[] **tags** - List of tags
2. int **$compilation**, default = MemcachedTags::COMPILATION_ALL - The method of combining tags.
    * MemcachedTags::COMPILATION_ALL - The same as MemcachedTags::COMPILATION_OR.
    * MemcachedTags::COMPILATION_AND - Delete keys that have every tags.
    * MemcachedTags::COMPILATION_OR - Delete keys that have any tags.
    * MemcachedTags::COMPILATION_XOR - Delete keys containing tag1 that are not have any of the other tags.

##### Example

```php
// Delete all apples and oranges
$MemcachedTags->deleteKeysByTags(['apple', 'oranges']);

// Delete only big oranges
$MemcachedTags->deleteKeysByTags(['big', 'oranges'], MemcachedTags::COMPILATION_AND);

// Delete all orange expect big oranges
$MemcachedTags->deleteKeysByTags(['oranges', 'big'], MemcachedTags::COMPILATION_XOR);

```

#### `string[]` MemcachedTags :: getKeysByTag ( `string` **$tag** )
---
Returns a list of keys with tag.

##### Method Pameters
1. string **tag** - Name of tag

##### Example

```php
$MemcachedTags->getKeysByTag('big');
or
$MemcachedTags->getKeysByTag('red');
```

#### `string[]|array` MemcachedTags :: getKeysByTags ( `string[]` **$tags** [, `int` **$compilation** = MemcachedTags::COMPILATION_ALL] )
---
Returns a list of keys by several tags.

##### Method Pameters
1. string[] **tags** - List of tags
2. int **$compilation**, default = MemcachedTags::COMPILATION_ALL - The method of combining tags.
    * MemcachedTags::COMPILATION_ALL - Returns array with keys for every tag. `array(tag1 => [key1, key2], ...)`
    * MemcachedTags::COMPILATION_AND - Returns a list of keys that have every tags.
    * MemcachedTags::COMPILATION_OR - Returns a list of keys that have any tags.
    * MemcachedTags::COMPILATION_XOR - Returns a list of keys containing tag1 that are not have any of the other tags.

##### Example

```php
// Get all apples and oranges
$MemcachedTags->getKeysByTags(['apple', 'oranges']);

// Get all apples or oranges
$MemcachedTags->getKeysByTags(['apple', 'oranges'], MemcachedTags::COMPILATION_OR);

// Get only big oranges
$MemcachedTags->getKeysByTags(['big', 'oranges'], MemcachedTags::COMPILATION_AND);

// Get all orange expect big oranges
$MemcachedTags->getKeysByTags(['oranges', 'big'], MemcachedTags::COMPILATION_XOR);
```

## Installation

### Composer

Download composer:

    wget -nc http://getcomposer.org/composer.phar

and add dependency to your project:

    php composer.phar require cheprasov/php-memcached-tags

## Running tests

To run tests type in console:

    ./vendor/bin/phpunit ./test/

## Something doesn't work

Feel free to fork project, fix bugs and finally request for pull
