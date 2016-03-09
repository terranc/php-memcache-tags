<?php
/**
 * This file is part of MemcachedTags.
 * git: https://github.com/cheprasov/php-memcached-tags
 *
 * (C) Alexander Cheprasov <cheprasov.84@ya.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
require('./vendor/autoload.php');

use MemcachedTags\MemcachedTags;

// Example 1. Create new Instance

$Memcached = new \Memcached();
$Memcached->addServer('127.0.0.1', '11211');
$Memcached->flush();

$MemcachedTags = new MemcachedTags($Memcached, ['prefix' => 'users']);

// Example 2. Adding some tags to key

// some test data
$Memcached->set('user:1', '{"name": "Alexander", "sex": "m", "country": "UK",     "city": "London"}');
$Memcached->set('user:2', '{"name": "Irina",     "sex": "f", "country": "UK",     "city": "London"}');
$Memcached->set('user:3', '{"name": "Ilya",      "sex": "m", "country": "Russia", "city": "Petersburg"}');
$Memcached->set('user:4', '{"name": "Dima",      "sex": "m", "country": "Russia", "city": "Murmansk"}');
$Memcached->set('user:5', '{"name": "Dom",       "sex": "m", "country": "UK",     "city": "London"}');

$MemcachedTags->addTagsToKeys(['city:London', 'country:UK'], ['user:1', 'user:2', 'user:5']);
$MemcachedTags->addTagsToKeys(['city:Murmansk', 'country:Russia'], 'user:4');
$MemcachedTags->addTagsToKeys(['city:Petersburg', 'country:Russia'], 'user:3');

$MemcachedTags->addTagsToKeys('sex:m', ['user:1', 'user:3', 'user:4', 'user:5']);
$MemcachedTags->addTagsToKeys('sex:f', 'user:2');

$MemcachedTags->addTagsToKeys('all', ['user:1','user:2', 'user:3', 'user:4', 'user:5']);

// or you can create key with tags

$MemcachedTags->setKeyWithTags('user:1', 'Alexander', ['country:UK', 'city:London', 'sex:m', 'all']);

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
// int(2) - Count of deleted keys
