<?php

namespace app\middleware\panel;

use app\controller\WebSocket;
use app\model\Token;
use Workerman\Connection\TcpConnection;

class WebSocketAuth
{
    static public function process(TcpConnection $conn)
    {
        if (!isset($_GET['token']) or !$token = Token::fromTable($_GET['token']) or $token->isExpired()) {
            $conn->close(json_encode(['code' => 401, 'msg' => '密钥错误或已过期。']));
            return false;
        }
        if ($token->type != Token::TYPE_WS) {
            $conn->close(json_encode(['code' => 400, 'msg' => '此密钥不可用于 WebSocket 连接。']));
            return false;
        }
        $path = explode('?', $_SERVER['REQUEST_URI'])[0];
        $conn->token = $token;

        if (!isset(WebSocket::ROUTE[$path])) return $conn->close(json_encode(['code' => 404]));
        call_user_func(WebSocket::ROUTE[$path], $conn);

        return true;
    }
}
