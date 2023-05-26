<?php

namespace App\Common\Tools\Bloom;

/**
 * 应用redis实现的布隆过滤器.
 */
class BloomRedis extends BloomFilterHash
{
    /**
     * 须要应用一个办法来定义bucket的名字.
     */
    protected string $bucket = 'default';

    protected array $hashFunction = ['JSHash', 'PJWHash', 'ELFHash', 'BKDRHash', 'SDBMHash', 'DJBHash', 'DEKHash', 'FNVHash'];
    protected $Redis;
    protected $bloomLength = 1000;

    public function __construct(string $bucket = '', int $bloomLength = 0, array $hashFunction = [])
    {
        $bucket       && $this->bucket       = $bucket;
        $bloomLength  && $this->bloomLength  = $bloomLength;
        $hashFunction && $this->hashFunction = $hashFunction;
        throw_if(!$this->bucket || !$this->hashFunction, new \Exception('须要定义bucket和hashFunction', 1));
        $this->Redis = redis(); //假如这里你曾经连贯好了
    }

    /**
     * 添加 : 将哈希函数计算后的数字，在二进制对应位置置为1
     * 返回 : hash处理后的数字.
     *
     * @param string $string
     *
     * @return mixed
     */
    public function add(string $string)
    {
        $arr = [];
        foreach ($this->hashFunction as $function) {
            $arr[] = $hash = 0 == $this->bloomLength ?
                BloomFilterHash::$function($string) :
                BloomFilterHash::$function($string) % $this->bloomLength;
            $this->Redis->setBit($this->bucket, $hash, 1);
        }

        return $arr;
    }

    /**
     * 查询是否存在
     *  1,存在的一定会存在
     *  2,不存在有一定几率会误判.
     *
     * @param string $string
     *
     * @return bool
     */
    public function exists(string $string)
    {
        $len = strlen($string);
        $res = [];
        foreach ($this->hashFunction as $function) {
            $hash = 0 == $this->bloomLength ?
                BloomFilterHash::$function($string, $len) :
                BloomFilterHash::$function($string, $len) % $this->bloomLength;
            $res[] = $this->Redis->getBit($this->bucket, $hash);
        }
        foreach ($res as $bit) {
            if (0 == $bit) {
                return false;
            }
        }

        return true;
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
}
