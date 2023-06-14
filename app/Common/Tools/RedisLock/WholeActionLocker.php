<?php

namespace App\Common\Tools\RedisLock;

use Illuminate\Redis\Connections\Connection;

/**
 * Class WholeActionLocker.
 *
 * @mixin RedisLocker
 *
 * 全局操作并发锁
 */
class WholeActionLocker
{
    const LOCK_OWNER_DEFAULT  = 'DEFAULT';

    protected static string $lockPrefix = '{whole_action_lock}:';
    protected int $ttl;
    protected int $retryTime;
    protected int $lockType;

    /**
     * @var Connection
     */
    protected Connection $redis;

    /**
     * @var RedisLocker
     */
    protected RedisLocker $locker;

    /**
     * 锁实例.
     *
     * @param string $type
     * @param int    $ttl       秒
     * @param int    $retryTime 秒
     *
     * @return static
     */
    public static function make($type = '', $ttl = 5, $retryTime = 10)
    {
        $static = static::class;
        !app()->has($static) && app()->singleton($static);

        return tap(app()->get($static), function ($instance) use ($type, $ttl, $retryTime) {
            /*
             * @var static $instance
             */
            $instance->lockType  = $type;
            $instance->ttl       = $ttl;
            $instance->retryTime = $retryTime;
            $instance->redis     = redis();
        });
    }

    /**
     * 阻塞等待获取锁
     *
     * @param          $operationNo
     * @param callable $callable
     *
     * @return bool
     */
    public function acquireLock($operationNo, callable $callable)
    {
        $locker = $this->getLock($operationNo);

        return $locker->block($this->retryTime, $callable);
    }

    /**
     * 获取锁
     *
     * @param        $operationNo
     * @param null   $callable
     * @param string $owner
     *
     * @return mixed
     */
    public function lock($operationNo, $callable = null, string $owner = self::LOCK_OWNER_DEFAULT)
    {
        $locker = $this->getLock($operationNo, $owner);

        return $locker->get($callable);
    }

    /**
     * Attempt to acquire the lock.
     *
     * @param        $operationNo
     * @param string $owner
     *
     * @return bool
     */
    public function acquire($operationNo, string $owner = self::LOCK_OWNER_DEFAULT)
    {
        $locker = $this->getLock($operationNo, $owner);

        return $locker->acquire();
    }

    /**
     * Release the lock.
     *
     * @param        $operationNo
     * @param string $owner
     *
     * @return void
     */
    public function release($operationNo, string $owner = self::LOCK_OWNER_DEFAULT)
    {
        $locker = $this->getLock($operationNo, $owner);

        return $locker->release();
    }

    /**
     * 检查锁是否被获取.
     *
     * @param $operationNo
     * @param string $owner
     *
     * @return bool
     */
    public function isLock($operationNo, string $owner = self::LOCK_OWNER_DEFAULT): bool
    {
        $locker       = $this->getLock($operationNo, $owner);
        $currentOwner = $locker->getCurrentOwner();
        if ($currentOwner == $owner) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * get lock class.
     *
     * @param $operationNo
     * @param string $owner
     *
     * @return RedisLocker
     */
    protected function getLock($operationNo, string $owner = self::LOCK_OWNER_DEFAULT)
    {
        $this->locker = new RedisLocker($this->redis, $this->getLockPrefix() . $operationNo, $this->ttl, $owner);

        return $this->locker;
    }

    /**
     * get lock prefix.
     *
     * @return string
     */
    protected function getLockPrefix()
    {
        $prefix = config('cache.prefix', '');

        return $prefix . self::$lockPrefix . $this->lockType;
    }

    public function __call($name, $arguments)
    {
        return $this->locker->$name(...$arguments);
    }
}
