<?php

namespace Tests\Feature\Leecode;

use Tests\TestCase;

class Test1_10 extends TestCase
{
    /**
     * 两数之和.
     *
     * @see https://leetcode.cn/problems/two-sum/
     */
    public function testTwoSum()
    {
        $nums   = [3, 3];
        $target = 6;
        dump($this->twoSum($nums, $target));
    }

    /**
     * 两数相加c.
     *
     * @see https://leetcode.cn/problems/add-two-numbers/
     */
    public function testAddTwoNumbers()
    {
        $nums   = [2, 4, 3];
        $target = [5, 6, 4];
        dump($this->addTwoNumbers($nums, $target));
    }

    /**
     ************************主业务逻辑******************************.
     */

    /**
     * @param int[] $nums
     * @param int   $target
     *
     * @return int[]
     */
    public function twoSum($nums, $target)
    {
        $arr = [];
        $tmp = [];
        foreach ($nums as $key => $value) {
            if (isset($tmp[$target - $value])) {
                $arr = [$tmp[$target - $value], $key];
                break;
            }
            $tmp[$value] = $key;
        }

        return $arr;
    }

    public function addTwoNumbers(array $l1, array $l2)
    {
        $l1_array = $this->getClsArray($l1);
        $l2_array = $this->getClsArray($l2);
        $res      = (int) implode('', array_reverse($l1_array)) + (int) implode('', array_reverse($l2_array));
        $res      = array_map('intval', str_split($res));

        return array_reverse($res);
    }

    public function testSA()
    {
        $res = [7, 0, 8];
        $cls = new \stdClass();
        $res = $this->setCls($cls, current($res), $res, 0);
        dd(json_decode(json_encode($res), true));
    }

    public function setCls($obj, $val, $res, $k)
    {
        $obj->val = $val;
        if (isset($res[$k + 1])) {
            $obj->next = $this->setCls($obj, $val, $res, $k + 1);
        } else {
            return $obj;
        }

        return $obj;
    }

    public function getClsArray($cls, $resArray = [])
    {
        if (isset($cls->val)) {
            $resArray[] = $cls->val;
        }
        if ($cls->next->val) {
            $this->getClsArray($cls->next, $resArray);
        } else {
            return $resArray;
        }

        return [];
    }
}

class ListNode
{
    public $val  = 0;
    public $next = null;

    public function __construct($val = 0, $next = null)
    {
        $this->val  = $val;
        $this->next = $next;
    }
}
