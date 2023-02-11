<?php

namespace app\middleware\panel;

use app\util\Config;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class PanelAuth implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        if (
            !$request->header('authorization') ||
            explode(' ', $request->header('authorization'))[1] !== Config::Get()['node_token']
        ) return json(['code' => 401, 'msg' => '密钥错误。']);
        return $handler($request);
    }
}
