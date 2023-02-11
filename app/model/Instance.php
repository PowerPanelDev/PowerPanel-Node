<?php

namespace app\model;

use app\client\Panel;
use app\event\EventData;
use app\handler\FileSystemHandler;
use app\util\Config;

class Instance extends Container
{
    const STATUS_INSTALLING = 1;
    const STATUS_STARTING = 11;
    const STATUS_RUNNING = 21;
    const STATUS_STOPPING = 31;
    const STATUS_STOPPED = 41;

    public array $detail;

    public function getDetail()
    {
        if (!isset($this->detail))
            $this->detail = (new Panel())->post('/api/node/ins/detail', [
                'attributes' => [
                    'uuid' => $this->uuid
                ]
            ])['attributes'];
        return $this->detail;
    }

    public function getInstanceStatus()
    {
        return Table::Get(Table::INSTANCE_STATS)->get($this->uuid)['status'] ?? self::STATUS_STOPPED;
    }

    public function setInstanceStatus(int $status, bool $report = true)
    {
        Table::Get(Table::INSTANCE_STATS)->update($this->uuid, ['status' => $status]);

        if ($report)
            // 上报实例状态
            self::ReportStats([$this->uuid => $this->getInstanceStats()]);

        return $this;
    }

    public function getBasePath()
    {
        return Config::Get()['storage_path']['instance_data'] . '/' . $this->uuid;
    }

    public function power(string $power)
    {
        $eventData = new EventData('instance.power', $this->uuid, $power);
        $eventData->call();
        \app\client\Event\Workerman::Publish($eventData);
    }

    public function getInstanceStats()
    {
        return Table::Get(Table::INSTANCE_STATS)->get($this->uuid);
    }

    public function getFileSystemHandler(): FileSystemHandler
    {
        return new FileSystemHandler($this);
    }

    static public function GetInstanceList()
    {
        return (new Panel())
            ->get('/api/node/ins')['attributes']['list'];
    }

    static public function IsInstance(string $uuid)
    {
        return Table::Get(Table::INSTANCE)->exist($uuid);
    }

    /**
     * 上报容器统计数据
     *
     * @param array $stats
     * @return array 储存空间超限的实例 UUID 列表
     */
    static public function ReportStats(array $stats): array
    {
        return (new Panel())->put('/api/node/ins/stats', [
            'data' => $stats
        ])['data'];
    }
}
