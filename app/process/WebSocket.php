<?php

namespace app\process;

use app\event\EventData;
use app\event\Handler;
use app\middleware\panel\WebSocketAuth;
use app\model\Instance;
use app\model\StatsRow;
use app\util\Config;
use stdClass;
use Workerman\Connection\TcpConnection;

class WebSocket
{
    protected stdClass $stats;

    public function onWorkerStart()
    {
        \app\client\Event\Workerman::Init();

        $this->listenStats();
    }

    public function onConnect(TcpConnection $conn)
    {
        $conn->onWebSocketConnect = [WebSocketAuth::class, 'process'];
    }

    public function onClose(TcpConnection $conn)
    {
        /** @var Handler $handler */
        foreach ($conn->handler as $handler) {
            $handler->remove();
        }
    }

    public function listenStats()
    {
        $this->stats = new stdClass;
        $this->stats->proc = proc_open(
            'docker -H ' . escapeshellarg(Config::Get()['docker']['socket']) . ' stats --no-trunc --format "{{ json . }}"',
            [['pipe', 'r'], ['pipe', 'w'], STDERR],
            $pipes
        );
        $this->stats->pipes = $pipes;
        $this->stats->terminal = new TcpConnection($pipes[1]);
        $this->stats->terminal->skip = false;
        $this->stats->terminal->onMessage = function (TcpConnection $terminal, $stdout) {
            if ($stdout[0] != '{') return;
            $terminal->skip = !$terminal->skip;
            if ($terminal->skip) return;

            $rows = explode(PHP_EOL, $stdout);
            foreach ($rows as $row) {
                if (!$row = StatsRow::fromRaw($row)) continue;
                if (!Instance::IsInstance($row->name)) continue;
                (new EventData('instance.stats', $row->name, $row))->call();
            }
        };
    }
}
