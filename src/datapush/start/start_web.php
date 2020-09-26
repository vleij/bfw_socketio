<?php
/**
 * Created by PhpStorm.
 * User: leij
 * Date: 2020/9/2
 * Time: 9:31
 */
use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Protocols\Http\Request;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use think\Config;
use DataPush\base\ErrCode;

$config = new Config();
//配置文件
$deploy = $config->load("./datapush/deploy/socketio.php",'default_options_name');
// 全局数组保存uid在线数据
$uidConnectionMap = array();
// 全局数组保存to数据
$toConnectionMap = array();
// 记录最后一次广播的在线用户数
$last_online_count = 0;
// 记录最后一次广播的在线页面数
$last_online_page_count = 0;
//判断是否使用https
if(!empty(array_filter($deploy['https']))){
    // 传入ssl选项，包含证书的路径
    $context = array(
        'ssl' => array(
            'local_cert'  => $deploy['https']['local_cert'],
            'local_pk'    => $deploy['https']['local_pk'],
            'verify_peer' => $deploy['https']['verify_peer'],
        )
    );
    $io = new SocketIO($deploy['SocketIO_port'],$context);

}else{
    $io = new SocketIO($deploy['SocketIO_port']);
}
//判断是否设置域名限制
if(!empty(array_filter($deploy['origins']))){
    $origins = '';
    foreach ($deploy['origins'] as $key => $val){
        $origins .= $val." ";
    }
    $io->origins(rtrim($origins));
}

// 当有客户端连接时
$io->on('connection', function($socket)use($io){
    // 定义chat message事件回调函数
    $socket->on('chat message', function($msg)use($io){
        // 触发所有客户端定义的chat message from server事件
        $io->emit('chat message from server', $msg);
    });

    // 当客户端发来登录事件时触发
    $socket->on('login', function ($uid, $to)use($socket){
        global $uidConnectionMap, $last_online_count, $last_online_page_count, $toConnectionMap;
        // 已经登录过了

        if(isset($socket->uid)){
            return;
        }
        // 更新对应uid的在线数据
        $uid = (string)$uid;

        if(!isset($uidConnectionMap[$uid]))
        {
            $uidConnectionMap[$uid] = 0;
        }
        if(!isset($toConnectionMap[$to]))
        {
            $toConnectionMap[$to] = 0;
        }
        ++$toConnectionMap[$to];
        // 这个uid有++$uidConnectionMap[$uid]个socket连接
        ++$uidConnectionMap[$uid];

        // 将这个连接加入到uid分组，方便针对uid推送数据
        $socket->join($uid);
        $socket->join($to);
        $socket->uid = $uid;
        // 更新这个socket对应页面的在线数据
        //$socket->emit('update_online_count', "当前<b>{$last_online_count}</b>人在线，共打开<b>{$last_online_page_count}</b>个页面");
    });

    //监听客户端关闭操作（刷新或者网络断开）
    $socket->on('disconnect', function () use($socket) {
        if(!isset($socket->uid))
        {
            return;
        }

        global $uidConnectionMap, $sender_io;

        // 将uid的在线socket数减一
        if(--$uidConnectionMap[$socket->uid] <= 0)
        {
            unset($uidConnectionMap[$socket->uid]);
        }
//        $socket->disconnect();
    });
});
//判断是否监听一个http端口默认开启
if(!empty($deploy['http'])){
// 监听一个http端口，通过http协议访问这个端口可以向所有客户端推送数据(url类似http://ip:9191?msg=xxxx)
    $io->on('workerStart', function($socket)use($io, $deploy) {
        $inner_http_worker = new Worker('http://'.$deploy['http']);
        $inner_http_worker->onMessage = function(TcpConnection $http_connection, Request $request)use($io, $deploy, $socket){
            global $toConnectionMap;
            if($request->method() == 'GET'){
                $parameter = $request->get();
            }else{
                $parameter = $request->post();
            }
            if(!isset($parameter)) {
                return $http_connection->send('fail, '.$parameter.' not found');
            }
            $message = $parameter;
            if(empty($message)){
                return $http_connection->send('error');
            }

            //请求事件
            switch ($message['event']){
                //向当前客户端发送事件
                case 'push':
                    $uid = @$message['uid'];
                    $content= @$message['data'];
                    $io->to($uid)->emit('message', $content);
                    break;

                // 有指定uid则向uid所在socket组发送数据
                case 'group_push':
                    $content= @$message['data'];
                    $to = @$message['to'];
                    $io->to($to)->emit('message', $content);
                    // http接口返回，如果用户离线socket返回fail

                    if($to && !isset($toConnectionMap[$to])){
                        return $http_connection->send(ErrCode::getErrText(-2));
                    }else{
                        return $http_connection->send(ErrCode::getErrText(0));
                    }
                break;

                //向所有客户端发送事件
                case 'broadcast':
                    $content= @$message['data'];
                    $io->emit('message', $content);
                    break;
                //定时向所有客户端发送数据
                case 'open_timer';
                    $data = $message['data'];
                    $timer_id = Timer::add($message['push_time'], function() use ($data){
                        global $io;
                        $io->emit('new_msg', $data);
                    },[], @$message['bool']);
                    $_SESSION['timer_id'] = $timer_id;
                break;
                //销毁定时器
                case 'close_timer';
                    $timer_id = $_SESSION['timer_id'];
                    $data = $message['data'];
                    Timer::add(0.1, function($timer_id) use ($data)
                    {
                        global $io;
                        if(!empty($data)){
                            $io->emit('new_msg', $data);
                        }
                        Timer::del($timer_id);
                    }, array($timer_id), false);
                    break;
            }
            //请求成功回复
            $http_connection->send(ErrCode::getErrText(0));
        };
        //监听http端口
        $inner_http_worker->listen();

    });
}

if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}