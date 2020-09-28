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
    //限制连接域名 当我们想指定特定域名的页面才能连接，可以用来设置域名白名单。(多域名用逗号分隔)
    "origins"=>[''],
    "https" => [
        //证书的绝对路径 .pem
        'local_cert' => '',
        //密匙绝对的路径 .key
        'local_pk' => '',
        //域名验证
        'verify_peer' => false,
    ],
    //客户端连接url
    'client_link'=>'127.0.0.1:9191',

];