<?php

namespace app\util;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class SwooleLogger
{
    static public $logger;

    static public function Init()
    {
        self::$logger = new Logger('default');
        foreach ((include __DIR__ . '/../../config/log.php')['default']['handlers'] as $handler) {
            self::$logger->pushHandler(
                (new $handler['class'](...$handler['constructor']))
                    ->setFormatter(
                        new $handler['formatter']['class'](...$handler['formatter']['constructor'])
                    )
            );
        }
    }

    static public function info($message)
    {
        self::$logger->info($message);
    }

    static public function debug($message)
    {
        self::$logger->debug($message);
    }

    static public function warning($message)
    {
        self::$logger->warning($message);
    }
}
