<?php

namespace app\model;

class ContainerDetail
{
    public Instance $instance;
    public array $detail;
    public string $uuid;
    public string $image;
    public int $disk;
    public array $env = [];
    public string $workingDir;
    public array $cmd;
    public array $binds = [];
    public int $memory;
    public int $memorySwap;
    public int $cpuPeriod;
    public int $cpuQuota;
    public array $portBindings = [];
    public array $appConfig = [];

    public function __construct(Instance $instance, array $detail)
    {
        $this->instance = $instance;
        $this->detail = $detail;

        $this->uuid = $instance->uuid;
        $this->image = $detail['image'];
        $this->disk = $detail['disk'];
        $this->workingDir = $detail['app']['working_path'];
        $this->cmd = explode(' ', $detail['app']['startup']);
        $this->memory = $detail['memory'] * 1024 * 1024;
        // TODO 处理 SWAP
        $this->memorySwap = ($detail['memory'] + 0) * 1024 * 1024;
        $this->cpuPeriod = 100000;
        $this->cpuQuota = $detail['cpu'] / 100 * $this->cpuPeriod;
        $this->appConfig = json_decode($detail['app']['config'], true);

        // TODO 处理自带 Env
        foreach ([] as $key => $value) {
            $this->env[] = $key . '=' . $value;
        }
        foreach (json_decode($detail['app']['data_path'], true) as $target => $source) {
            $this->binds[] = $instance->getBasePath() . $source . ':' . $target;
        }
        foreach ($detail['allocations'] as $allocation) {
            $this->portBindings[$allocation['port'] . '/tcp'] = [[
                'HostIp' => $allocation['ip'],
                'HostPort' => strval($allocation['port'])
            ]];
            $this->portBindings[$allocation['port'] . '/udp'] = [[
                'HostIp' => $allocation['ip'],
                'HostPort' => strval($allocation['port'])
            ]];
        }
    }
}
