<?php

namespace app\model;

use app\client\Docker;
use app\util\Config;

class Container
{
    public string $uuid;

    static public Docker $client;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function start()
    {
        self::GetDocker()->post('/containers/' . $this->uuid . '/start', []);
        return $this;
    }

    public function stop()
    {
        self::GetDocker()->post('/containers/' . $this->uuid . '/stop', []);
        return $this;
    }

    public function kill()
    {
        self::GetDocker()->post('/containers/' . $this->uuid . '/kill', []);
        return $this;
    }

    public function pause()
    {
        self::GetDocker()->post('/containers/' . $this->uuid . '/pause', []);
        return $this;
    }

    public function unpause()
    {
        self::GetDocker()->post('/containers/' . $this->uuid . '/unpause', []);
        return $this;
    }

    public function sendStdin($content)
    {
        
    }

    public function inspect()
    {
        return json_decode(self::GetDocker()->get('/containers/' . $this->uuid . '/json'), true);
    }

    public function getLog()
    {
        $log = self::GetDocker()->get('/containers/' . $this->uuid . '/logs?stdout=true&stderr=true&tail=500');
        if (strpos($log, 'No such container:') !== false)
            return false;
        else
            return $log;
    }

    /**
     * 获取容器运行状态
     *
     * @return 'created' | 'restarting' | 'running' | 'removing' | 'paused' | 'exited' | 'dead'
     */
    public function getContainerStatus()
    {
        return $this->inspect()['State']['Status'];
    }

    static public function Create(ContainerDetail $detail)
    {
        (new Docker())
            ->post('/containers/create?name=' . $detail->uuid, [
                'User' => 'root',   // TODO 更改 Docker 运行用户
                'Tty' => true,
                'AttachStdin' => true,
                'OpenStdin' => true,
                'Image' => $detail->image,
                'Env' => $detail->env,
                'WorkingDir' => $detail->workingDir,
                'Labels' => [
                    'Service' => 'PowerPanel'
                ],
                'HostConfig' => [
                    'AutoRemove' => true,
                    'Binds' => $detail->binds,
                    'Memory' => $detail->memory,
                    'MemorySwap' => $detail->memorySwap,
                    'CpuPeriod' => $detail->cpuPeriod,
                    'CpuQuota' => $detail->cpuQuota,
                    'Dns' => Config::Get()['docker']['dns'],
                    'PortBindings' => $detail->portBindings
                ]
            ] + ($detail->cmd ? ['Cmd' => $detail->cmd] : []));
        return new self($detail->uuid);
    }

    static public function GetDocker(): Docker
    {
        if (!isset(self::$client)) self::$client = new Docker();
        return self::$client;
    }

    static public function GetContainerList($filters = ['label' => ['Service=PowerPanel']])
    {
        // 默认只筛选面板创建的容器
        return json_decode(
            self::GetDocker()
                ->get('/containers/json?filters=' . json_encode($filters)),
            true
        );
    }
}
