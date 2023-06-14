<?php

namespace App\Common;

use Illuminate\Support\Arr;
use Yurun\Util\HttpRequest;
use Illuminate\Support\Facades\Log;

/**
 * Request請求工具文件.
 */
class RequestTools
{
    private $configData;

    //默认配置
    const DEFAULT_MAPPING = [
        'timeout'       => 10 * 1000, //超时时间10s
        'content_type'  => 'json',    //请求格式
        'headers'       => ['Content-Type' => 'application/json;charset=UTF-8', 'Accept' => 'application/json'],
        'is_log_result' => false,
    ];

    /**
     * 构建单例结构体.
     *
     * @return static
     */
    public static function make(): self
    {
        $static = static::class;
        !app()->has($static) && app()->singleton($static);

        return app()->get($static);
    }

    /**
     * get请求.
     *
     * @param string $url         请求uri或者接口地址
     * @param array  $params      请求参数
     * @param array  $headers     请求头
     * @param string $contentType 内容类型，支持null/json，为null时不处理
     * @param int    $timeout     超时时间ms
     *
     * @return mixed
     */
    public function get(string $url, array $params = [], array $headers = [], string $contentType = '', int $timeout = 0, $isLogResult = false, $cookies = [])
    {
        $this->setMethod('get');

        return $this->handle(...func_get_args());
    }

    /**
     * post请求.
     *
     * @param string $url         请求uri或者接口地址
     * @param array  $params      请求参数
     * @param array  $headers     请求头
     * @param string $contentType 内容类型，支持null/json，为null时不处理
     * @param int    $timeout     超时时间ms
     *
     * @return mixed
     */
    public function post($url, array $params = [], array $headers = [], string $contentType = '', int $timeout = 0, $isLogResult = false, $cookies = [])
    {
        $this->setMethod('post');

        return $this->handle(...func_get_args());
    }

    /**
     * 主流程.
     *
     * @param string $url         请求uri或者接口地址
     * @param array  $params      请求参数
     * @param array  $headers     请求头
     * @param string $contentType 内容类型，支持null/json，为null时不处理
     * @param int    $timeout     超时时间ms
     *
     * @return mixed
     */
    private function handle(string $url, array $params = [], array $headers = [], string $contentType = '', int $timeout = 0, $isLogResult = false, $cookies = [])
    {
        //初始化数据
        $this->init([
            'uri'           => $url,
            'headers'       => $headers,
            'content_type'  => $contentType,
            'timeout'       => $timeout,
            'is_log_result' => $isLogResult,
            'cookies'       => $cookies,
        ]);

        //构建请求
        $http     = new HttpRequest();
        $method   = $this->getConfig('method');
dump($this->configData);

        try {
            $response = $http
                ->headers($this->getConfig('headers'))
                ->timeout($this->getConfig('timeout'))
                ->cookies($cookies)
                ->url($this->getConfig('url'))
                ->params($params)
                ->contentType($this->getConfig('content_type'))
                ->method($method)
                ->send();
            $result = $response->body();
            $errMsg = $response->getError();
        } catch (\Exception $exception) {
            Log::error($this->getConfig('log_path'), [
                $exception->getCode(),
                $exception->getLine(),
                $exception->getMessage(),
            ]);
        } finally {
            //日志记录
            Log::info($this->getConfig('log_path'), [
                'url'     => $this->getConfig('url'),
                'request' => $params,
                'config'  => $this->configData,
                'result'  => $this->configData['is_log_result'] ? ['is_log_result' => $this->configData['is_log_result'], 'res' => ($result ?? [])] : ['is_log_result' => $this->configData['is_log_result']],
                'error'   => $errMsg ?? '',
            ]);
        }

        return isset($result) ? json_decode($result, true) : [];
    }

    /**
     * 初始化操作.
     *
     * @param $data
     */
    private function init($data)
    {
        //1. 设置应用名称
        if (!$this->getConfig('app_name')) {
            $this->setAppName();
        }

        //2. 设置配置信息
        $this->setConfig($data);

        //3. 设置日志目录
        if (!$this->getConfig('log_path')) {
            $this->setLogPath(sprintf('request_api.%s.%s', $this->getConfig('app_name'), $this->getConfig('')));
        }
    }

    /**
     ***************************************** 参数设置以及获取 ******************************************************.
     */
    /**
     * 设置请求方法.
     *
     * @param string $value 常用请求方法（post，get，put，head【！注意:download方法需要重写】）
     */
    public function setMethod(string $value)
    {
        $this->configData['method'] = $value;

        return $this;
    }

    /**
     * 设置请求目的端应用名称.
     *
     * @param string $name
     */
    public function setAppName(string $name = '')
    {
        $this->configData['app_name']  = $name ?: env('APP_NAME', 'hlyun_v5_center');

        return $this;
    }

    /**
     * 设置日志路径.
     *
     * @param string $value
     */
    public function setLogPath(string $value)
    {
        $this->configData['log_path']  = $value;

        return $this;
    }

    /**
     * 设置日志是否记录请求结果.
     *
     * @param $value
     */
    public function setIsLogResult($value)
    {
        $this->configData['is_log_result']  = $value;

        return $this;
    }

    /**
     * 初始化配置信息.
     *
     * @param array $configs
     */
    public function setConfig(array $configs)
    {
        //默认值以及初始化值设置
        $this->configData = array_merge(self::DEFAULT_MAPPING, $this->configData);

        //拼装完整请求地址
        $this->configData['url'] = preg_match('/http/', $configs['uri']) ? $configs['uri'] : $this->getUrl($configs['uri']);
    }

    /**
     * 获取配置.
     *
     * @param string $name 配置名称
     */
    public function getConfig(string $name)
    {
        return Arr::get($this->configData, $name);
    }

    /**
     * 获取请求完整地址.
     *
     * @param string $name
     */
    public function getUrl(string $name)
    {
        $uriMapping = config('request.' . $this->getConfig('app_name'));

        return Arr::get($uriMapping, 'host') . Arr::get($uriMapping, 'uri.' . $name);
    }

    /**
     * 设置系统cookies [key=>val].
     *
     * @param array $cookies
     * @return RequestTools
     */
    public function setCookies(array $cookies)
    {
        $this->configData['cookies'] = $cookies;

        return $this;
    }
}
