<?php

namespace app\model;

class StatsRow
{
    public bool $initialized = false;
    public string $name;
    public float $cpu;
    public int $memory;
    public array $netIO, $blockIO;
    public bool $status;

    static public function ByteParser($data)
    {
        // 统一单位和大小写
        $data = str_replace(['GIB', 'MIB', 'KIB'], ['GB', 'MB', 'KB'], strtoupper($data));
        if (strpos($data, 'GB') !== false) {
            // GB 作为单位
            $return = str_replace('GB', '', $data) * 1024 * 1024 * 1024;
        } elseif (strpos($data, 'MB') !== false) {
            // MB 作为单位
            $return = str_replace('MB', '', $data) * 1024 * 1024;
        } elseif (strpos($data, 'KB') !== false) {
            // KB 作为单位
            $return = str_replace('KB', '', $data) * 1024;
        } elseif (strpos($data, 'B') !== false) {
            // B 作为单位
            $return = str_replace('B', '', $data);
        } else throw new \Exception('未知类型 ' . $data);

        return round($return);
    }

    /**
     * 从 Docker Stats 的行构建对象
     *
     * @param String $json
     * @return StatsRow|false
     */
    static public function fromRaw(String $json)
    {
        if (!$json) return false;
        $data = json_decode($json, true);
        if (!$data) return false;

        $netIO = explode(' / ', $data['NetIO']);
        $blockIO = explode(' / ', $data['BlockIO']);

        $object = new self;
        $object->initialized = true;
        $object->name = $data['Name'];
        $object->cpu = rtrim($data['CPUPerc'], '%');
        $object->memory = self::ByteParser(explode(' / ', $data['MemUsage'])[0]);
        $object->netIO = [self::ByteParser($netIO[0]), self::ByteParser($netIO[1])];
        $object->blockIO = [self::ByteParser($blockIO[0]), self::ByteParser($blockIO[1])];
        $object->status = ($data['PIDs'] > 0);

        return $object;
    }
}
