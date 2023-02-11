<?php

namespace app\model;

use app\model\Table\Instance;
use app\model\Table\Instance\Stats;
use app\model\Table\Model;
use app\model\Table\Token;

class Table
{
    const TOKEN = 'Token';
    const INSTANCE = 'Instance';
    const INSTANCE_STATS = 'Instance/Stats';

    static array $table;

    static public function Init()
    {
        self::$table = [
            self::TOKEN             => new Token(),
            self::INSTANCE          => new Instance(),
            self::INSTANCE_STATS    => new Stats()
        ];
    }

    static public function Get($table): Model
    {
        if (isset(self::$table[$table]))
            return self::$table[$table];
        else
            throw new \Exception('表 ' . $table . ' 不存在', 500);
    }
}
