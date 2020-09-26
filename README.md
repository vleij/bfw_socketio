## 介绍
bfw_socketio 是一个基于开源 异步PHP socket框架 Workerman, 进一步封装 开发者只需简单调用暴露接口即可进行socket数据传输


## 安装
请使用composer安装bfw_socketio。

脚本中引用vendor中的autoload.php实现bfw_socketio相关类的加载。例如
```php
require_once '/你的vendor路径/autoload.php';
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
<script src='https://cdn.bootcss.com/socket.io/2.0.3/socket.io.js'></script>
<script>
// 如果服务端不在本机，请把127.0.0.1改成服务端ip
var socket = io('http://127.0.0.1:9191');
// 当连接服务端成功时触发connect默认事件
socket.on('connect', function(){
    console.log('connect success');
});
</script>
```
