<?php

namespace app\process;

use app\client\Docker;
use app\client\Event\Swoole as EventClient;
use app\controller\ListenerEvent;
use app\event\Event;
use app\event\EventData;
use app\event\Handler;
use app\model\Container;
use app\model\Instance;
use app\model\Table;
use app\util\Config;
use app\util\SwooleLogger as Log;
use Swoole\Process;
use Swoole\Timer;
use Swoole\WebSocket\CloseFrame;

class Listener extends Process
{
    public Docker $docker;
    public \app\client\Event\Swoole $event;
    public array $attaching = [];

    public function __construct()
    {
        $this->docker = new Docker();
        parent::__construct([$this, 'onWorkerStart'], false, 2, true /* 启用协程 */);
    }

    public function onWorkerStart()
    {
        global $eventLock;

        Log::Init();

        // Event 进程启动后此处得到锁 停止阻塞 继续运行
        $eventLock->lock();
        $eventLock->unlock();

        // 初始化事件总线连接
        \app\client\Event\Swoole::Init();

        Log::info('正在获取本地容器列表...');
        $containerList = Container::GetContainerList();
        Log::info('正在从面板获取实例列表...');
        $instanceList = Instance::GetInstanceList();

        foreach ($instanceList as $instance) {
            // TODO 写入更多信息
            Table::Get(Table::INSTANCE)->set($instance['uuid'], [
                'id' => $instance['id']
            ]);
            Table::Get(Table::INSTANCE_STATS)->set($instance['uuid'], [
                'status' => Instance::STATUS_STOPPED,
                'disk_usage' => 0
            ]);
        }

        // 处理标准 IO
        foreach ($containerList as $container) {
            $containerName = substr($container['Names'][0], 1);     // 获取 UUID
            if (Instance::IsInstance($containerName)) {
                $instance = new Instance($containerName);
                // 向内存表设置运行状态
                if ($container['State'] == 'running') {
                    $instance->setInstanceStatus(Instance::STATUS_RUNNING, false);
                    Log::info('容器 ' . $containerName . ' 正在运行中');

                    $this->attach($instance);
                }
            } else {
                Log::debug('非面板实例: ' . $containerName);
            }
        }

        $this->registerEvent();

        Timer::tick(Config::Get()['report_stats_interval'] * 1000, [$this, 'calcDiskUsage']);
        $this->calcDiskUsage();

        Log::info('Listener 进程已启动');
    }

    public function attach(Instance $instance, array $appConfig = [])
    {
        go(function () use ($instance, $appConfig) {
            $client = $this->docker->getClient();
            $client->setHeaders(['Host' => 'localhost']);
            $client->upgrade('/containers/' . $instance->uuid . '/attach/ws?stream=1');

            $this->attaching[$instance->uuid] = $client;

            while (1) {
                $frame = $client->recv();
                if ($frame instanceof CloseFrame) {
                    unset($this->attaching[$instance->uuid]);
                    Log::info('实例 ' . $instance->uuid . ' 监听连接已关闭');

                    // 检测实例运行状态 判断 WS 是否异常退出
                    if ($instance->getContainerStatus() != 'removing') {
                        Log::warning('实例 ' . $instance->uuid . ' 监听连接异常关闭 正在关机');
                        $instance->stop();
                    }

                    $instance->setInstanceStatus(Instance::STATUS_STOPPED);

                    $eventData = new EventData('instance.status', $instance->uuid, Instance::STATUS_STOPPED);
                    $eventData->call();
                    EventClient::Publish($eventData);

                    return;
                }
                if ($frame) {
                    if (isset($appConfig['done']) && $instance->getInstanceStatus() == Instance::STATUS_STARTING) {
                        if (strpos($frame->data, $appConfig['done']) !== false) {
                            $instance->setInstanceStatus(Instance::STATUS_RUNNING);

                            $eventData = new EventData('instance.status', $instance->uuid, Instance::STATUS_RUNNING);
                            $eventData->call();
                            EventClient::Publish($eventData);

                            Log::info('实例 ' . $instance->uuid . ' 已启动');
                        }
                    }

                    $eventData = new EventData('instance.stdio.stdout', $instance->uuid, base64_encode($frame->data));
                    $eventData->call();
                    EventClient::Publish($eventData);
                }
            }
        });
    }

    public function calcDiskUsage()
    {
        Log::debug('正在计算容器储存用量...');

        // 在扫描超大文件夹时使用 du 命令耗时较 PHP 递归迭代可减少 ~50%
        $dataPath = Config::Get()['storage_path']['instance_data'];
        exec('du -s ' . escapeshellarg($dataPath) . '/*', $return);
        foreach ($return as $row) {
            [$KBytes, $path] = explode("\t", $row);
            $uuid = str_replace($dataPath . '/', '', $path);

            if (!Instance::isInstance($uuid)) continue;
            Table::Get(Table::INSTANCE_STATS)->update($uuid, ['disk_usage' => $KBytes * 1024]);
        }

        Log::info('容器储存用量计算完成');
        Log::debug('正在上报容器统计数据...');

        // 处理储存空间超限容器
        $list = Instance::ReportStats(Table::Get(Table::INSTANCE_STATS)->toArray());
        foreach ($list as $uuid) {
            $eventData = new EventData('instance.disk.exceeded', $uuid);
            $eventData->call();
            EventClient::Publish($eventData);
        }
    }

    protected function registerEvent()
    {
        ListenerEvent::$listener = $this;
        /** @var callable $callback */
        foreach (ListenerEvent::$list as $event => $callback) {
            Event::Get($event)->addHandler(new Handler($callback));
        }
        return $this;
    }
}

(new Listener())->start();
