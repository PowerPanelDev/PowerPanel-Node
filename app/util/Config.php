<?php

namespace app\util;

class Config
{
    static $config;

    static public function Get()
    {
        // 需要打包进 Phar 使用 __DIR__ 会出问题
        if (!isset(self::$config)) self::$config = json_decode(file_get_contents('config.json'), true);
        return self::$config;
    }
}
