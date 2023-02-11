<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use app\controller\File;
use app\controller\Token;
use app\middleware\panel\PanelAuth;
use app\middleware\panel\PublicAuth;
use Webman\Route;

Route::group('/api/panel', function () {
    Route::post('/token', [Token::class, 'Generate']);
})->middleware([PanelAuth::class]);

Route::group('/api/panel/files', function () {
    Route::post('/list', [File::class, 'GetList']);
    Route::post('/rename', [File::class, 'Rename']);
    Route::post('/compress', [File::class, 'Compress']);
    Route::post('/decompress', [File::class, 'Decompress']);
    Route::post('/delete', [File::class, 'Delete']);
    Route::post('/permission', [File::class, 'GetPermission']);
    Route::put('/permission', [File::class, 'SetPermission']);
    Route::post('/create', [File::class, 'Create']);
    Route::post('/read', [File::class, 'Read']);
    Route::post('/save', [File::class, 'Save']);
})->middleware([PanelAuth::class]);

Route::group('/api/public', function () {
    Route::get('/files/download', [File::class, 'Download']);
    Route::post('/files/upload', [File::class, 'Upload']);
})->middleware([PublicAuth::class]);

Route::options('[{path:.+}]', function (){
    return response('');
});

Route::disableDefaultRoute();
