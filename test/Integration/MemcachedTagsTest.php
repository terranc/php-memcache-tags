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
        $this->assertSame(1, $MemcachedTags->deleteTags('hi'));
        $this->assertSame(['hello' => 'value 3', 'world' => 'value 4'], $MC->getMulti(['hello', 'world']));
        $this->assertSame(false, $MC->get('tag_hi'));

        $MC->set('tag_hi', 'hello|;|world');
        $this->assertSame(['hello', 'world'], $MemcachedTags->getKeysByTags('hi'));
        $this->assertSame(['hello' => 'value 3', 'world' => 'value 4'], $MC->getMulti(['hello', 'world']));
        $this->assertSame(['hello', 'world'], $MemcachedTags->getKeysByTags('hi'));

        $this->assertSame(2, $MemcachedTags->deleteKeysByTags('hi'));
        $this->assertSame([], $MC->getMulti(['hello', 'world']));
        $this->assertSame([], $MemcachedTags->getKeysByTags('hi'));

        $this->assertSame('foo|;|bar|;|hello|;|world', $MC->get('tag_test'));
        $this->assertSame(2, $MemcachedTags->deleteKeysByTags('test'));
        $this->assertSame(false, $MC->get('tag_test'));
        $this->assertSame(false, $MC->get('foo'));
        $this->assertSame(false, $MC->get('bar'));
    }

    /**
     * @see MemcachedTags::addTags
     */
    public function test_addTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC, 'tag_');

        $this->assertSame(1, $MemcachedTags->addTags('test', 'foo'));
        $this->assertSame('foo', $MC->get('tag_test'));

        $this->assertSame(1, $MemcachedTags->addTags('test', 'foo'));
        $this->assertSame('foo|;|foo', $MC->get('tag_test'));

        $this->assertSame(1, $MemcachedTags->addTags(['test', 'test'], ['bar', 'bar']));
        $this->assertSame('foo|;|foo|;|bar', $MC->get('tag_test'));

        $this->assertSame(3, $MemcachedTags->addTags(['tag1', 'tag2', 'test'], ['moo', 'too']));
        $this->assertSame('foo|;|foo|;|bar|;|moo|;|too', $MC->get('tag_test'));
        $this->assertSame('moo|;|too', $MC->get('tag_tag1'));
        $this->assertSame('moo|;|too', $MC->get('tag_tag2'));
    }

    /**
     * @see MemcachedTags::deleteKeysByTags
     */
    public function test_deleteKeysByTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC, 'tag_');

        $MC->setMulti([
            'foo' => '1',
            'bar' => '1',
            'moo' => '1',
            'too' => '1',
        ]);

        $this->assertSame(1, $MemcachedTags->addTags('tag1', 'foo'));
        $this->assertSame(1, $MemcachedTags->addTags('tag2', 'bar'));
        $this->assertSame(3, $MemcachedTags->addTags(['tag1', 'tag2', 'tag3'], ['moo', 'too']));

        $this->assertSame(3, $MemcachedTags->deleteKeysByTags('tag1'));
        $this->assertSame(false, $MC->get('foo'));
        $this->assertSame('1', $MC->get('bar'));
        $this->assertSame(false, $MC->get('moo'));
        $this->assertSame(false, $MC->get('too'));

        $this->assertSame(false, $MC->get('tag_tag1'));
        $this->assertSame('bar|;|moo|;|too', $MC->get('tag_tag2'));
        $this->assertSame('moo|;|too', $MC->get('tag_tag3'));

        $this->assertSame(0, $MemcachedTags->deleteKeysByTags('tag1'));
        $this->assertSame(0, $MemcachedTags->deleteKeysByTags('tag3'));
        $this->assertSame(false, $MC->get('tag_tag3'));
        $this->assertSame(1, $MemcachedTags->deleteKeysByTags('tag2'));
        $this->assertSame(false, $MC->get('tag_tag2'));
        $this->assertSame(false, $MC->get('bar'));

        $MC->setMulti([
            'foo' => '1',
            'bar' => '1',
            'moo' => '1',
            'too' => '1',
        ]);

        $this->assertSame('1', $MC->get('foo'));
        $this->assertSame('1', $MC->get('bar'));
        $this->assertSame('1', $MC->get('moo'));
        $this->assertSame('1', $MC->get('too'));

        $this->assertSame(1, $MemcachedTags->addTags('tag1', ['foo', 'bar']));
        $this->assertSame(1, $MemcachedTags->addTags('tag2', ['moo', 'too']));
        $this->assertSame(4, $MemcachedTags->deleteKeysByTags(['tag1', 'tag2']));

        $this->assertSame(false, $MC->get('foo'));
        $this->assertSame(false, $MC->get('bar'));
        $this->assertSame(false, $MC->get('moo'));
        $this->assertSame(false, $MC->get('too'));
    }


    /**
     * @see MemcachedTags::deleteTags
     */
    public function test_deleteTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC, 'tag_');

        $MC->setMulti([
            'foo' => '1',
            'bar' => '1',
            'moo' => '1',
            'too' => '1',
        ]);

        $this->assertSame(1, $MemcachedTags->addTags('tag1', 'foo'));
        $this->assertSame(1, $MemcachedTags->addTags('tag2', 'bar'));
        $this->assertSame(3, $MemcachedTags->addTags(['tag1', 'tag2', 'tag3'], ['moo', 'too']));

        $this->assertSame(1, $MemcachedTags->deleteTags('tag1'));
        $this->assertSame('1', $MC->get('foo'));
        $this->assertSame('1', $MC->get('bar'));
        $this->assertSame('1', $MC->get('moo'));
        $this->assertSame('1', $MC->get('too'));

        $this->assertSame(false, $MC->get('tag_tag1'));
        $this->assertSame('bar|;|moo|;|too', $MC->get('tag_tag2'));
        $this->assertSame('moo|;|too', $MC->get('tag_tag3'));

        $this->assertSame(0, $MemcachedTags->deleteTags('tag1'));
        $this->assertSame(2, $MemcachedTags->deleteTags(['tag2', 'tag3']));
        $this->assertSame(false, $MC->get('tag_tag2'));
        $this->assertSame(false, $MC->get('tag_tag3'));

        $this->assertSame('1', $MC->get('foo'));
        $this->assertSame('1', $MC->get('bar'));
        $this->assertSame('1', $MC->get('moo'));
        $this->assertSame('1', $MC->get('too'));
    }

    /**
     * @see MemcachedTags::getKeysByTags
     */
    public function test_getKeysByTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC, 'tag_');

        $MC->setMulti([
            'foo' => '1',
            'bar' => '1',
            'moo' => '1',
            'too' => '1',
        ]);

        $this->assertSame(1, $MemcachedTags->addTags('tag1', 'foo'));
        $this->assertSame(1, $MemcachedTags->addTags('tag2', 'bar'));
        $this->assertSame(3, $MemcachedTags->addTags(['tag1', 'tag2', 'tag3'], ['moo', 'too']));

        $this->assertSame(['foo', 'moo', 'too'], $MemcachedTags->getKeysByTags('tag1'));
        $this->assertSame(['bar', 'moo', 'too'], $MemcachedTags->getKeysByTags('tag2'));
        $this->assertSame(['moo', 'too'], $MemcachedTags->getKeysByTags('tag3'));
        $this->assertSame(['foo', 'moo', 'too', 'bar'], $MemcachedTags->getKeysByTags(['tag1', 'tag2']));
    }

    /**
     * @see MemcachedTags::getTagKeyNames
     */
    public function test_getTagKeyNames() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC, 'tag_');

        $this->assertSame('tag_tag1', $MemcachedTags->getTagKeyNames('tag1'));
        $this->assertSame('tag_test', $MemcachedTags->getTagKeyNames('test'));
        $this->assertSame(['tag_tag1', 'tag_tag1', 'tag_tag2'], $MemcachedTags->getTagKeyNames(['tag1', 'tag1', 'tag2']));
    }

    /**
     * @see MemcachedTags::touchTags
     */
    public function test_touchTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC, 'tag_');

        $MC->setMulti([
            'foo' => '1',
            'bar' => '1',
            'moo' => '1',
            'too' => '1',
        ]);

        $this->assertSame(1, $MemcachedTags->addTags('tag1', 'foo'));
        $this->assertSame(1, $MemcachedTags->addTags('tag2', 'bar'));
        $this->assertSame(1, $MemcachedTags->addTags('tag3', ['moo', 'too']));

        $this->assertSame(1, $MemcachedTags->touchTags('tag1'));
        $this->assertSame(2, $MemcachedTags->touchTags(['tag2', 'tag3']));

        sleep(1);

        $this->assertSame('foo', $MC->get('tag_tag1'));
        $this->assertSame('bar', $MC->get('tag_tag2'));
        $this->assertSame('moo|;|too', $MC->get('tag_tag3'));

        $this->assertSame(1, $MemcachedTags->touchTags('tag1', 1));
        $this->assertSame(2, $MemcachedTags->touchTags(['tag2', 'tag3'], 1));

        sleep(1);

        $this->assertSame(false, $MC->get('tag_tag1'));
        $this->assertSame(false, $MC->get('tag_tag2'));
        $this->assertSame(false, $MC->get('tag_tag3'));
    }

}
