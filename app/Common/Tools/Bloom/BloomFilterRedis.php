<?php

namespace App\Common\Tools\Bloom;

/**
 * 应用redis实现的布隆过滤器.
 */
class BloomFilterRedis extends BloomFilterHash
{
    /**
     * 须要应用一个办法来定义bucket的名字.
     */
    protected string $bucket = 'default';

    protected array $hashFunction = ['JSHash', 'PJWHash', 'ELFHash', 'BKDRHash', 'SDBRHash', 'DJBHash', 'DEKHash', 'FNVHash'];
    protected $Redis;

    public function __construct()
    {
        if (!$this->bucket || !$this->hashFunction) {
            throw new \Exception('须要定义bucket和hashFunction', 1);
        }
        $this->Redis = redis(); //假如这里你曾经连贯好了
    }

    /**
     * 设置桶值.
     *
     * @param string $value
     */
    public function setBucket(string $value): self
    {
        $this->bucket = $value;

        return $this;
    }

    /**
     * 设置对应校验的hash方法.
     *
     * @param array|string $value
     */
    public function setHashFunction($value): self
    {
        $values             = is_array($value) ? $value : [$value];
        $this->hashFunction = $values;

        return $this;
    }

    /**
     * 增加到汇合中.
     */
    public function add($string)
    {
        $this->Redis->multi();
        foreach ($this->hashFunction as $function) {
            $hash = $this->$function($string);
            $this->Redis->setBit($this->bucket, $hash, 1);
        }

        return $this->Redis->exec();
    }

    /**
     * 查问是否存在, 如果已经写入过，必然回true，如果没写入过，有肯定几率会误判为存在.
     */
    public function exists($string)
    {
        $this->Redis->multi();
        $len  = strlen($string);
        foreach ($this->hashFunction as $function) {
            $hash = $this->$function($string, $len);
            $this->Redis->getBit($this->bucket, $hash);
        }
        $res = $this->Redis->exec();
        foreach ($res as $bit) {
            if (0 == $bit) {
                return false;
            }
        }

        return true;
    }
}
