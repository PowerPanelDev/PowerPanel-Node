<?php

namespace app\model\Table\Instance;

use app\model\Table\Model;

class Stats extends Model
{
    public function __construct()
    {
        parent::__construct(1024);
        $this->column('status', parent::TYPE_INT, 1);   // TINT_INT
        $this->column('disk_usage', parent::TYPE_INT);  // BIG_INT
        $this->create();
    }
}
