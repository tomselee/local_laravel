<?php

use Illuminate\Support\Arr;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;

if (!function_exists('m_time')) {
    /**
     * 返回毫秒级时间戳.
     */
    function m_time()
    {
        list($usec, $sec) = explode(' ', microtime());

        return (float) sprintf('%.0f', (floatval($usec) + floatval($sec)) * 1000);
    }
}

if (!function_exists('parse_name')) {
    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     *
     * @param string $name    字符串
     * @param int    $type    转换类型
     * @param bool   $ucfirst 首字母是否大写（驼峰规则）
     *
     * @return string
     */
    function parse_name(string $name, $type = 0, bool $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        } else {
            return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $name), '_'));
        }
    }
}

if (!function_exists('error_msg')) {
    /**
     * 返回异常的错信信息.
     *
     * @param Throwable $e
     * @param int       $count
     * @param string[]  $contents
     *
     * @return array
     */
    function error_msg(Throwable $e, $count = 3, $contents = ['file', 'line'])
    {
        $stackTrace = collect(array_slice((array) $e->getTrace(), 0, $count))->map(function ($item) use ($contents) {
            return Arr::only($item, $contents);
        })->toArray();

        return [
            'class' => get_class($e),
            'msg'   => $e->getMessage(),
            'code'  => $e->getCode(),
            'line'  => $e->getLine(),
            'file'  => $e->getFile(),
            'trace' => $stackTrace,
        ];
    }
}

if (!function_exists('f_date')) {
    /**
     * 时间转换.
     *
     * @param int $time
     *
     * @return false|string
     */
    function f_date(int $time = 0)
    {
        if (0 == $time) {
            $time = time();
        }
        if (!is_numeric($time)) {
            return $time;
        }

        return $time ? date('Y-m-d H:i:s', $time) : '';
    }
}

if (!function_exists('str_filter')) {
    /**
     * 字符串过滤.
     *
     * @param $string
     *
     * @return string|string[]
     */
    function str_filter($string)
    {
        return str_replace([
            "\r\n",
            "\r",
            "\n",
            "\t",
        ], '', trim($string));
    }
}

if (!function_exists('arr_dimension')) {
    /**
     * 获取数组维度（一维或者多维）.
     *
     * @param $array
     *
     * @return int
     *
     * @author litongzhi 2022/10/17 17:29
     */
    function arr_dimension($array)
    {
        if (is_array(current($array))) {
            return 1 + arr_dimension(current($array));
        }

        return 1;
    }
}

if (!function_exists('is_empty_strongly')) {
    /**
     * 是否为空字符串-强类型校验(可用于传参0和空的区别校验,用于区分前端传0情况)
     * *** 1。类型不一致，返回false
     * *** 2。参数值不为空，返回false.
     *
     * @author litongzhi 2022/10/17 17:29
     */
    function is_empty_strongly($data)
    {
        //null或者空字符串的情况，即返回true
        if (null === $data || '' === $data) {
            return true;
        }

        return false;
    }
}

if (!function_exists('redis')) {
    /**
     * 获取redis实例.
     *
     * @return Connection|PhpRedisConnection
     *
     * @author litongzhi 2023/05/17 17:29
     */
    function redis($collectionName = '')
    {
        return \Illuminate\Support\Facades\Redis::connection($collectionName);
    }
}

if (!function_exists('system_out_print')) {
    /**
     * 控制台输出数据.
     *
     * @author litongzhi 2023/05/17 17:29
     */
    function system_out_print($value)
    {
        $clos = fopen('php://stdout', 'a');
        return fwrite($clos, $value . PHP_EOL);
    }
}
