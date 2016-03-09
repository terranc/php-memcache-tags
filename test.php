<?php
require('./vendor/autoload.php');

use MemcachedTags\MemcachedTags;
use SimpleProfiler\Profiler;

// Example 1. Create new Instance

$Memcached = new \Memcached();
$Memcached->addServer('127.0.0.1', '11211');
$Memcached->flush();

$MTags = new MemcachedTags($Memcached);


for ($j = 0; $j< 100; ++$j) {
    for ($i = 0; $i< 100; ++$i) {
        Profiler::start('set1');
        $MTags->setKeyWithTags('key'. $i, $i, ['all']);
        Profiler::stop();
    }
    Profiler::start('rem1');
    $MTags->deleteKey('key'. $i);
    Profiler::stop();
}

Profiler::echoStat();
