<?php

namespace app\event;

class Handler
{
    public int $id;
    public event $event;
    public $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function call(string $sub, ...$params)
    {
        call_user_func($this->callback, $this, $sub, ...$params);
    }

    public function remove()
    {
        $this->event->removeHandler($this);
    }
}
