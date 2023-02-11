<?php

namespace app\handler;

use app\adaptar\compress\Compress;
use app\adaptar\compress\PhpZip;
use app\model\Instance;
use app\util\Config;
use Symfony\Component\Filesystem\Path;

class FileSystemHandler
{
    public function __construct(
        public Instance $instance
    ) {
    }

    /**
     * 规范化绝对路径为类似于 __DIR__ 的格式
     *
     * /        => /
     * /path    => /path
     * /path/   => /path
     * path/    => /path
     * path     => /path
     * 
     * @param string $path
     * @return string
     */
    public function normalizePath(string $path)
    {
        return rtrim('/' . trim(str_replace('\\', '/', $path), '/'), '/');
    }

    public function getBasePath()
    {
        return $this->normalizePath(Config::Get()['storage_path']['instance_data']) . '/' . $this->instance->uuid;
    }

    public function isTraversal(string $path, string $base = NULL)
    {
        return !Path::isBasePath($base ?: $this->getBasePath(), $path);
    }

    public function getFlySystem()
    {
        return new \League\Flysystem\Filesystem(
            new \League\Flysystem\Local\LocalFilesystemAdapter(
                $this->getBasePath()
            )
        );
    }

    public function getSymfony()
    {
        return new \Symfony\Component\Filesystem\Filesystem();
    }

    public function list(string $path)
    {
        // 分离目录和文件 排序时目录优先
        $list = [[], []];
        /** @var FileAttributes|DirectoryAttributes $item */
        foreach ($this->getFlySystem()->listContents($this->normalizePath($path))->sortByPath() as $item) {
            $explode = explode('/', $item->path());
            $list[$item->isFile()][] = [
                'base64' => base64_encode(end($explode)),
                'is_file' => $item->isFile(),
                'size' => $item->isFile() ? $item->fileSize() : 4096,
                'modified_at' => $item->lastModified()
            ];
        }
        return [...$list[0], ...$list[1]];
    }

    public function rename(string $from, string $to)
    {
        $this->getFlySystem()->move($this->normalizePath($from), $this->normalizePath($to));
    }

    public function delete(array $targets)
    {
        $flysystem = $this->getFlySystem();
        foreach ($targets as $target) {
            $target = $this->normalizePath($target);
            if ($flysystem->fileExists($target)) {
                $flysystem->delete($target);
            } else if ($flysystem->directoryExists($target)) {
                $flysystem->deleteDirectory($target);
            }
        }
    }

    public function create(string $type, string $path)
    {
        $flysystem = $this->getFlySystem();
        if ($flysystem->has($path))
            throw new \Exception('文件已存在。', 400);

        if ($type == 'file') {
            $flysystem->write($path, '');
        } else {
            $flysystem->createDirectory($path);
        }
    }

    public function read(string $target)
    {
        $target = Path::canonicalize($this->getBasePath() . $this->normalizePath($target));
        if ($this->isTraversal($target))
            throw new \Exception('路径不合法。', 400);
        if (!$this->getSymfony()->exists($target))
            throw new \Exception('文件不存在。', 400);

        return file_get_contents($target);
    }

    public function save(string $target, string $content)
    {
        $symfony = $this->getSymfony();
        $target = Path::canonicalize($this->getBasePath() . $this->normalizePath($target));
        if ($this->isTraversal($target))
            throw new \Exception('路径不合法。', 400);
        if (!$symfony->exists($target))
            throw new \Exception('文件不存在。', 400);

        $symfony->dumpFile($target, $content);
    }

    public function getPermission(string $target)
    {
        $target = Path::canonicalize($this->getBasePath() . $this->normalizePath($target));
        if ($this->isTraversal($target))
            throw new \Exception('路径不合法。', 400);
        if (!$this->getSymfony()->exists($target))
            throw new \Exception('文件不存在。', 400);

        return substr(sprintf('%o', fileperms($target)), -4);
    }

    public function setPermission(string $target, $permission)
    {
        $target = Path::canonicalize($this->getBasePath() . $this->normalizePath($target));
        $symfony = $this->getSymfony();
        if ($this->isTraversal($target))
            throw new \Exception('路径不合法。', 400);
        if (!$symfony->exists($target))
            throw new \Exception('文件不存在', 400);

        $symfony->chmod($target, octdec(str_pad($permission, 4, 0, STR_PAD_LEFT)));
    }

    public function compress(string $base, array $_targets)
    {
        // 确保基目录在容器目录下
        $base = Path::canonicalize($this->getBasePath() . $this->normalizePath($base));
        if ($this->isTraversal($base))
            throw new \Exception('路径不合法。', 400);
        $targets = [];
        foreach ($_targets as $target) {
            // 确保文件/目录在基目录下
            $path = Path::canonicalize($base . $this->normalizePath($target));
            if ($this->isTraversal($path, $base))
                throw new \Exception('路径不合法。', 400);
            $targets[$target] = $path;
        }
        PhpZip::Compress($targets, $base . '/' . date('Y-m-d H-i-s') . '.zip');
    }

    public function decompress(string $target)
    {
        // 确保基目录在容器目录下
        $target = Path::canonicalize($this->getBasePath() . $this->normalizePath($target));
        $symfony = $this->getSymfony();
        if ($this->isTraversal($target))
            throw new \Exception('路径不合法。', 400);
        if (!$symfony->exists($target))
            throw new \Exception('文件不存在。', 400);
        Compress::Get($target)::Decompress($target, Path::getDirectory($target));
    }
}
