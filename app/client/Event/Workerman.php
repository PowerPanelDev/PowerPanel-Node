<?php

namespace app\client\Event;

use app\event\EventData;
use app\util\Config;
use support\Log;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;

class Workerman
{
    static AsyncTcpConnection|null $client = null;

    static public function Init()
    {
        global $eventKey;
        $event = new AsyncTcpConnection('ws://127.0.0.1:' . Config::Get()['node_port']['event'] . '/?channel=Workerman&key=' . $eventKey);
        $event->onMessage = [self::class, 'onMessage'];
        $event->connect();
        self::$client = $event;
    }

    static public function Publish(EventData $eventData)
    {
        self::$client->send($eventData);
    }

    static public function onMessage(TcpConnection $conn, $data)
    {
        EventData::fromJson($data)->call();
    }
}
