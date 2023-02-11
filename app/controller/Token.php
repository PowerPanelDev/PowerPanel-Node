<?php

namespace app\controller;

use app\model\Token as PanelToken;
use support\Request;
use support\Response;

class Token
{
    public function Generate(Request $request): Response
    {
        $token = new PanelToken($request->post()['attributes']);
        return json([
            'code' => 200,
            'attributes' => [
                'token' => $token->token
            ]
        ]);
    }
}
