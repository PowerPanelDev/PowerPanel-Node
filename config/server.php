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

use app\util\Config;

return [
    'listen' => 'http://127.0.0.1:' . Config::Get()['node_port']['webman'],
    'transport' => Config::Get()['tls']['enable'] ? 'ssl' : 'tcp',
    'context' => Config::Get()['tls']['enable'] ? [
        'ssl' => [
            'local_cert'  => Config::Get()['tls']['crt'],
            'local_pk'    => Config::Get()['tls']['key'],
            'verify_peer' => false
        ]
    ] : [],
    'name' => 'webman',
    'count' => 1,
    'user' => '',
    'group' => '',
    'reusePort' => false,
    'event_loop' => '',
    'stop_timeout' => 2,
    'pid_file' => runtime_path() . '/webman.pid',
    'status_file' => runtime_path() . '/webman.status',
    'stdout_file' => runtime_path() . '/logs/stdout.log',
    'log_file' => runtime_path() . '/logs/workerman.log',
    'max_package_size' => Config::Get()['max_package_size']
];
