<?php

namespace app\adaptar\compress;

use League\MimeTypeDetection\FinfoMimeTypeDetector;

abstract class Compress
{
    static $adaptar = [
        'application/zip' => PhpZip::class
    ];

    abstract static function Compress(array $targets, string $to);
    abstract static function Decompress(string $target, string $to);

    static public function Get(string $file)
    {
        $detector = new FinfoMimeTypeDetector();
        $mimetype = $detector->detectMimeTypeFromPath($file);
        if (isset(self::$adaptar[$mimetype])) {
            return self::$adaptar[$mimetype];
        } else {
            throw new \Exception('此压缩文件类型不受支持。');
        }
    }
}
