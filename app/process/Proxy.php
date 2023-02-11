<?php

namespace app\process;

use app\util\Config;
use support\Log;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 代理服务器进程
 * 用于 Webman 和 WebSocket 分流
 */
class Proxy
{
    public function onWorkerStart()
    {
        Log::info('Proxy 进程已启动');
    }

    public function onMessage(TcpConnection $conn, $data)
    {
        // 判断是否有 WebSocket 特征
        $request = new Request($data);
        $isWebSocket = $request->header('Upgrade') == 'websocket';
        // 分流至对应服务器
        $port = Config::Get()['node_port'][$isWebSocket ? 'websocket' : 'webman'];

        // 为防止 onConnect 发送时机较客户端第 2 包更晚 此处先暂停接收
        $conn->pauseRecv();
        $remote = new AsyncTcpConnection('tcp://127.0.0.1:' . $port);
        $remote->onConnect = function ($remote) use ($conn, $data) {
            $remote->send($data);
            // 客户端第 1 包发送完毕 可以正常开始转发后续包
            $conn->resumeRecv();
        };
        $remote->pipe($conn);
        $conn->pipe($remote);
        $remote->connect();
        $conn->remote = $remote;

        Log::info(($isWebSocket ? ('[WS] ' . $conn->getRemoteAddress()) : ('[HTTP] ' . $conn->getRemoteAddress()) . ' ' . strtoupper($request->method() ?: 'GET')) . ' ' . $request->uri());
    }
}
