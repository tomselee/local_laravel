<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Common\Tools\BloomFilterRedis;

class BloomTest extends TestCase
{
    public function testMakeRedis()
    {
        $redis = Redis();
        dd($redis->set('test', 'test'));
    }

    /**
     * 测试新增数据.
     *
     * @return void
     */
    public function testAddData()
    {
        $testRepeatList = ['test', 'test0', 'test1', 'test1', 'test3', 'test'];

        $cls = new BloomFilterRedis();
        $cls->setBucket('test_bucket')->setHashFunction('PJWHash');
        array_walk($testRepeatList, function ($item) use ($cls) {
            $cls->exists($item) && dump($item);

            !$cls->exists($item) && $cls->add($item);
        });
    }


}
