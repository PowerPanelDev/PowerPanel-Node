<?php

namespace app\event;

class Event
{
    static public $pool = [];

    static public function Get($event, $sub = 'general'): self
    {
        return self::$pool[$event][$sub] ?? new self($event, $sub);
    }

    public $handler = [];
    public $nextHandlerId = 0;

    /**
     * 请使用 Event::Get 方法
     *
     * @param string $event
     * @param string $sub
     */
    protected function __construct(
        public string $event,
        public string $sub
    ) {
        if (!isset(self::$pool[$event]))
            self::$pool[$event] = [];
        self::$pool[$event][$sub] = $this;
    }

    public function addHandler(Handler $handler)
    {
        $handler->id = $this->nextHandlerId++;
        $handler->event = $this;

        $this->handler[] = $handler;

        return $this;
    }

    public function removeHandler(Handler $handler)
    {
        unset($this->handler[$handler->id]);
        return $this;
    }

    public function hasHandler()
    {
        return count($this->handler) > 0;
    }

    public function call(...$params)
    {
        // 非总事件调用时需调用总事件
        if ($this->sub != 'general') $general = self::Get($this->event);
        if (!$this->hasHandler() && !(isset($general) && $general->hasHandler())) return;
        foreach ([...$this->handler, ...((isset($general) ? $general->handler : []))] as $handler) {
            $handler->call($this->sub, ...$params);
        }
        return $this;
    }
}
