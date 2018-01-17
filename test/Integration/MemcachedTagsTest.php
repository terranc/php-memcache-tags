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
        $Memcached->set('user:1', '{"name": "Alexander", "sex": "m", "country": "UK",     "city": "London"}');
        $Memcached->set('user:2', '{"name": "Irina",     "sex": "f", "country": "UK",     "city": "London"}');
        $Memcached->set('user:3', '{"name": "Ilya",      "sex": "m", "country": "Russia", "city": "Petersburg"}');
        $Memcached->set('user:4', '{"name": "Dima",      "sex": "m", "country": "Russia", "city": "Murmansk"}');
        $Memcached->set('user:5', '{"name": "Dom",       "sex": "m", "country": "UK",     "city": "London"}');
    }

    public function testMemcached() {
        $Memcached = static::$Memcached;
        $this->assertInstanceOf(\Memcached::class, $Memcached);
    }

    /**
     * @see MemcachedTags::addTagsToKeys
     */
    public function test_addTagsToKeys() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC);

        $this->assertSame(true, $MemcachedTags->addTagsToKeys(['city:London', 'country:UK'], ['user:1', 'user:2', 'user:5']));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_city:London'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_country:UK'));
        $this->assertSame('city:London||country:UK', $MC->get('tag_k_user:1'));
        $this->assertSame('city:London||country:UK', $MC->get('tag_k_user:2'));
        $this->assertSame('city:London||country:UK', $MC->get('tag_k_user:5'));

        $this->assertSame(true, $MemcachedTags->addTagsToKeys(['city:Murmansk', 'country:Russia'], 'user:4'));
        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:4', $MC->get('tag_t_country:Russia'));
        $this->assertSame('city:Murmansk||country:Russia', $MC->get('tag_k_user:4'));

        $this->assertSame(true, $MemcachedTags->addTagsToKeys(['city:Petersburg', 'country:Russia'], 'user:3'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame('city:Petersburg||country:Russia', $MC->get('tag_k_user:3'));

        $this->assertSame(true, $MemcachedTags->addTagsToKeys('sex:m', ['user:1', 'user:3', 'user:4', 'user:5']));
        $this->assertSame('user:1||user:3||user:4||user:5', $MC->get('tag_t_sex:m'));
        $this->assertSame('city:London||country:UK||sex:m', $MC->get('tag_k_user:1'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m', $MC->get('tag_k_user:4'));
        $this->assertSame('city:London||country:UK||sex:m', $MC->get('tag_k_user:5'));

        $this->assertSame(true, $MemcachedTags->addTagsToKeys('sex:f', 'user:2'));
        $this->assertSame('user:2', $MC->get('tag_t_sex:f'));
        $this->assertSame('city:London||country:UK||sex:f', $MC->get('tag_k_user:2'));

        $this->assertSame(true, $MemcachedTags->addTagsToKeys('all', ['user:1','user:2', 'user:3', 'user:4', 'user:5']));
        $this->assertSame('user:1||user:2||user:3||user:4||user:5', $MC->get('tag_t_all'));
        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:1'));
        $this->assertSame('city:London||country:UK||sex:f||all', $MC->get('tag_k_user:2'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m||all', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m||all', $MC->get('tag_k_user:4'));
        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:5'));
    }

    /**
     * @param \Memcached $MC
     * @param MemcachedTags $MemcachedTags
     */
    protected function addTags(\Memcached $MC, MemcachedTags $MemcachedTags) {
        $this->assertSame(true, $MemcachedTags->addTagsToKeys(['city:London', 'country:UK'], ['user:1', 'user:2', 'user:5']));
        $this->assertSame(true, $MemcachedTags->addTagsToKeys(['city:Murmansk', 'country:Russia'], 'user:4'));
        $this->assertSame(true, $MemcachedTags->addTagsToKeys(['city:Petersburg', 'country:Russia'], 'user:3'));
        $this->assertSame(true, $MemcachedTags->addTagsToKeys('sex:m', ['user:1', 'user:3', 'user:4', 'user:5']));
        $this->assertSame(true, $MemcachedTags->addTagsToKeys('sex:f', 'user:2'));
        $this->assertSame(true, $MemcachedTags->addTagsToKeys('all', ['user:1','user:2', 'user:3', 'user:4', 'user:5']));

        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_city:London'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_country:UK'));
        $this->assertSame('user:1||user:3||user:4||user:5', $MC->get('tag_t_sex:m'));
        $this->assertSame('user:2', $MC->get('tag_t_sex:f'));
        $this->assertSame('user:1||user:2||user:3||user:4||user:5', $MC->get('tag_t_all'));

        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:1'));
        $this->assertSame('city:London||country:UK||sex:f||all', $MC->get('tag_k_user:2'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m||all', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m||all', $MC->get('tag_k_user:4'));
        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:5'));

        $this->assertSame(true, isset($MC->get('user:1')[0]));
        $this->assertSame(true, isset($MC->get('user:2')[0]));
        $this->assertSame(true, isset($MC->get('user:3')[0]));
        $this->assertSame(true, isset($MC->get('user:4')[0]));
        $this->assertSame(true, isset($MC->get('user:5')[0]));
    }

    /**
     * @see MemcachedTags::deleteKeys
     */
    public function test_deleteKeys() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC);
        $this->addTags($MC, $MemcachedTags);

        $this->assertSame(0, $MemcachedTags->deleteKeys(['user:foo']));

        $this->assertSame(1, $MemcachedTags->deleteKeys(['user:2']));

        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame('user:1||user:5', $MC->get('tag_t_city:London'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:1||user:5', $MC->get('tag_t_country:UK'));
        $this->assertSame('user:1||user:3||user:4||user:5', $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame('user:1||user:3||user:4||user:5', $MC->get('tag_t_all'));

        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:1'));
        $this->assertSame(false, $MC->get('tag_k_user:2'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m||all', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m||all', $MC->get('tag_k_user:4'));
        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:5'));

        $this->assertSame(true, isset($MC->get('user:1')[0]));
        $this->assertSame(false, isset($MC->get('user:2')[0]));
        $this->assertSame(true, isset($MC->get('user:3')[0]));
        $this->assertSame(true, isset($MC->get('user:4')[0]));
        $this->assertSame(true, isset($MC->get('user:5')[0]));

        $this->assertSame(2, $MemcachedTags->deleteKeys(['user:5', 'user:1', 'user:5']));

        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame(false, $MC->get('tag_t_city:London'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame(false, $MC->get('tag_t_country:UK'));
        $this->assertSame('user:3||user:4', $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame('user:3||user:4', $MC->get('tag_t_all'));

        $this->assertSame(false, $MC->get('tag_k_user:1'));
        $this->assertSame(false, $MC->get('tag_k_user:2'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m||all', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m||all', $MC->get('tag_k_user:4'));
        $this->assertSame(false, $MC->get('tag_k_user:5'));

        $this->assertSame(false, isset($MC->get('user:1')[0]));
        $this->assertSame(false, isset($MC->get('user:2')[0]));
        $this->assertSame(true, isset($MC->get('user:3')[0]));
        $this->assertSame(true, isset($MC->get('user:4')[0]));
        $this->assertSame(false, isset($MC->get('user:5')[0]));

        $this->assertSame(2, $MemcachedTags->deleteKeys(['user:3', 'user:4']));

        $this->assertSame(false, $MC->get('tag_t_city:Murmansk'));
        $this->assertSame(false, $MC->get('tag_t_city:Petersburg'));
        $this->assertSame(false, $MC->get('tag_t_city:London'));
        $this->assertSame(false, $MC->get('tag_t_country:Russia'));
        $this->assertSame(false, $MC->get('tag_t_country:UK'));
        $this->assertSame(false, $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame(false, $MC->get('tag_t_all'));

        $this->assertSame(false, $MC->get('tag_k_user:1'));
        $this->assertSame(false, $MC->get('tag_k_user:2'));
        $this->assertSame(false, $MC->get('tag_k_user:3'));
        $this->assertSame(false, $MC->get('tag_k_user:4'));
        $this->assertSame(false, $MC->get('tag_k_user:5'));

        $this->assertSame(false, isset($MC->get('user:1')[0]));
        $this->assertSame(false, isset($MC->get('user:2')[0]));
        $this->assertSame(false, isset($MC->get('user:3')[0]));
        $this->assertSame(false, isset($MC->get('user:4')[0]));
        $this->assertSame(false, isset($MC->get('user:5')[0]));
    }


    /**
     * @see MemcachedTags::deleteTags
     */
    public function test_deleteTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC);
        $this->addTags($MC, $MemcachedTags);

        $this->assertSame(0, $MemcachedTags->deleteTags(['tag:foo']));

        $this->assertSame(1, $MemcachedTags->deleteTags(['sex:f']));

        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_city:London'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_country:UK'));
        $this->assertSame('user:1||user:3||user:4||user:5', $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame('user:1||user:2||user:3||user:4||user:5', $MC->get('tag_t_all'));

        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:1'));
        $this->assertSame('city:London||country:UK||all', $MC->get('tag_k_user:2'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m||all', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m||all', $MC->get('tag_k_user:4'));
        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:5'));

        $this->assertSame(true, isset($MC->get('user:1')[0]));
        $this->assertSame(true, isset($MC->get('user:2')[0]));
        $this->assertSame(true, isset($MC->get('user:3')[0]));
        $this->assertSame(true, isset($MC->get('user:4')[0]));
        $this->assertSame(true, isset($MC->get('user:5')[0]));

        $this->assertSame(1, $MemcachedTags->deleteTags(['city:London']));

        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame(false, $MC->get('tag_t_city:London'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_country:UK'));
        $this->assertSame('user:1||user:3||user:4||user:5', $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame('user:1||user:2||user:3||user:4||user:5', $MC->get('tag_t_all'));

        $this->assertSame('country:UK||sex:m||all', $MC->get('tag_k_user:1'));
        $this->assertSame('country:UK||all', $MC->get('tag_k_user:2'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m||all', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m||all', $MC->get('tag_k_user:4'));
        $this->assertSame('country:UK||sex:m||all', $MC->get('tag_k_user:5'));

        $this->assertSame(1, $MemcachedTags->deleteTags(['all', 'all', 'foo']));

        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame(false, $MC->get('tag_t_city:London'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_country:UK'));
        $this->assertSame('user:1||user:3||user:4||user:5', $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame(false, $MC->get('tag_t_all'));

        $this->assertSame('country:UK||sex:m', $MC->get('tag_k_user:1'));
        $this->assertSame('country:UK', $MC->get('tag_k_user:2'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m', $MC->get('tag_k_user:4'));
        $this->assertSame('country:UK||sex:m', $MC->get('tag_k_user:5'));

        $this->assertSame(2, $MemcachedTags->deleteTags(['sex:m', 'sex:m', 'sex:f', 'country:UK']));

        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame(false, $MC->get('tag_t_city:London'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame(false, $MC->get('tag_t_country:UK'));
        $this->assertSame(false, $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame(false, $MC->get('tag_t_all'));

        $this->assertSame(false, $MC->get('tag_k_user:1'));
        $this->assertSame(false, $MC->get('tag_k_user:2'));
        $this->assertSame('city:Petersburg||country:Russia', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia', $MC->get('tag_k_user:4'));
        $this->assertSame(false, $MC->get('tag_k_user:5'));

        $this->assertSame(3, $MemcachedTags->deleteTags(['city:Murmansk', 'country:Russia', 'city:Petersburg']));

        $this->assertSame(false, $MC->get('tag_t_city:Murmansk'));
        $this->assertSame(false, $MC->get('tag_t_city:Petersburg'));
        $this->assertSame(false, $MC->get('tag_t_city:London'));
        $this->assertSame(false, $MC->get('tag_t_country:Russia'));
        $this->assertSame(false, $MC->get('tag_t_country:UK'));
        $this->assertSame(false, $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame(false, $MC->get('tag_t_all'));

        $this->assertSame(false, $MC->get('tag_k_user:1'));
        $this->assertSame(false, $MC->get('tag_k_user:2'));
        $this->assertSame(false, $MC->get('tag_k_user:3'));
        $this->assertSame(false, $MC->get('tag_k_user:4'));
        $this->assertSame(false, $MC->get('tag_k_user:5'));

        $this->assertSame(true, isset($MC->get('user:1')[0]));
        $this->assertSame(true, isset($MC->get('user:2')[0]));
        $this->assertSame(true, isset($MC->get('user:3')[0]));
        $this->assertSame(true, isset($MC->get('user:4')[0]));
        $this->assertSame(true, isset($MC->get('user:5')[0]));
    }

    /**
     * @see MemcachedTags::deleteKeysByTag
     */
    public function test_deleteKeysByTag() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC);
        $this->addTags($MC, $MemcachedTags);

        $this->assertSame(1, $MemcachedTags->deleteKeysByTag('sex:f'));
        $this->assertSame(0, $MemcachedTags->deleteKeysByTag('sex:f'));

        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame('user:1||user:5', $MC->get('tag_t_city:London'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:1||user:5', $MC->get('tag_t_country:UK'));
        $this->assertSame('user:1||user:3||user:4||user:5', $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame('user:1||user:3||user:4||user:5', $MC->get('tag_t_all'));

        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:1'));
        $this->assertSame(false, $MC->get('tag_k_user:2'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m||all', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m||all', $MC->get('tag_k_user:4'));
        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:5'));

        $this->assertSame(true, isset($MC->get('user:1')[0]));
        $this->assertSame(false, isset($MC->get('user:2')[0]));
        $this->assertSame(true, isset($MC->get('user:3')[0]));
        $this->assertSame(true, isset($MC->get('user:4')[0]));
        $this->assertSame(true, isset($MC->get('user:5')[0]));

        $this->assertSame(2, $MemcachedTags->deleteKeysByTag('city:London'));

        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame(false, $MC->get('tag_t_city:London'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame(false, $MC->get('tag_t_country:UK'));
        $this->assertSame('user:3||user:4', $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame('user:3||user:4', $MC->get('tag_t_all'));

        $this->assertSame(false, $MC->get('tag_k_user:1'));
        $this->assertSame(false, $MC->get('tag_k_user:2'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m||all', $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m||all', $MC->get('tag_k_user:4'));
        $this->assertSame(false, $MC->get('tag_k_user:5'));

        $this->assertSame(false, isset($MC->get('user:1')[0]));
        $this->assertSame(false, isset($MC->get('user:2')[0]));
        $this->assertSame(true, isset($MC->get('user:3')[0]));
        $this->assertSame(true, isset($MC->get('user:4')[0]));
        $this->assertSame(false, isset($MC->get('user:5')[0]));

        $this->assertSame(2, $MemcachedTags->deleteKeysByTag('all'));

        $this->assertSame(false, $MC->get('tag_t_city:Murmansk'));
        $this->assertSame(false, $MC->get('tag_t_city:Petersburg'));
        $this->assertSame(false, $MC->get('tag_t_city:London'));
        $this->assertSame(false, $MC->get('tag_t_country:Russia'));
        $this->assertSame(false, $MC->get('tag_t_country:UK'));
        $this->assertSame(false, $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame(false, $MC->get('tag_t_all'));

        $this->assertSame(false, $MC->get('tag_k_user:1'));
        $this->assertSame(false, $MC->get('tag_k_user:2'));
        $this->assertSame(false, $MC->get('tag_k_user:3'));
        $this->assertSame(false, $MC->get('tag_k_user:4'));
        $this->assertSame(false, $MC->get('tag_k_user:5'));

        $this->assertSame(false, isset($MC->get('user:1')[0]));
        $this->assertSame(false, isset($MC->get('user:2')[0]));
        $this->assertSame(false, isset($MC->get('user:3')[0]));
        $this->assertSame(false, isset($MC->get('user:4')[0]));
        $this->assertSame(false, isset($MC->get('user:5')[0]));
    }

    /**
     * @see MemcachedTags::deleteKeysByTags
     */
    public function test_deleteKeysByTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC);
        $this->addTags($MC, $MemcachedTags);

        $this->assertSame(0, $MemcachedTags->deleteKeysByTags([]));
        $this->assertSame(0, $MemcachedTags->deleteKeysByTags([], MemcachedTags::COMPILATION_XOR));

        $this->assertSame(1, $MemcachedTags->deleteKeysByTags(['country:Russia', 'city:Murmansk'], MemcachedTags::COMPILATION_XOR));

        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame(false, $MC->get('tag_t_city:Petersburg'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_city:London'));
        $this->assertSame('user:4', $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_country:UK'));
        $this->assertSame('user:1||user:4||user:5', $MC->get('tag_t_sex:m'));
        $this->assertSame('user:2', $MC->get('tag_t_sex:f'));
        $this->assertSame('user:1||user:2||user:4||user:5', $MC->get('tag_t_all'));

        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:1'));
        $this->assertSame('city:London||country:UK||sex:f||all', $MC->get('tag_k_user:2'));
        $this->assertSame(false, $MC->get('tag_k_user:3'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m||all', $MC->get('tag_k_user:4'));
        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:5'));

        $this->assertSame(true, isset($MC->get('user:1')[0]));
        $this->assertSame(true, isset($MC->get('user:2')[0]));
        $this->assertSame(false, isset($MC->get('user:3')[0]));
        $this->assertSame(true, isset($MC->get('user:4')[0]));
        $this->assertSame(true, isset($MC->get('user:5')[0]));

        $this->assertSame(1, $MemcachedTags->deleteKeysByTags(['country:Russia', 'city:Murmansk'], MemcachedTags::COMPILATION_AND));

        $this->assertSame(false, $MC->get('tag_t_city:Murmansk'));
        $this->assertSame(false, $MC->get('tag_t_city:Petersburg'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_city:London'));
        $this->assertSame(false, $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_country:UK'));
        $this->assertSame('user:1||user:5', $MC->get('tag_t_sex:m'));
        $this->assertSame('user:2', $MC->get('tag_t_sex:f'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_all'));

        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:1'));
        $this->assertSame('city:London||country:UK||sex:f||all', $MC->get('tag_k_user:2'));
        $this->assertSame(false, $MC->get('tag_k_user:3'));
        $this->assertSame(false, $MC->get('tag_k_user:4'));
        $this->assertSame('city:London||country:UK||sex:m||all', $MC->get('tag_k_user:5'));

        $this->assertSame(true, isset($MC->get('user:1')[0]));
        $this->assertSame(true, isset($MC->get('user:2')[0]));
        $this->assertSame(false, isset($MC->get('user:3')[0]));
        $this->assertSame(false, isset($MC->get('user:4')[0]));
        $this->assertSame(true, isset($MC->get('user:5')[0]));

        $this->assertSame(2, $MemcachedTags->deleteKeysByTags(['sex:m']));
        $this->assertSame(0, $MemcachedTags->deleteKeysByTags(['foo', 'bar'], MemcachedTags::COMPILATION_OR));

        $this->assertSame(false, $MC->get('tag_t_city:Murmansk'));
        $this->assertSame(false, $MC->get('tag_t_city:Petersburg'));
        $this->assertSame('user:2', $MC->get('tag_t_city:London'));
        $this->assertSame(false, $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:2', $MC->get('tag_t_country:UK'));
        $this->assertSame(false, $MC->get('tag_t_sex:m'));
        $this->assertSame('user:2', $MC->get('tag_t_sex:f'));
        $this->assertSame('user:2', $MC->get('tag_t_all'));

        $this->assertSame(false, $MC->get('tag_k_user:1'));
        $this->assertSame('city:London||country:UK||sex:f||all', $MC->get('tag_k_user:2'));
        $this->assertSame(false, $MC->get('tag_k_user:3'));
        $this->assertSame(false, $MC->get('tag_k_user:4'));
        $this->assertSame(false, $MC->get('tag_k_user:5'));

        $this->assertSame(false, isset($MC->get('user:1')[0]));
        $this->assertSame(true, isset($MC->get('user:2')[0]));
        $this->assertSame(false, isset($MC->get('user:3')[0]));
        $this->assertSame(false, isset($MC->get('user:4')[0]));
        $this->assertSame(false, isset($MC->get('user:5')[0]));

        $this->assertSame(1, $MemcachedTags->deleteKeysByTags(['all', 'all']));

        $this->assertSame(false, $MC->get('tag_t_city:Murmansk'));
        $this->assertSame(false, $MC->get('tag_t_city:Petersburg'));
        $this->assertSame(false, $MC->get('tag_t_city:London'));
        $this->assertSame(false, $MC->get('tag_t_country:Russia'));
        $this->assertSame(false, $MC->get('tag_t_country:UK'));
        $this->assertSame(false, $MC->get('tag_t_sex:m'));
        $this->assertSame(false, $MC->get('tag_t_sex:f'));
        $this->assertSame(false, $MC->get('tag_t_all'));

        $this->assertSame(false, $MC->get('tag_k_user:1'));
        $this->assertSame(false, $MC->get('tag_k_user:2'));
        $this->assertSame(false, $MC->get('tag_k_user:3'));
        $this->assertSame(false, $MC->get('tag_k_user:4'));
        $this->assertSame(false, $MC->get('tag_k_user:5'));

        $this->assertSame(false, isset($MC->get('user:1')[0]));
        $this->assertSame(false, isset($MC->get('user:2')[0]));
        $this->assertSame(false, isset($MC->get('user:3')[0]));
        $this->assertSame(false, isset($MC->get('user:4')[0]));
        $this->assertSame(false, isset($MC->get('user:5')[0]));
    }

    /**
     * @see MemcachedTags::getKeysByTag
     */
    public function test_getKeysByTag() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC);
        $this->addTags($MC, $MemcachedTags);

        $this->assertSame(['user:1', 'user:2', 'user:3', 'user:4', 'user:5'], $MemcachedTags->getKeysByTag('all'));
        $this->assertSame(['user:2'], $MemcachedTags->getKeysByTag('sex:f'));
        $this->assertSame(['user:1', 'user:2', 'user:5'], $MemcachedTags->getKeysByTag('city:London'));
        $this->assertSame(['user:1', 'user:2', 'user:5'], $MemcachedTags->getKeysByTag('country:UK'));
        $this->assertSame(['user:4'], $MemcachedTags->getKeysByTag('city:Murmansk'));
        $this->assertSame([], $MemcachedTags->getKeysByTag('foo'));
    }

    /**
     * @see MemcachedTags::getKeysByTags
     */
    public function test_getKeysByTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC);
        $this->addTags($MC, $MemcachedTags);

        $this->assertSame(['foo' => [], 'bar' => []], $MemcachedTags->getKeysByTags(['foo', 'bar']));
        $this->assertSame([], $MemcachedTags->getKeysByTags(['foo', 'bar'], MemcachedTags::COMPILATION_XOR));
        $this->assertSame([], $MemcachedTags->getKeysByTags(['foo', 'bar'], MemcachedTags::COMPILATION_AND));
        $this->assertSame(['sex:f' => ['user:2'], 'city:Murmansk' => ['user:4']], $MemcachedTags->getKeysByTags(['sex:f', 'city:Murmansk']));
        $this->assertSame(['sex:f' => ['user:2']], $MemcachedTags->getKeysByTags(['sex:f']));
        $this->assertSame(['user:2', 'user:4'], $MemcachedTags->getKeysByTags(['sex:f', 'city:Murmansk'], MemcachedTags::COMPILATION_OR));
        $this->assertSame(['user:1', 'user:5'], $MemcachedTags->getKeysByTags(['sex:m', 'city:London'], MemcachedTags::COMPILATION_AND));
        $this->assertSame(['user:3', 'user:4'], $MemcachedTags->getKeysByTags(['sex:m', 'city:London'], MemcachedTags::COMPILATION_XOR));
        $this->assertSame(['user:2'], $MemcachedTags->getKeysByTags(['city:London', 'sex:m'], MemcachedTags::COMPILATION_XOR));
        $this->assertSame([], $MemcachedTags->getKeysByTags(['all', 'sex:m', 'sex:f'], MemcachedTags::COMPILATION_XOR));
        $this->assertSame(['user:2'], $MemcachedTags->getKeysByTags(['all', 'sex:m', 'country:Russia'], MemcachedTags::COMPILATION_XOR));
    }

    /**
     * @see MemcachedTags::setKeyWithTags
     */
    public function test_setKeyWithTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC);
        $MC->flush();

        $this->assertSame(true, $MemcachedTags->setKeyWithTags('user:1', 'Alexander', ['city:London', 'sex:m']));

        $this->assertSame('Alexander', $MC->get('user:1'));
        $this->assertSame('city:London||sex:m', $MC->get('tag_k_user:1'));
        $this->assertSame('user:1', $MC->get('tag_t_city:London'));
        $this->assertSame('user:1', $MC->get('tag_t_sex:m'));

        $this->assertSame(true, $MemcachedTags->setKeyWithTags('user:2', 'Irina', ['city:London', 'sex:f', 'country:UK']));

        $this->assertSame('Irina', $MC->get('user:2'));
        $this->assertSame('city:London||sex:f||country:UK', $MC->get('tag_k_user:2'));
        $this->assertSame('user:1||user:2', $MC->get('tag_t_city:London'));
        $this->assertSame('user:2', $MC->get('tag_t_sex:f'));
        $this->assertSame('user:2', $MC->get('tag_t_country:UK'));

        $this->assertSame(true, $MemcachedTags->setKeyWithTags('user:3', 'Ilya', ['city:Petersburg', 'sex:m', 'country:Russia']));

        $this->assertSame('Ilya', $MC->get('user:3'));
        $this->assertSame('city:London||sex:f||country:UK', $MC->get('tag_k_user:2'));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame('user:1||user:3', $MC->get('tag_t_sex:m'));
        $this->assertSame('user:3', $MC->get('tag_t_country:Russia'));
    }

    /**
     * @see MemcachedTags::setKeysWithTags
     */
    public function test_setKeysWithTags() {
        $MC = static::$Memcached;
        $MemcachedTags = new MemcachedTags($MC);
        $MC->flush();

        $this->assertSame(true, $MemcachedTags->setKeysWithTags(['user:1' => 'Alexander', 'user:2' => 'Irina', 'user:5' => 'Dom'], ['city:London', 'country:UK']));

        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_city:London'));
        $this->assertSame('user:1||user:2||user:5', $MC->get('tag_t_country:UK'));
        $this->assertSame('city:London||country:UK', $MC->get('tag_k_user:1'));
        $this->assertSame('city:London||country:UK', $MC->get('tag_k_user:2'));
        $this->assertSame('city:London||country:UK', $MC->get('tag_k_user:5'));

        $this->assertSame('Alexander', $MC->get('user:1'));
        $this->assertSame('Irina', $MC->get('user:2'));
        $this->assertSame('Dom', $MC->get('user:5'));

        $this->assertSame(true, $MemcachedTags->setKeysWithTags(['user:4' => 'Dima'], ['city:Murmansk', 'country:Russia', 'sex:m']));
        $this->assertSame('user:4', $MC->get('tag_t_city:Murmansk'));
        $this->assertSame('user:4', $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:4', $MC->get('tag_t_sex:m'));
        $this->assertSame('city:Murmansk||country:Russia||sex:m', $MC->get('tag_k_user:4'));
        $this->assertSame('Dima', $MC->get('user:4'));

        $this->assertSame(true, $MemcachedTags->setKeysWithTags(['user:3' => 'Ilya'], ['city:Petersburg', 'country:Russia', 'sex:m']));
        $this->assertSame('user:3', $MC->get('tag_t_city:Petersburg'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_country:Russia'));
        $this->assertSame('user:4||user:3', $MC->get('tag_t_sex:m'));
        $this->assertSame('city:Petersburg||country:Russia||sex:m', $MC->get('tag_k_user:3'));
        $this->assertSame('Ilya', $MC->get('user:3'));
    }

}
