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

$config = new Config();
$deploy = $config->load("./datapush/deploy/socketio.php",'default_options_name');

$io = new SocketIO($deploy['SocketIO_port']);
// 当有客户端连接时
$io->on('connection', function($socket)use($io){
    // 定义chat message事件回调函数
    $socket->on('chat message', function($msg)use($io){
        // 触发所有客户端定义的chat message from server事件
        $io->emit('chat message from server', $msg);
    });
});

if(!empty($deploy['http'])){
    $i=1;
// 监听一个http端口，通过http协议访问这个端口可以向所有客户端推送数据(url类似http://ip:9191?msg=xxxx)
    $io->on('workerStart', function()use($io, $deploy,$i) {
        global $io,$i;
        $inner_http_worker = new Worker('http://'.$deploy['http']);
        $inner_http_worker->onMessage = function(TcpConnection $http_connection, Request $request)use($io){
            $get = $request->get();
            if(!isset($get)) {
                return $http_connection->send('fail, $_GET["msg"] not found');
            }
            $io->emit('chat message', $get);
            $http_connection->send('ok');
        };
        $inner_http_worker->listen();
        Timer::add($deploy['push_time'], function(){
            global $io,$i;
            $io->emit('new_msg', "推送".$i++);
        });
    });
}

if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}