<?php

namespace app\util;

/**
 * 随机文本工具类
 */

class Random
{
    static public function String($l)
    {
        $return = '';
        $sample = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $length = strlen($sample);
        for ($i = 0; $i < $l; $i++) {
            $return .= $sample[mt_rand(0, $length - 1)];
        }
        return $return;
    }
}
