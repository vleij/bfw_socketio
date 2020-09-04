<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/3
 * Time: 13:51
 */
return [
    //SocketIO 进程端口
    "SocketIO_port"=>"9120",
    //监听一个http端口，通过http协议访问这个端口可以向所有客户端推送数据(url类似http://ip:9191?msg=xxxx)
    "http"=>'0.0.0.0:9191',
    //1秒推送一次,支持毫米级推送0.1
    "push_time"=>'1',
];