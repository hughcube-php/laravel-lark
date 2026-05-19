<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace HughCube\Laravel\Lark;

use HughCube\Laravel\Lark\Robot\Client;
use HughCube\Laravel\ServiceSupport\LazyFacade;

/**
 * Class Lark.
 *
 * @method static Client robot(string|null $name = null)
 *
 * @see \HughCube\Laravel\Lark\Manager
 * @see \HughCube\Laravel\Lark\ServiceProvider
 */
class Lark extends LazyFacade
{
    /**
     * Get the registered name of the component.
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
