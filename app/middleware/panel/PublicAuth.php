<?php

namespace app\middleware\panel;

use app\model\Token;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class PublicAuth implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        if (!$request->get('token') or !$token = Token::fromTable($request->get('token')) or $token->isExpired()) {
            return json(['code' => 401, 'msg' => '密钥错误。'])->withStatus(400);
        }
        if ($token->type != Token::TYPE_HTTP) {
            return json(['code' => 400, 'msg' => '此密钥不可用于 HTTP 请求。'])->withStatus(400);
        }
        $request->token = $token;
        return $handler($request);
    }
}
