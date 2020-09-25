<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/25
 * Time: 11:18
 */
namespace DataPush;
require_once __DIR__ . '../../vendor/autoload.php';
use think\Config;
use DataPush\base\StatusCode;
use DataPush\base\ErrCode;
class Push
{
    private $deploy = [];
    private $url = '127.0.0.1:9191';
    function __construct()
    {

        $config = new Config();
        $this->deploy = $config->load("./deploy/socketio.php",'default_options_name');
    }

    public function push($data)
    {
        $message = [
            'event'=>'push',
            'data' => json_encode($data),
        ];
        $res = self::post_request($this->url, $message);
        return $res;
    }

    public function timer_push($time, $data)
    {
        $message = [
            'event'=>'open_timer',
            'push_time'=>$time,
            'data' => json_encode($data),
        ];
        self::post_request($this->url, $message);
        $Statu = new StatusCode();
        die;
        $Err = new ErrCode();
        return $this->replace($Statu->getStatusCode('0'), $Err->getErrText('0'), [], 200);
    }
    public function timer_close($data, $time='0.5')
    {
        $message = [
            'event'=>'close_timer',
            'push_time'=>$time,
            'data' => json_encode($data),
        ];
        $res = self::post_request($this->url, $message);
        return $res;
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
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }

    protected function url_joint($data)
    {
        $url = $this->deploy['http'] + '';
        return $url;
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
    private function replace(int $status, $message = "error", $data = [], $httpStatus = '200')
    {
        $result = [
            "status" => $status,
            "message" => $message,
            "result" => $data
        ];
        return json($result, $httpStatus);
    }
}

$a = new Push();
//$res = $a->push(['name'=>'leijia']);
$res = $a->timer_push(1, ['name'=>'leijia']);
//$res = $a->timer_close(['name'=>'leijia']);
var_dump($res);