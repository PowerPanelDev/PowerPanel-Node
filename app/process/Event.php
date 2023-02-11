<?php

namespace app\process;

use support\Log;
use Workerman\Connection\TcpConnection;

class Event
{
    public function onWorkerStart()
    {
        global $eventLock;
        // Event 进程启动完成 解锁 使 Listener 进程得到锁并开始运行
        $eventLock->unlock();

        Log::info('Event 进程已启动');
    }

    public function onConnect(TcpConnection $conn)
    {
        $conn->onWebSocketConnect = [$this, 'onWebSocketConnect'];
    }

    public function onWebSocketConnect(TcpConnection $conn)
    {
        global $eventKey;
        if (!isset($_GET['key']) || $_GET['key'] !== $eventKey) {
            $conn->close(json_encode(['code' => 401, 'msg' => 'EventKey 错误。']));
            Log::debug('[' . $conn->getRemoteAddress() . '] 密钥错误 已关闭连接');
            return;
        }
        Log::debug('[' . $_GET['channel'] . '] [' . $conn->getRemoteAddress() . '] 已连接到事件总线服务器');
        $conn->authorized = true;
    }

    /**
     * 转发信息
     *
     * @param TcpConnection $conn
     * @return void
     */
    public function onMessage(TcpConnection $conn, $data)
    {
        /** @var TcpConnection $target */
        foreach ($conn->worker->connections as $target) {
            if (!isset($target->authorized) || !$target->authorized || $target->id == $conn->id) continue;
            $target->send($data);
        }
    }
}
