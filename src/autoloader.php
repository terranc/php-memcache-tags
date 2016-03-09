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
namespace MemcachedTags;

spl_autoload_register(function($class) {
    if (__NAMESPACE__.'\\' !== substr($class, 0, strlen(__NAMESPACE__.'\\'))) {
        return false;
    }
    $classPath = __DIR__ .'/'. str_replace('\\', '/', $class) .'.php';
    if (is_file($classPath)) {
        include($classPath);
        return true;
    }
    return false;
}, false, true);
