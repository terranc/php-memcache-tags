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

interface TagsInterface {

    /**
     * @param string|string[] $tags
     * @param string|string[] $keys
     * @return int
     */
    public function addTags($tags, $keys);

    /**
     * @param string|string[] $tags
     * @return bool
     */
    public function deleteKeysByTags($tags);

    /**
     * @param string|string[] $tags
     * @return bool
     */
    public function deleteTags($tags);

    /**
     * @param string|string[] $tags
     * @return string[]
     */
    public function getKeysByTags($tags);

    /**
     * @param string|string[] $tags
     * @return string|string[]
     */
    public function getTagKeyNames($tags);

    /**
     * @param string|string[] $tags
     * @param int $expiration
     * @return int
     */
    public function touchTags($tags, $expiration = 0);

}
