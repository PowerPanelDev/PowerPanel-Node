<?php

namespace app\controller;

use app\client\Event\Workerman as EventClient;
use app\event\Event;
use app\event\EventData;
use app\event\Handler;
use app\model\Instance;
use app\model\StatsRow;
use Workerman\Connection\TcpConnection;

use function Co\run;

class WebSocket
{
    const ROUTE = [
        '/ws/console' => [self::class, 'Console']
    ];

    static public function Console(TcpConnection $conn)
    {
        if (!isset($conn->token->data['instance']) || !isset($_GET['instance']) || $conn->token->data['instance'] != $_GET['instance'])
            $conn->close(json_encode(['code' => 401, 'msg' => '此 Token 无权访问此实例。']));

        $instance = new Instance($conn->token->data['instance']);

        if ($conn->token->isPermit('console.status.get')) {
            $conn->send(json_encode([
                'type' => 'status',
                'data' => $instance->getInstanceStatus()
            ]));
            Event::Get('instance.status', $instance->uuid)
                ->addHandler($conn->handler['status'] = new Handler(function (Handler $handler, string $sub, int $status, string $msg = NULL) use ($conn) {
                    $conn->send(json_encode([
                        'type' => 'status',
                        'data' => $status
                    ] + ($msg ? ['msg' => $msg] : [])));
                }));
        }
        if ($conn->token->isPermit('console.history')) {
            run(function () use ($conn, $instance) {
                if ($log = $instance->getLog()) $conn->send(json_encode([
                    'type' => 'history',
                    'data' => base64_encode($log)
                ]));
            });
        }
        if ($conn->token->isPermit('console.read')) {
            Event::Get('instance.stdio.stdout', $instance->uuid)
                ->addHandler($conn->handler['stdout'] = new Handler(function (Handler $handler, string $sub, string $content) use ($conn) {
                    $conn->send(json_encode([
                        'type' => 'stdout',
                        'data' => $content
                    ]));
                }));
        }
        if ($conn->token->isPermit('console.stats')) {
            $conn->send(json_encode([
                'type' => 'stats',
                'data' => [
                    'cpu' => 0,
                    'memory' => 0,
                    'disk' => $instance->getInstanceStats()['disk_usage'] ?? 0,
                    'io' => [
                        'net' => ['in' => 0, 'out' => 0],
                        'block' => ['in' => 0, 'out' => 0]
                    ]
                ]
            ]));
            Event::Get('instance.stats', $instance->uuid)
                ->addHandler($conn->handler['stats'] = new Handler(function (Handler $handler, string $sub, StatsRow $stats) use ($conn, $instance) {
                    $conn->send(json_encode([
                        'type' => 'stats',
                        'data' => [
                            'cpu' => $stats->cpu,
                            'memory' => $stats->memory,
                            'disk' => $instance->getInstanceStats()['disk_usage'] ?? 0,
                            'io' => [
                                'net' => ['in' => $stats->netIO[0], 'out' => $stats->netIO[1]],
                                'block' => ['in' => $stats->blockIO[0], 'out' => $stats->blockIO[1]]
                            ]
                        ]
                    ]));
                }));
        }
        $conn->onMessage = function (TcpConnection $conn, $data) use ($instance) {
            $data = json_decode($data, true);
            switch ($data['type']) {
                case 'power':
                    if ($conn->token->isPermit('console.status.set')) {
                        $instance->power($data['data']);
                    }
                    break;
                case 'stdin':
                    if ($conn->token->isPermit('console.write')) {
                        $eventData = new EventData('instance.stdio.stdin', $instance->uuid, $data['data']);
                        $eventData->call();
                        EventClient::Publish($eventData);
                    }
                    break;
            }
        };
    }
}
