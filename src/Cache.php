<?php

namespace KuaiNiu\Redis;

use Predis\Client;
use yii\base\InvalidConfigException;
use yii\di\Instance;

/**
 * Redis Cache implements a cache application component based on [redis](https://redis.io/) key-value store.
 * Redis Cache requires redis version 2.6.12 or higher to work properly.
 * It needs to be configured with a redis [[Connection]]. By default it will use the `redis` application component.
 * > Note: It is recommended to use separate [[Connection::$database|database]] for cache and do not share it with
 * > other components. If you need to share database, you should set [[$shareDatabase]] to `true` and make sure that
 * > [[$keyPrefix]] has unique value which will allow to distinguish between cache keys and other data in database.
 * See [[yii\caching\Cache]] manual for common cache operations that redis Cache supports.
 * Unlike the [[yii\caching\Cache]], redis Cache allows the expire parameter of [[set]], [[add]], [[mset]] and [[madd]] to
 * be a floating point number, so you may specify the time in milliseconds (e.g. 0.1 will be 100 milliseconds).
 * To use redis Cache as the cache application component, configure the application as follows,
 * ~~~
 * Or if you have configured the redis [[Connection]] as an application component, the following is sufficient:
 * ~~~
 * [
 *     'components' => [
 *         'cache' => [
 *             'class' => 'KuaiNiu\Redis\Cache',
 *             // 'redis' => 'redis' // id of the connection application component
 *         ],
 *     ],
 * ]
 * ~~~
 *
 * @property-read bool $isCluster Whether redis is running in cluster mode or not. This property is read-only.
 * @author Coding He Ping
 */
class Cache extends \yii\caching\Cache
{
    /**
     * @var Client|string|array the Redis [[Connection]] object or the application component ID of the Redis [[Connection]].
     * This can also be an array that is used to create a redis [[Connection]] instance in case you do not want do configure
     * redis connection as an application component.
     * After the Cache object is created, if you want to change this property, you should only assign it
     * with a Redis [[Connection]] object.
     */
    public $redis = 'redis';

    /**
     * Initializes the redis Cache component.
     * This method will initialize the [[redis]] property to make sure it refers to a valid redis connection.
     *
     * @throws InvalidConfigException|InvalidConfigException if [[redis]] is invalid.
     */
    public function init(): void
    {
        parent::init();
        $this->redis = Instance::ensure($this->redis, Client::class);
    }

    /**
     * Checks whether a specified key exists in the cache.
     * This can be faster than getting the value from the cache if the data is big.
     * Note that this method does not check whether the dependency associated
     * with the cached data, if there is any, has changed. So a call to [[get]]
     * may return false while exists returns true.
     *
     * @param mixed $key a key identifying the cached value. This can be a simple string or
     *                   a complex data structure consisting of factors representing the key.
     *
     * @return bool true if a value exists in cache, false if the value is not in the cache or expired.
     */
    public function exists($key): bool
    {
        $key = $this->buildKey($key);
        return (bool)$this->redis->exists($key);
    }

    protected function getValue($key)
    {
        return $this->redis->get($key);
    }

    protected function setValue($key, $value, $duration): bool
    {
        if ($duration === 0) {
            return (bool)$this->redis->set($key, $value);
        }

        $expire = ($duration * 1000);

        return (bool)$this->redis->set($key, $value, 'PX', $expire);
    }

    protected function addValue($key, $value, $duration): bool
    {
        if ($duration === 0) {
            return (bool)$this->redis->set($key, $value, 'NX');
        }

        $expire = ($duration * 1000);

        return (bool)$this->redis->set($key, $value, 'PX', $expire, 'NX');
    }

    protected function deleteValue($key)
    {
        return $this->redis->del($key);
    }

    protected function flushValues(): bool
    {
        return $this->redis->flushdb();
    }
}
