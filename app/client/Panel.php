<?php

namespace app\client;

use app\util\Config;

use function Swoole\Coroutine\Http\get;
use function Swoole\Coroutine\Http\post;
use function Swoole\Coroutine\Http\request;

class Panel
{
    public string $endpoint;
    public string $token;

    public function __construct()
    {
        $config = Config::Get();
        $this->endpoint = $config['panel_endpoint'];
        $this->token = $config['panel_token'];
    }

    public function get(string $path)
    {
        $request = get($this->endpoint . $path, [], [
            'Content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token
        ]);
        $return = json_decode($request->getBody(), true);
        $this->checkResponse($return);
        return $return;
    }

    public function post(string $path, array $data)
    {
        $request = post($this->endpoint . $path, json_encode($data), [], [
            'Content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token
        ]);
        $return = json_decode($request->getBody(), true);
        $this->checkResponse($return);
        return $return;
    }

    public function put(string $path, array $data)
    {
        $request = request($this->endpoint . $path, 'PUT', json_encode($data), [], [
            'Content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token
        ]);
        $return = json_decode($request->getBody(), true);
        $this->checkResponse($return);
        return $return;
    }

    public function checkResponse($return)
    {
        if (!isset($return['code'])) throw new \Exception('面板无响应', 500);
        if ($return['code'] != 200) throw new \Exception('[Panel] ' . $return['msg'], $return['code']);
    }
}
