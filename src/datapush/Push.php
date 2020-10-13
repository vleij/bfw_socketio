<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/25
 * Time: 11:18
 */
namespace DataPush;
require_once __DIR__ . '../../../../../autoload.php';
require_once __DIR__ . '../../vendor/autoload.php';
use DataPush\base\Config;
use DataPush\base\StatusCode;
use Exception;
class Push
{
    private static $deploy = [];
    private static $url;
    function __construct()
    {
        $config = new Config();
        self::$deploy = $config->load(__DIR__."/deploy/socketio");
        if(empty(self::$deploy)){
            throw new Exception("socketio配置参数错误");
        }
        if(!empty(self::$deploy['client_link'])){
            self::$url =  self::$deploy['client_link'];
        }else{
            throw new Exception("client_link 客户端配置参数错误");
        }
    }

    /**
     * Notes: 向指定客户端发送数据
     * Date: 2020/9/26
     * Time: 10:13
     * @param $uid 客户端uid
     * @param $data
     * @return string|\think\response\Json
     * @author: 雷佳
     */
    public static function publish($data, $uid)
    {
        if(empty($data) || empty($uid)){
            throw new Exception("参数缺失");
        }
        $message = [
            'event'=>'push',
            'uid' => $uid,
            'data' => json_encode($data),
        ];
        try {
            $code = self::post_request(self::$url, $message);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return self::replace(StatusCode::getStatusCode('0'), $code);
    }

    /** 向属于当前组的客户端发送数据
     * Notes:
     * Date: 2020/9/26
     * Time: 10:42
     * @param $data
     * @param $to 分组
     * @return string|\think\response\Json
     * @author: 雷佳
     */
    public static function group_push($data, $to)
    {
        if(empty($data) || empty($to)){
            throw new Exception("参数缺失");
        }
        $message = [
            'event'=>'group_push',
            'to' => $to,
            'data' => json_encode($data),
        ];
        try {
            $code = self::post_request(self::$url, $message);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return self::replace(StatusCode::getStatusCode('0'), $code);
    }

    /**
     * Notes: 向所用客户端连接发送数据（广播）
     * Date: 2020/9/26
     * Time: 10:41
     * @param $data
     * @return string|\think\response\Json
     * @author: 雷佳
     */
    public static function broadcast($data)
    {
        if(empty($data)){
            throw new Exception("参数缺失");
        }
        $message = [
            'event'=>'broadcast',
            'data' => json_encode($data),
        ];
        try {
            $code = self::post_request(self::$url, $message);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return self::replace(StatusCode::getStatusCode('0'), $code);
    }

    /**
     * Notes: 定时推送
     * Date: 2020/9/25
     * Time: 17:23
     * @param bool $persistent 是否是持久的，如果只想定时执行一次，则传递false 默认是true，即一直定时执行
     * @param $time 时间间隔
     * @param $data
     * @return mixed
     * @throws Exception
     * @author: 雷佳
     */
    public static function timer_push($time, $data, $persistent=true)
    {
        if(empty($time) || empty($data)){
            throw new Exception("参数缺失");
        }
        $message = [
            'event'=>'open_timer',
            'push_time'=>$time,
            'bool' => $persistent,
            'data' => json_encode($data),
        ];
        $timer_id = self::post_request(self::$url, $message);
        return $timer_id;
    }

    /**
     * Notes:
     * Date: 2020/9/27
     * Time: 14:22
     * @param $time 定时触发时间
     * @param $data [Auth::class,'function'] Auth 类名  function 方法名
     * @param $parameter 函数需要的参数
     * @param bool $persistent 是否持续触发
     * @return mixed
     * @throws Exception
     * @author: 雷佳
     */
    public static function timer_func($time, $data, $parameter, $persistent=true)
    {
        if(empty($time) || empty($data)){
            throw new Exception("参数缺失");
        }

        $message = [
            'event'=>'func_timer',
            'push_time'=>$time,
            'bool' => $persistent,
            'parameter' => json_encode($parameter),
            'data' => json_encode($data),
        ];

        $timer_id = self::post_request(self::$url, $message);
        return $timer_id;
    }

    /**
     * Notes:销毁定时推送 （如果 timer_push方法 bool 设置为只执行一次则无需调用此方法,定时器会自动销毁）
     * Date: 2020/9/25
     * Time: 17:23
     * @param $data
     * @param string $time
     * @return \think\response\Json
     * @throws Exception
     * @author: 雷佳
     */
    public static function timer_close($timer_id, $data=[], $time='0.1')
    {
        if(empty($timer_id)){
            throw new Exception("参数缺失");
        }
        $message = [
            'timer_id'=>$timer_id,
            'event'=>'close_timer',
            'close_time'=>$time,
            'data' => json_encode($data),
        ];
        $code = self::post_request(self::$url, $message);
        return self::replace(StatusCode::getStatusCode('0'), $code);
    }

    /**
     * Notes: curl get 请求
     * Date: 2020/9/25
     * Time: 11:50
     * @param $url
     * @return bool|mixed|string
     * @author: 雷佳
     */
    private static function get_request($url)
    {
        $headerArray =array("Content-type:application/json;","Accept:application/json");
        // 1. 初始化
        $ch = curl_init();
        // 2. 设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        // 3. 执行并获取HTML文档内容
        $output = curl_exec($ch);
        // 4. 释放curl句柄
        curl_close($ch);
        $output = json_decode($output,true);
        return $output;
    }

    /**
     * Notes:curl post请求
     * Date: 2020/9/25
     * Time: 11:50
     * @param $url
     * @param $param
     * @return mixed
     * @author: 雷佳
     */
    private static function post_request($url, $param)
    {
        if (empty($url) || empty($param)) {
            throw new Exception("参数缺失");
        }
        $postUrl = $url;
        $curlPost = $param;
        if(is_array($curlPost)){
            $curlPost = http_build_query($curlPost);
        }
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }
    
    /**
     * Notes:通用化Api数据处理
     * User: Administrator
     * Date: 2020/9/14
     * Time: 11:17
     * @param $status
     * @param string $message
     * @param array $data
     * @param string $httpStatus
     * @return \think\response\Json
     * @author: 雷佳
     */
    private static function replace(int $status, $message = "error", $data = [], $httpStatus = '200')
    {
        $result = [
            "status" => $status,
            "message" => $message,
            "result" => $data
        ];
        if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] == 'xmlhttprequest'))
        {
            // 是ajax请求
            return json_encode($result, $httpStatus);
        } else {
            // 不是ajax请求
            return $result;
        }
    }
}