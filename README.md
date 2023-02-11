### PowerPnael-Node

> 本项目使用 Workerman、Webman、Swoole。

### 启动

#### 开发模式

```shell
./swoole-cli webman start
```

#### 生产环境

```shell
./swoole-cli webman start -d
```

### 进程列表

#### Proxy

负责将 Tcp 流量分流至 WebSocket / Webman 服务器，实现 HTTP / WS 端口二合一。

#### WebSocket

负责处理客户端的 WebSocket 类连接。

#### Webman

负责处理面板、客户端的 HTTP 类请求。此进程不进行事件监听。

#### Listener

负责容器状态、容器启停、统计数据获取及容器标准 IO 的处理。此进程监听标准 IO 类事件。

#### Event

内部事件总线服务器进程，负责进程之间的事件通信。

其中，Listener 进程为纯 Swoole 实现，由 `app/bootstrap.php` 单独启动，可直接使用协程相关方法，其他进程需要使用 `run` 函数创建协程容器后才可使用协程，协程容器内使用阻塞方法仍然会导致阻塞。
