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
     * Add tag(s) for key(s)
     * @param string|string[] $tags
     * @param string|string[] $keys
     * @return int Returns count of created/updated tags.
     */
    public function addTags($tags, $keys);

    /**
     * Delete all keys by tag
     * @param string|string[] $tag
     * @return int Returns count of deleted keys.
     */
    public function deleteKeysByTag($tag);

    /**
     * Delete all keys by tags
     * @param string[] $tags
     * @param int $compilation
     * @return int Returns count of deleted keys.
     */
    public function deleteKeysByTags(array $tags, $compilation);

    /**
     * Get keys by tag.
     * @param string $tag
     * @return string[] Returns list of keys.
     */
    public function getKeysByTag($tag);

    /**
     * Get keys by tags.
     * @param string[] $tags
     * @param int $compilation
     * @return string[] Returns list of keys.
     */
    public function getKeysByTags(array $tags, $compilation);

}
