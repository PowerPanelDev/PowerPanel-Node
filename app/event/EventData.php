<?php

namespace app\event;

use support\Log;

class EventData
{
    public $params = [];

    public function __construct(
        public string $event,
        public string $sub,
        ...$params
    ) {
        $this->params = $params ?? [];
    }

    public function __toString()
    {
        return json_encode([
            'event' => $this->event,
            'sub' => $this->sub,
            'data' => $this->params
        ]);
    }

    static public function fromJson(string $packet)
    {
        $packet = json_decode($packet, true);
        return new self($packet['event'], $packet['sub'], ...$packet['data']);
    }

    public function call()
    {
        Event::Get($this->event, $this->sub)->call(...$this->params);
    }
}
