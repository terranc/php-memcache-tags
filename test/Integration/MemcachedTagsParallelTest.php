<?php
namespace Test\Integration;

use MemcachedTags\MemcachedTags;
use Parallel\Parallel;
use Parallel\Storage\MemcachedStorage;

class MemcachedTagsParallelTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Memcached
     */
    protected static $Memcached;

    protected function getMemcached() {
        $MC = new \Memcached();
        // MEMCACHED_TEST_SERVER defined in phpunit.xml
        $server = explode(':', MEMCACHED_TEST_SERVER);
        $MC->addServer($server[0], $server[1]);
        return $MC;
    }

    public function test_parallel() {
        $MC = $this->getMemcached();
        $MC->flush();
        unset($MC);

        $Storage = new MemcachedStorage(
            ['servers'=>[explode(':', MEMCACHED_TEST_SERVER)]]
        );
        $Parallel = new Parallel($Storage);

        $start = microtime(true) + 2;

        for ($i = 1; $i <= 5; $i++) {
            $Parallel->run('foo'.$i, function () use ($start) {
                $MemcachedTags = new MemcachedTags($MC = $this->getMemcached()
                );
                while (microtime(true) < $start) {
                    // wait for start
                }
                for ($i = 1; $i <= 1000; ++$i) {
                    $j = mt_rand(1, 100);
                    $MemcachedTags->setKeyWithTags('key_1_' . $j, $j, 'tag1');
                    $MemcachedTags->setKeysWithTags(['key_2_'.$j => $j, 'key_3_'.$j => $j], ['tag2', 'tag3']);
                }
                return 1;
            });
        }

        $MemcachedTags = new MemcachedTags($MC = $this->getMemcached());

        while (microtime(true) < $start) {
            // wait for start
        }
        for ($i = 1; $i <= 1000; ++$i) {
            $MemcachedTags->deleteKeysByTag('tag1');
            $MemcachedTags->deleteKeysByTags(['tag2', 'tag3']);
        }

        $Parallel->wait(['foo1','foo2','foo3','foo4','foo5']);

        $keys1 = $MemcachedTags->getKeysByTag('tag1');
        $keys2 = $MemcachedTags->getKeysByTag('tag2');
        $keys3 = $MemcachedTags->getKeysByTag('tag3');

        for ($i = 1; $i <= 100; ++$i) {
            $value = $MC->get('key_1_'. $i);
            $this->assertSame($value ? true : false, in_array('key_1_' . $i, $keys1));

            $value = $MC->get('key_2_'. $i);
            $this->assertSame($value ? true : false, in_array('key_2_' . $i, $keys2));

            $value = $MC->get('key_3_'. $i);
            $this->assertSame($value ? true : false, in_array('key_3_' . $i, $keys3));
        }

        $MC->flush();
    }

}
