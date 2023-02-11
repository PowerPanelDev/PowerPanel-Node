<?php

namespace app\model;

use app\model\Table;
use app\util\Config;
use app\util\Random;

class Token
{
    const TYPE_HTTP = 1;
    const TYPE_WS = 2;

    public string $token;
    public int $type;
    public array $permission;
    public array $data;
    public int $created_at;
    public int $expire_at;

    public function __construct(array $attributes, bool $exists = false)
    {
        $this->token = $attributes['token'] ?? Random::String(32);
        $this->type = $attributes['type'];
        $this->data = $attributes['data'];
        $this->permission = $attributes['permission'];
        $this->created_at = $attributes['created_at'];
        $this->expire_at = $attributes['expire_at'];

        // 写入内存表
        $table = Table::Get(Table::TOKEN);
        if (!$exists) $table->set($this->token, [
            'type'          => $this->type,
            'permission'    => json_encode($this->permission),
            'data'          => json_encode($this->data),
            'created_at'    => $this->created_at,
            'expire_at'     => $this->expire_at
        ]);

        // 现有行数超过一半 清理过期 Token
        if ($table->count() >= Config::Get()['max_token_count'] / 2)
            self::purge();
    }

    public function isPermit(string $permission)
    {
        return in_array($permission, $this->permission)
            || $this->permission[0] == 'all';
    }

    public function isExpired()
    {
        return time() > $this->expire_at;
    }

    static public function fromTable(string $token)
    {
        $table = Table::Get(Table::TOKEN);
        if (!$table->exist($token)) return false;
        $data = $table->get($token);
        return new self([
            'token'         => $token,
            'type'          => $data['type'],
            'permission'    => json_decode($data['permission'], true),
            'data'          => json_decode($data['data'], true),
            'created_at'    => $data['created_at'],
            'expire_at'     => $data['expire_at']
        ], true);
    }

    static public function purge()
    {
        $table = Table::Get(Table::TOKEN);
        foreach ($table as $token => $v) {
            if (time() > $v['expire_at'])
                $table->delete($token);
        }
    }
}
