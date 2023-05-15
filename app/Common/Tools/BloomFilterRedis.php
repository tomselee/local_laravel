<?php

namespace App\Common\Tools;

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

/**
 * 抽象类：布隆过滤器涉及到的hash方法.
 */
abstract class BloomFilterHash
{
    /**
     * 由Justin Sobel编写的按位散列函数.
     */
    public function JSHash($string, $len = null)
    {
        $hash        = 1315423911;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; ++$i) {
            $hash ^= (($hash << 5) + ord($string[$i]) + ($hash >> 2));
        }

        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 该哈希算法基于AT＆T贝尔实验室的Peter J. Weinberger的工作。
     * Aho Sethi和Ulman编写的“编译器（原理，技术和工具）”一书倡议应用采纳此特定算法中的散列办法的散列函数。
     */
    public function PJWHash($string, $len = null)
    {
        $bitsInUnsignedInt = 4 * 8; //（unsigned int）（sizeof（unsigned int）* 8）;
        $threeQuarters     = ($bitsInUnsignedInt * 3) / 4;
        $oneEighth         = $bitsInUnsignedInt       / 8;
        $highBits          = 0xFFFFFFFF << (int) ($bitsInUnsignedInt - $oneEighth);
        $hash              = 0;
        $test              = 0;
        $len || $len       = strlen($string);
        for ($i = 0; $i < $len; ++$i) {
            $hash = ($hash << (int) ($oneEighth)) + ord($string[$i]);
        }
        $test = $hash & $highBits;
        if (0 != $test) {
            $hash = (($hash ^ ($test >> (int) ($threeQuarters))) & (~$highBits));
        }

        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 相似于PJW Hash性能，但针对32位处理器进行了调整。它是基于UNIX的零碎上的widley应用哈希函数。
     */
    public function ELFHash($string, $len = null)
    {
        $hash        = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; ++$i) {
            $hash = ($hash << 4) + ord($string[$i]);
            $x    = $hash & 0xF0000000;
            if (0 != $x) {
                $hash ^= ($x >> 24);
            }
            $hash &= ~$x;
        }

        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 这个哈希函数来自Brian Kernighan和Dennis Ritchie的书“The C Programming Language”。
     * 它是一个简略的哈希函数，应用一组奇怪的可能种子，它们都形成了31 .... 31 ... 31等模式，它仿佛与DJB哈希函数十分类似。
     */
    public function BKDRHash($string, $len = null)
    {
        $seed        = 131;  // 31 131 1313 13131 131313 etc..
        $hash        = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; ++$i) {
            $hash = (int) (($hash * $seed) + ord($string[$i]));
        }

        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 这是在开源SDBM我的项目中应用的首选算法。
     * 哈希函数仿佛对许多不同的数据集具备良好的总体散布。它仿佛实用于数据集中元素的MSB存在高差别的状况。
     */
    public function SDBMHash($string, $len = null)
    {
        $hash        = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; ++$i) {
            $hash = (int) (ord($string[$i]) + ($hash << 6) + ($hash << 16) - $hash);
        }

        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 由Daniel J. Bernstein传授制作的算法，首先在usenet新闻组comp.lang.c上向世界展现。
     * 它是有史以来公布的最无效的哈希函数之一。
     */
    public function DJBHash($string, $len = null)
    {
        $hash        = 5381;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; ++$i) {
            $hash = (int) (($hash << 5) + $hash) + ord($string[$i]);
        }

        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * Donald E. Knuth在“计算机编程艺术第3卷”中提出的算法，主题是排序和搜寻第6.4章。
     */
    public function DEKHash($string, $len = null)
    {
        $len || $len = strlen($string);
        $hash        = $len;
        for ($i = 0; $i < $len; ++$i) {
            $hash = (($hash << 5) ^ ($hash >> 27)) ^ ord($string[$i]);
        }

        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }

    /**
     * 参考 http://www.isthe.com/chongo/tech/comp/fnv/.
     */
    public function FNVHash($string, $len = null)
    {
        $prime       = 16777619; //32位的prime 2^24 + 2^8 + 0x93 = 16777619
        $hash        = 2166136261; //32位的offset
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; ++$i) {
            $hash = (int) ($hash * $prime) % 0xFFFFFFFF;
            $hash ^= ord($string[$i]);
        }

        return ($hash % 0xFFFFFFFF) & 0xFFFFFFFF;
    }
}
