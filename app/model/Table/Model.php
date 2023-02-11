<?php

namespace app\model\Table;

use Swoole\Table;

class Model extends Table
{
    public function toArray(): array
    {
        $array = [];
        foreach ($this as $key => $value)
            $array[$key] = $value;
        return $array;
    }

    public function pluck($field)
    {
        $array = [];
        foreach ($this as $key => $value)
            $array[$key] = $value[$field];
        return $array;
    }

    public function update(string $key, array $data)
    {
        $this->set($key, $data + $this->get($key));
    }
}
