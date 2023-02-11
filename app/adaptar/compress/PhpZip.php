<?php

namespace app\adaptar\compress;

use PhpZip\ZipFile;

class PhpZip extends Compress
{
    static public function Compress(array $targets, string $to)
    {
        $file = new ZipFile();
        foreach ($targets as $name => $path) {
            if (is_file($path))
                $file->addFile($path, $name);
            else
                $file->addDir($path, $name);
        }
        $file->saveAsFile($to)
            ->close();
    }

    static public function Decompress(string $target, string $to)
    {
        $file = new ZipFile();
        $file->openFile($target)
            ->extractTo($to)
            ->close();
    }
}
