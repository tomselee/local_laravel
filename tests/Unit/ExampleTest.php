<?php

namespace Tests\Unit;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
    }

    /**
     * 布隆算法.
     *
     * @return void
     */
    public function testRestBloom()
    {
        $redis = redis();
        //添加单个元素
        $redis->rawCommand('bf.add', 'user', 'user1');
        $redis->rawCommand('bf.add', 'user', 'user2');

        //添加多个元素
        $redis->rawCommand('bf.madd', 'user', 'user3', 'user4', 'user5');

        //查询单个元素是否存在
        $res = $redis->rawCommand('bf.exists', 'user', 'user1');
        var_dump($res);

        //查询多个元素是否存在
        $res = $redis->rawCommand('bf.mexists', 'user', 'user4', 'user5');
        var_dump($res);
    }
}
