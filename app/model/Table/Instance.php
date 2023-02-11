<?php

namespace app\model\Table;

class Instance extends Model
{
    public function __construct()
    {
        parent::__construct(1024);
        $this->column('status', parent::TYPE_INT);  // BIG_INT
        $this->create();
    }
}
