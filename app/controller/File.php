<?php

namespace app\controller;

use app\model\Instance;
use League\Flysystem\PathTraversalDetected;
use support\Request;
use support\Response;
use Symfony\Component\Filesystem\Path;
use Webman\Http\UploadFile;

// TODO 处理文件系统权限
class File
{
    public function GetList(Request $request): Response
    {
        try {
            $instance = new Instance($request->post()['attributes']['uuid']);
            return json([
                'code' => 200,
                'data' => $instance->getFileSystemHandler()->list(base64_decode($request->post()['attributes']['path']))
            ]);
        } catch (PathTraversalDetected) {
            return json(['code' => 400, 'msg' => '路径不合法。']);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function Rename(Request $request)
    {
        try {
            $instance = new Instance($request->post()['attributes']['uuid']);

            $instance->getFileSystemHandler()->rename(
                base64_decode($request->post()['attributes']['from']),
                base64_decode($request->post()['attributes']['to'])
            );

            return json(['code' => 200]);
        } catch (PathTraversalDetected) {
            return json(['code' => 400, 'msg' => '路径不合法。']);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function Delete(Request $request)
    {
        try {
            $instance = new Instance($request->post()['attributes']['uuid']);
            $handler  = $instance->getFileSystemHandler();

            $base = $handler->normalizePath(base64_decode($request->post()['attributes']['base']));
            $handler->delete(
                array_map(fn (string $v) => $base . '/' . base64_decode($v), $request->post()['attributes']['targets'])
            );

            return json(['code' => 200]);
        } catch (PathTraversalDetected) {
            return json(['code' => 400, 'msg' => '路径不合法。']);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function Create(Request $request)
    {
        try {
            $instance = new Instance($request->post()['attributes']['uuid']);

            $handler = $instance->getFileSystemHandler();
            $handler->create(
                $request->post()['attributes']['type'],
                $handler->normalizePath(base64_decode($request->post()['attributes']['base'])) . '/' . base64_decode($request->post()['attributes']['name'])
            );

            return json(['code' => 200]);
        } catch (PathTraversalDetected) {
            return json(['code' => 400, 'msg' => '路径不合法。']);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function Read(Request $request)
    {
        try {
            $instance = new Instance($request->post()['attributes']['uuid']);

            return json([
                'code' => 200,
                'attributes' => [
                    'content' => $instance->getFileSystemHandler()->read(base64_decode($request->post()['attributes']['path']))
                ]
            ]);
        } catch (PathTraversalDetected) {
            return json(['code' => 400, 'msg' => '路径不合法。']);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function Save(Request $request)
    {
        try {
            $instance = new Instance($request->post()['attributes']['uuid']);

            $instance->getFileSystemHandler()->save(
                base64_decode($request->post()['attributes']['path']),
                base64_decode($request->post()['attributes']['content'])
            );

            return json([
                'code' => 200
            ]);
        } catch (PathTraversalDetected) {
            return json(['code' => 400, 'msg' => '路径不合法。']);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function GetPermission(Request $request)
    {
        try {
            $instance = new Instance($request->post()['attributes']['uuid']);

            return json([
                'code' => 200,
                'attributes' => [
                    'permission' => $instance->getFileSystemHandler()->getPermission(base64_decode($request->post()['attributes']['path']))
                ]
            ]);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function SetPermission(Request $request)
    {
        try {
            $instance = new Instance($request->post()['attributes']['uuid']);

            return json([
                'code' => 200,
                'attributes' => [
                    'permission' => $instance->getFileSystemHandler()->setPermission(
                        base64_decode($request->post()['attributes']['path']),
                        $request->post()['attributes']['permission']
                    )
                ]
            ]);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function Compress(Request $request)
    {
        try {
            $instance = new Instance($request->post()['attributes']['uuid']);

            $instance->getFileSystemHandler()->compress(
                base64_decode($request->post()['attributes']['base']),
                array_map(fn (string $v) => base64_decode($v), $request->post()['attributes']['targets'])
            );

            return json(['code' => 200]);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function Decompress(Request $request)
    {
        try {
            $instance = new Instance($request->post()['attributes']['uuid']);

            $instance->getFileSystemHandler()->decompress(base64_decode($request->post()['attributes']['path']));

            return json(['code' => 200]);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function Upload(Request $request)
    {
        try {
            $token = $request->token;
            if (!$token->isPermit('file.upload'))
                throw new \Exception('此密钥不可用于文件上传。', 401);

            $instance = new Instance($token->data['instance']);
            $handler = $instance->getFileSystemHandler();
            $symfony = $handler->getSymfony();
            $base = Path::canonicalize($instance->getBasePath() . $handler->normalizePath(base64_decode($token->data['base'])));
            // 确保基路径在容器目录下
            if ($handler->isTraversal($base))
                throw new \Exception('路径不合法。', 400);

            try {
                if ($request->get('slice') == 1) {
                    // 切片上传模式
                    /** @var UploadFile $file */
                    foreach ($request->file() as $file) {
                        $target = $base . '/' . $file->getUploadName();
                        // 处理不一定存在的问题
                        if ($handler->isTraversal($target, $base))
                            throw new \Exception('路径不合法。', 400);

                        if ($symfony->exists($target)) {
                            // 文件已存在
                            if ($request->get('first') == 1) {
                                // 切片为首个切片 即文件为遗留文件
                                $symfony->remove($target);
                                $file->move($base);
                            } else {
                                file_put_contents(
                                    $target,
                                    file_get_contents($file->getRealPath()),
                                    FILE_APPEND
                                );
                            }
                        } else {
                            // 文件不存在
                            $file->move($target);
                        }
                    }
                } else {
                    // 小文件上传模式
                    /** @var UploadFile $file */
                    foreach ($request->file() as $file) {
                        $target = $base . '/' . $file->getUploadName();
                        // 处理不一定存在的问题
                        if ($handler->isTraversal($target, $base))
                            throw new \Exception('路径不合法。', 400);

                        $file->move($target);
                    }
                }
            } catch (\Throwable $th) {
                // 若为空文件可能会抛出异常
            }

            return json(['code' => 200]);
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }

    public function Download(Request $request)
    {
        try {
            $token = $request->token;
            if (!$token->isPermit('file.download'))
                throw new \Exception('此密钥不可用于文件下载。', 401);

            $instance = new Instance($token->data['instance']);
            $handler = $instance->getFileSystemHandler();
            $path = Path::canonicalize($instance->getBasePath() . $handler->normalizePath(base64_decode($token->data['path'])));
            // 确保基路径在容器目录下
            if ($handler->isTraversal($path))
                throw new \Exception('路径不合法。', 400);
            if (!is_file($path))
                throw new \Exception('文件不存在。', 404);

            $explode = explode('/', $path);
            return response()->download($path, end($explode));
        } catch (\Throwable $th) {
            return json(['code' => $th->getCode(), 'msg' => $th->getMessage()])->withStatus($th->getCode() ?: 500);
        }
    }
}
