<?php

use app\model\Table;
use app\util\Config;
use Swoole\Lock;

error_reporting(E_ALL);
date_default_timezone_set(Config::Get()['timezone']);

$eventKey = md5(uniqid());                  // 鉴权密钥 用于进程间通信 WebSocket 连接时使用
$eventLock = new Lock(SWOOLE_MUTEX);        // Swoole 协程启动锁
$eventLock->lock();                         // 上锁 使 Listener 阻塞

// 初始化共享内存表
Table::Init();

// 启动 Listener 进程
require_once __DIR__ . '/process/Listener.php';
