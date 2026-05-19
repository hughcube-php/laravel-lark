<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
 */

namespace HughCube\Laravel\Lark;

use HughCube\Laravel\Lark\Robot\Client;
use HughCube\Laravel\ServiceSupport\LazyFacade;

/**
 * Lark 门面（Facade）。
 *
 * @method static Client robot(string|null $name = null)
 *
 * @see \HughCube\Laravel\Lark\Manager
 * @see \HughCube\Laravel\Lark\ServiceProvider
 */
class Lark extends LazyFacade
{
    /**
     * 获取组件在容器中注册的名称。
     */
    public static function getFacadeAccessor(): string
    {
        return 'lark';
    }

    protected static function registerServiceProvider($app): void
    {
        $app->register(ServiceProvider::class);
    }
}
