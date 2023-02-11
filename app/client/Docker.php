<?php

namespace app\client;

use app\util\Config;
use Swoole\Coroutine\Http\Client;

class Docker
{
    public string $socket;

    public function __construct(string $socket = NULL)
    {
        $this->socket = $socket ?: Config::Get()['docker']['socket'];
    }

    public function getClient()
    {
        $parse = parse_url($this->socket);
        return ($parse && $parse['scheme'] == 'tcp')
            ? new Client($parse['host'], $parse['port'])
            : new Client($this->socket);
    }

    public function get(string $url)
    {
        $client = $this->getClient();
        $client->setHeaders(['Host' => 'localhost']);
        $client->get($url);
        $client->close();
        return $client->body;
    }

    public function post(string $url, array $data)
    {
        $client = $this->getClient();
        $client->setHeaders([
            'Host' => 'localhost',
            'Content-type' => 'application/json'
        ]);
        $client->post($url, json_encode($data));
        $client->close();
        return $client->body;
    }
}
