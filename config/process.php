<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use app\process\Event;
use app\process\Proxy;
use app\process\WebSocket;
use app\util\Config;
use Workerman\Worker;

return [
    // File update detection and automatic reload
    'monitor' => [
        'handler' => process\Monitor::class,
        'reloadable' => false,
        'constructor' => [
            // Monitor these directories
            'monitor_dir' => array_merge([
                app_path(),
                config_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/.env',
            ], glob(base_path() . '/plugin/*/app'), glob(base_path() . '/plugin/*/config'), glob(base_path() . '/plugin/*/api')),
            // Files with these suffixes will be monitored
            'monitor_extensions' => [
                'php', 'html', 'htm', 'env'
            ],
            'options' => [
                'enable_file_monitor' => !Worker::$daemonize && DIRECTORY_SEPARATOR === '/',
                'enable_memory_monitor' => DIRECTORY_SEPARATOR === '/',
            ]
        ]
    ],
    'event' => [
        'handler' => Event::class,
        'listen'  => 'websocket://127.0.0.1:' . Config::Get()['node_port']['event']
    ],
    'websocket' => [
        'handler' => WebSocket::class,
        'listen'  => 'websocket://127.0.0.1:' . Config::Get()['node_port']['websocket']
    ],
    'proxy' => [
        'handler' => Proxy::class,
        'listen'  => 'tcp://0.0.0.0:' . Config::Get()['node_port']['proxy']
    ]
];
