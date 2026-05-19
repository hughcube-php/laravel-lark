<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
 */

namespace HughCube\Laravel\Lark;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class ServiceProvider extends IlluminateServiceProvider implements DeferrableProvider
{
    protected function configPath(): string
    {
        return (string) realpath(dirname(__DIR__) . '/config/config.php');
    }

    /**
     * 启动服务提供者。
     */
    public function boot(): void
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes(
                [$this->configPath() => config_path(sprintf('%s.php', Lark::getFacadeAccessor()))],
                'lark-config'
            );
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure(Lark::getFacadeAccessor());
        }
    }

    /**
     * 注册服务提供者。
     */
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), Lark::getFacadeAccessor());

        $this->app->singleton(Lark::getFacadeAccessor(), function ($app) {
            return new Manager($app);
        });
    }

    /**
     * 返回本提供者提供的服务（用于延迟加载）。
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Lark::getFacadeAccessor()];
    }
}
