## 介绍
bfw_socketio 是一个基于开源 异步PHP socket框架 Workerman, 进一步封装 开发者只需简单调用暴露接口即可进行socket数据传输


## 安装
请使用composer安装bfw_socketio。

脚本中引用vendor中的autoload.php实现bfw_socketio相关类的加载。例如
```php
require_once '/你的vendor路径/autoload.php';
```

### **配置文件**

bfw_socketio\src\deploy\socketio.php

```
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
```



## 服务端和客户端连接

**启动一个SocketIO服务端**
```php
进入bfw_socketio\src目录启动

cd bfw_socketio\src

php start.php start 用于调试模式

php start.php start -d 用于守护程序模式

```
**客户端**

```javascript
<script src='https://cdn.bootcss.com/socket.io/2.0.3/socket.io.js'>
    </script>
<script src='//cdn.bootcss.com/jquery/1.11.3/jquery.js'></script>
<script>
// 如果服务端不在本机，请把127.0.0.1改成服务端ip
    var socket = io('http://127.0.0.1:9120');

    socket.on('connect', function(){
        //登入事件
        socket.emit('login', uid, to);
    });

    // 启动定时器时触发事件
    socket.on('timer_msg', function(msg){
       console.log('收到消息：'+msg)
    });

    // 销毁定时器定时触发事件
    socket.on('close_timer_msg', function(msg){
        console.log('收到消息：'+msg)
    });

    //后端推送消息时
    socket.on('message', function(msg){
        console.log('收到消息：'+msg)
    });
    // 后端推送来在线数据时
    socket.on('update_online_count', function(online_stat){
        $('#online_box').html("当前<b>"+online_stat.online_count+"</b>人在线，共打开<b>"+online_stat.online_page+"</b>个页面");
    });
</script>
```

### push.php 核心接口

#### 1.publish

```php
array \DataPush\Push::publish(array $data, int $uid) 
```

### **参数**

data

服务端推送给客户端的数据。

uid

客户端id,用于指定客户端推送

### **返回值**

返回一个数组数据

array(3) {
  ["status"]=>
  int(0)
  ["message"]=>
  string(6) "成功"
  ["result"]=>
  array(0) {
  }
}

### **示例**

```php
require_once __DIR__ . '/vendor/autoload.php';
use DataPush\Push;

class Index{
    public function res()
    {
        $push = new Push();
        $res = $push::publish(['name'=>'leijia'],$uid);
    }
}
$res = new Index();
$res->res();
```

#### **2.group_push**

```php
array \DataPush\Push::group_push(array $data, int $to) 
```

### **参数**

data

服务端推送给客户端的数据。

to

客户端分组id,用于指定客户端组别推送

### **返回值**

返回一个数组数据

array(3) {
  ["status"]=>
  int(0)
  ["message"]=>
  string(6) "成功"
  ["result"]=>
  array(0) {
  }
}

### **示例**

```php
require_once __DIR__ . '/vendor/autoload.php';
use DataPush\Push;

class Index{
    public function res()
    {
        $push = new Push();
        $res = $push::group_push(['name'=>'leijia'],$to);
    }
}
$res = new Index();
$res->res();
```

#### 3.broadcast

数据推送给在线的所有客服端连接（广播）

```php
array \DataPush\Push::broadcast(array $data) 
```

### **参数**

data

服务端推送给客户端的数据。

### **返回值**

返回一个数组数据

array(3) {
  ["status"]=>
  int(0)
  ["message"]=>
  string(6) "成功"
  ["result"]=>
  array(0) {
  }
}

### **示例**

```
require_once __DIR__ . '/vendor/autoload.php';
use DataPush\Push;

class Index{
    public function res()
    {
        $push = new Push();
        $res = $push::broadcast(['name'=>'leijia']);
    }
}
$res = new Index();
$res->res();
```

#### 4.timer_push

定时器推送

```php
int \DataPush\Push::timer_push(int $time, array $data, bool $persistent) 
```

### **参数**

time

多长时间执行一次，单位秒，支持小数，可以精确到0.001，即精确到毫秒级别。

data

服务端推送给客户端的数据。

persistent 

是否是持久的，如果只想定时执行一次，则传递false（只执行一次的任务在执行完毕后会自动销毁，不必调用Push::timer_close()`）。默认是true，即一直定时执行。

### **返回值**

返回一个整数，代表计时器的timerid，可以通过调用`Push::timer_close($timerid)`销毁这个计时器。

### **示例**

```php
require_once __DIR__ . '/vendor/autoload.php';
use DataPush\Push;

class Index{
    public function res()
    {
        $push = new Push();
        $timerid = $push::timer_push(1,name'=>'leijia']);
    }
}
$res = new Index();
$res->res();
```

#### 5.timer_func

定时器执行类方法

```php
int \DataPush\Push::timer_func(int $time, array $data, array $parameter, bool $persistent) 
```



### **参数**

time

多长时间执行一次，单位秒，支持小数，可以精确到0.001，即精确到毫秒级别。

data

服务端推送给客户端的数据。

parameter

函数的参数，必须为数组。

persistent 

是否是持久的，如果只想定时执行一次，则传递false（只执行一次的任务在执行完毕后会自动销毁，不必调用Push::timer_close()`）。默认是true，即一直定时执行。

### **返回值**

返回一个整数，代表计时器的timerid，可以通过调用`Push::timer_close($timerid)`销毁这个计时器。

### **实例**

```php
require_once __DIR__ . '/vendor/autoload.php';
include __DIR__ .'/vendor/bfw/socketio/src/Cs.php';
use DataPush\Push;

class Index{
    public function res()
    {
        $push = new Push();
        $timerid = $push::timer_func(1,['Cs','save_log'],['log','555']);
    }
}
$res = new Index();
$res->res();
```

#### **6.timer_close**

销毁定时器执行类方法

```php
array \DataPush\Push::timer_close(int $timer_id, array $data=[], int $time='0.1') 
```



### **参数**

timer_id

创建定时器任务id

data

销毁前服务端推送给客户端的数据。（选填）

time（选填）

销毁定时器时间

### **返回值**

返回一个数组数据

array(3) {
  ["status"]=>
  int(0)
  ["message"]=>
  string(6) "false|true"
  ["result"]=>
  array(0) {
  }
}

### **实例**

```php
require_once __DIR__ . '/vendor/autoload.php';
include __DIR__ .'/vendor/bfw/socketio/src/Cs.php';
use DataPush\Push;

class Index{
    public function res()
    {
        $push = new Push();
        $res = $push::timer_close($timer_id);
    }
}
$res = new Index();
$res->res();
```

