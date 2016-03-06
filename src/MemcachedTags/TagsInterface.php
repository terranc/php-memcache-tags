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
     * Add add tag(s) for key(s)
     * @param string|string[] $tags
     * @param string|string[] $keys
     * @return int Returns count of created/updated tags.
     */
    public function addTags($tags, $keys);

    /**
     * Delete all keys by some tag(s)
     * @param string|string[] $tags
     * @return int Returns count of keys.
     */
    public function deleteKeysByTags($tags);

    /**
     * Delete tag(s). It is not affect keys.
     * @param string|string[] $tags
     * @return int Return count of deleted tags.
     */
    public function deleteTags($tags);

    /**
     * Get keys by tag(s).
     * @param string|string[] $tags
     * @return string[] Returns list of keys.
     */
    public function getKeysByTags($tags);

    /**
     * Get key name for tag(s).
     * @param string|string[] $tags
     * @return string|string[]
     */
    public function getTagKeyNames($tags);

    /**
     * Update expiration for tag(s). It is not affect keys.
     * @param string|string[] $tags
     * @param int $expiration
     * @return int Returns count of updated tags
     */
    public function touchTags($tags, $expiration = 0);

}
