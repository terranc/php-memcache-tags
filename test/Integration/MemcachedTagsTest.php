<?php
namespace Test\Integration;

use MemcachedTags\MemcachedTags;

class MemcachedTagsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Memcached
     */
    protected static $Memcached;

    public static function setUpBeforeClass() {
        static::$Memcached = new \Memcached();
        // MEMCACHED_TEST_SERVER defined in phpunit.xml
        $server = explode(':', MEMCACHED_TEST_SERVER);
        static::$Memcached->addServer($server[0], $server[1]);
    }

    public function setUp() {
        $Memcached = static::$Memcached;
        $this->assertSame(true, $Memcached->flush());
    }

    public function testMemcached() {
        $Memcached = static::$Memcached;
        $this->assertInstanceOf(\Memcached::class, $Memcached);
    }

    public function test_MemcachedTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC, 'tag_');

        $MC->set('foo', 'value 1');
        $this->assertSame(1, $MemcachedTags->addTags('test', 'foo'));
        $this->assertSame('foo', $MC->get('tag_test'));

        $MC->set('bar', 'value 2');
        $this->assertSame(1, $MemcachedTags->addTags('test', 'bar'));
        $this->assertSame('foo|;|bar', $MC->get('tag_test'));

        $MC->set('hello', 'value 3');
        $MC->set('world', 'value 4');
        $this->assertSame(2, $MemcachedTags->addTags(['test', 'hi'], ['hello', 'world']));
        $this->assertSame('foo|;|bar|;|hello|;|world', $MC->get('tag_test'));
        $this->assertSame('hello|;|world', $MC->get('tag_hi'));

        $this->assertSame(['foo' ,'bar' ,'hello', 'world'], $MemcachedTags->getKeysByTags('test'));
        $this->assertSame(['hello', 'world'], $MemcachedTags->getKeysByTags('hi'));

        $this->assertSame(['hello' => 'value 3', 'world' => 'value 4'], $MC->getMulti(['hello', 'world']));
        $this->assertSame(['tag_hi' => true], $MemcachedTags->deleteTags('hi'));
        $this->assertSame(['hello' => 'value 3', 'world' => 'value 4'], $MC->getMulti(['hello', 'world']));
        $this->assertSame(false, $MC->get('tag_hi'));

        $MC->set('tag_hi', 'hello|;|world');
        $this->assertSame(['hello', 'world'], $MemcachedTags->getKeysByTags('hi'));
        $this->assertSame(['hello' => 'value 3', 'world' => 'value 4'], $MC->getMulti(['hello', 'world']));
        $this->assertSame(['hello', 'world'], $MemcachedTags->getKeysByTags('hi'));

        $this->assertSame(true, $MemcachedTags->deleteKeysByTags('hi'));
        $this->assertSame([], $MC->getMulti(['hello', 'world']));
        $this->assertSame([], $MemcachedTags->getKeysByTags('hi'));

        $this->assertSame('foo|;|bar|;|hello|;|world', $MC->get('tag_test'));
        $this->assertSame(true, $MemcachedTags->deleteKeysByTags('test'));
        $this->assertSame(false, $MC->get('tag_test'));
        $this->assertSame(false, $MC->get('foo'));
        $this->assertSame(false, $MC->get('bar'));
    }

}
