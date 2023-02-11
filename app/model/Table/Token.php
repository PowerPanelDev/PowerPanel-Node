<?php

namespace app\model\Table;

use app\util\Config;

class Token extends Model
{
    const TYPE_HTTP = 1;
    const TYPE_WS = 2;

    public function __construct()
    {
        parent::__construct(Config::Get()['max_token_count']);
        $this->column('type', parent::TYPE_INT);
        $this->column('data', parent::TYPE_STRING, 512);
        $this->column('permission', parent::TYPE_STRING, 512);
        $this->column('created_at', parent::TYPE_INT);          // BIG_INT
        $this->column('expire_at', parent::TYPE_INT);           // BIG_INT
        $this->create();
    }
}
