<?php

namespace app\client\Event;

use app\event\EventData;
use app\util\Config;
use app\util\SwooleLogger as Log;
use Swoole\Coroutine\Http\Client;
use Swoole\WebSocket\CloseFrame;
use Swoole\WebSocket\Frame;

class Swoole
{
    static public Client $client;
    public $onEvent;

    static public function Init()
    {
        global $eventKey;
        $client = new Client('127.0.0.1', Config::Get()['node_port']['event']);
        $client->upgrade('/?channel=Swoole&key=' . $eventKey);
        self::$client = $client;
        self::Listen();
    }

    static public function Publish(EventData $eventData)
    {
        self::$client->push($eventData);
    }

    static public function Listen()
    {
        go(function () {
            while (1) {
                $data = self::$client->recv(1);
                if ($data === false && self::$client->errCode == 0) return self::reconnect();
                if ($data instanceof CloseFrame) return self::reconnect();
                if ($data instanceof Frame) {
                    EventData::fromJson($data->data)->call();
                }
            }
        });
    }

    /**
     * 断线重连
     * 掉线问题一般仅在 Workerman Reload 时出现
     *
     * @return void
     */
    static public function reconnect()
    {
        global $eventKey;
        Log::info('正在尝试重连至事件总线服务器');
        self::$client->upgrade('/?channel=Swoole&key=' . $eventKey);
        self::listen();
    }
}
