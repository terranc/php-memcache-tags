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

use Memcached;
use InvalidArgumentException;
use MemcachedLock\MemcachedLock;

class MemcachedTags implements TagsInterface {

    const VERSION = '0.1.0';

    protected $keySeparator = '|;|';

    protected $tagsSetAttempts = 2;

    protected $lockPrefix = 'lock_MemcachedTags_';

    /**
     * @var Memcached
     */
    protected $Memcached;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @param Memcached $Memcached
     * @param string $prefix
     */
    public function __construct(Memcached $Memcached, $prefix) {
        $this->prefix = $prefix;
        $this->Memcached = $Memcached;
        if ($Memcached->getOption(Memcached::OPT_COMPRESSION)) {
            $Memcached->setOption(Memcached::OPT_COMPRESSION, false);
        }
    }

    /**
     * @param string|string[] $keys
     */
    protected function checkKeysName($keys) {
        $keys = (array) $keys;
        foreach ($keys as $tag) {
            if (strpos($tag, $this->keySeparator) !== false) {
                throw new InvalidArgumentException('Please, do not use "'. $this->keySeparator .'" in key name');
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function addTags($tags, $keys) {
        $tags = (array) $tags;
        $locks = $this->getLocksForTags($tags);
        $this->checkKeysName($keys);
        $result = 0;
        if (is_array($keys)) {
            $keys = implode($this->keySeparator, $keys);
        }
        foreach ($tags as $tag) {
            $tagKey = $this->getTagKeyNames($tag);
            $attempts = $this->tagsSetAttempts;
            do {
                if ($this->Memcached->add($tagKey, $keys)) {
                    ++$result;
                    continue 2;
                }
                if ($this->Memcached->append($tagKey, $this->keySeparator . $keys)) {
                    ++$result;
                    continue 2;
                }
            } while (--$attempts > 0);
        }
        unset($locks);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function deleteKeysByTags($tags) {
        $locks = $this->getLocksForTags($tags);
        $result = $this->Memcached->deleteMulti($this->getKeysByTags($tags))
            && $this->Memcached->deleteMulti($this->getTagKeyNames((array) $tags));
        unset($locks);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function deleteTags($tags) {
        $locks = $this->getLocksForTags($tags);
        $result = $this->Memcached->deleteMulti($this->getTagKeyNames((array) $tags));
        unset($locks);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getKeysByTags($tags) {
        $tagKeys = $this->getTagKeyNames((array) $tags);
        $values = $this->Memcached->getMulti($tagKeys);
        $keys = [];
        foreach ($values as $value) {
            if (!$value || !($ks = explode($this->keySeparator, $value))) {
                continue;
            }
            // I don't use <array_push> or <array_merge> by performance reasons
            foreach ($ks as $k) {
                if (isset($k[0])) {
                    $keys[] = $k;
                }
            }
        }
        return array_unique($keys);
    }

    /**
     * @inheritdoc
     */
    public function getTagKeyNames($tags) {
        if (is_array($tags)) {
            return array_map(function($tag) {
                return $this->prefix . $tag;
            }, $tags);
        } else {
            return $this->prefix . $tags;
        }
    }

    /**
     * @inheritdoc
     */
    public function touchTags($tags, $expiration = 0) {
        $tags = (array) $tags;
        $result = 0;
        $tagKeys = $this->getTagKeyNames($tags);
        foreach ($tagKeys as $tagKey) {
            if ($this->Memcached->touch($tagKey, $expiration)) {
                ++$result;
            }
        }
        return $result;
    }

    /**
     * @param string|string[] $tags
     * @return MemcachedLock[]
     */
    protected function getLocksForTags($tags) {
        $tagKeys = $this->getKeysByTags((array) $tags);
        $locks = [];
        foreach ($tagKeys as $key) {
            $Lock = $this->createLock($this->prefix . $this->lockPrefix . $key);
            $Lock->acquire(2, 3);
            $locks[] = $Lock;
        }
        return $locks;
    }

    /**
     * @param string $key
     * @return MemcachedLock
     */
    protected function createLock($key) {
        return new MemcachedLock($this->Memcached, $key);
    }
} 
