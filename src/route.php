<?php

use kbitAdmin\middleware\AppAuthMiddleware;
use think\facade\Route;

$namespace = '\\KbitAddons\\payment\\api\\controller\\';
Route::group('addons_payment/api', function () use ($namespace) {
    Route::any('callback/sync', $namespace . 'Callback@sync');              # 支付后同步回调操作
    Route::any('callback/async', $namespace . 'Callback@async');              # 支付后异步回调操作
})->middleware([AppAuthMiddleware::class]);
