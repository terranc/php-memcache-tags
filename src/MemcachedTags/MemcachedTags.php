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

use InvalidArgumentException;
use Memcached;
use MemcachedLock\MemcachedLock;

class MemcachedTags implements TagsInterface {

    const VERSION = '0.1.0';

    const COMPILATION_ALL = 0;
    const COMPILATION_AND = 1;
    const COMPILATION_OR  = 2;
    const COMPILATION_XOR = 3;

    protected $lockPrefix = '_tagLock_';

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
    public function __construct(Memcached $Memcached, $prefix = 'mtag') {
        $this->Memcached = $Memcached;
        $this->prefix = $prefix;
    }

    /**
     * @param string $tag
     * @return string
     */
    protected function getKeyNameForTag($tag) {
        return $this->prefix. '_t_'. $tag;
    }

    /**
     * @param string $tag
     * @return string
     */
    protected function getKeyNameForKey($tag) {
        return $this->prefix. '_k_'. $tag;
    }

    /**
     * @param string $data
     * @return mixed
     */
    protected function decode($data) {
        return json_decode($data, true);
    }

    /**
     * @param mixed $data
     * @return string
     */
    protected function encode($data) {
        return json_encode($data);
    }

    /**
     * @param string $json
     * @param array $data
     * @return string
     */
    protected function addData($json, $data) {
        if (!$json || !($value = $this->decode($json))) {
            $value = array_values($data);
        } else {
            foreach ($data as $datum) {
                $value[] = $datum;
            }
            $value = array_unique($value);
        }
        return $this->encode($value);
    }

    /**
     * @param string $json
     * @param array $data
     * @return string
     */
    protected function removeData($json, $data) {
        if (!$json || !($value = $this->decode($json))) {
            return null;
        } else {
            $value = array_diff($value, $data);
            if (!$value) {
                return null;
            }
            return $this->encode(array_values($value));
        }
    }

    /**
     * @inheritdoc
     */
    public function addTags($tags, $keys) {
        $tags = is_array($tags) ? array_unique($tags) : (array) $tags;
        $keys = is_array($keys) ? array_unique($keys) : (array) $keys;
        $locks = $this->getLocksForTags($tags);

        $count = 0;
        foreach ($tags as $tag) {
            $keyName = $this->getKeyNameForTag($tag);
            $json = $this->addData($this->Memcached->get($keyName), $keys);
            if ($this->Memcached->set($keyName, $json)) {
                ++$count;
            }
        }
        foreach ($keys as $key) {
            $keyName = $this->getKeyNameForKey($key);
            $json = $this->addData($this->Memcached->get($keyName), $tags);
            $this->Memcached->set($keyName, $json);
        }
        return $count === count($tags);
    }

    /**
     * @inheritdoc
     */
    public function deleteKeysByTag($tag) {
        return $this->deleteKeysByTags((array) $tag);
    }

    /**
     * @inheritdoc
     */
    public function deleteKeysByTags(array $tags, $compilation = MemcachedTags::COMPILATION_ALL) {
        $locks = $this->getLocksForTags($tags);
        if ($compilation === self::COMPILATION_ALL) {
            $compilation = self::COMPILATION_OR;
        }
        $keys = $this->getKeysByTags($tags, $compilation);
        $tags = [];
        $result = 0;
        foreach ($keys as $key) {
            if ($this->Memcached->delete($key)) {
                ++$result;
            }
            if ($t = $this->Memcached->get($keyName = $this->getKeyNameForKey($key))) {
                $tags[] = $this->decode($t);
            }
            $this->Memcached->delete($keyName);
        }
        if (!$tags) {
            return $result;
        }
        $tags = array_unique(call_user_func_array('array_merge', $tags));
        foreach ($tags as $tag) {
            $keyName = $this->getKeyNameForTag($tag);
            $value = $this->removeData($this->Memcached->get($keyName), $keys);
            if (!$value) {
                $this->Memcached->delete($keyName);
            } else {
                $this->Memcached->set($keyName, $value);
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getKeysByTag($tag) {
        return $this->decode($this->Memcached->get($this->getKeyNameForTag($tag))) ?: [];
    }

    /**
     * @inheritdoc
     */
    public function getKeysByTags(array $tags, $compilation = MemcachedTags::COMPILATION_ALL) {
        $data = [];
        foreach ($tags as $tag) {
            $data[$tag] = $this->getKeysByTag($tag);
        }
        switch ($compilation) {
            case self::COMPILATION_ALL:
                return $data;
            case self::COMPILATION_AND:
                return array_values(call_user_func_array('array_intersect', $data));
            case self::COMPILATION_OR:
                return array_values(array_unique(call_user_func_array('array_merge', $data)));
            case self::COMPILATION_XOR:
                return array_values(call_user_func_array('array_diff', $data));
            default:
                throw new InvalidArgumentException('Unknown compilation type '. $compilation);
        }
    }

    /**
     * @param string|string[] $tags
     * @return MemcachedLock[]
     */
    protected function getLocksForTags($tags) {
        $tags = (array) $tags;
        $locks = [];
        foreach ($tags as $tag) {
            $locks[] = $this->createLock($this->prefix . $this->lockPrefix . $tag);
        }
        return $locks;
    }

    /**
     * @param string $key
     * @param int $locktime
     * @param int $waittime
     * @return MemcachedLock
     * @throws \MemcachedLock\Exception\LockHasAcquiredAlreadyException
     */
    protected function createLock($key, $locktime = 2, $waittime = 3) {
        $Lock = new MemcachedLock($this->Memcached, $key);
        $Lock->acquire($locktime, $waittime);
        return $Lock;
    }

} 
