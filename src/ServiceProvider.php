<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
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
     * Boot the provider.
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
     * Register the provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), Lark::getFacadeAccessor());

        $this->app->singleton(Lark::getFacadeAccessor(), function ($app) {
            return new Manager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Lark::getFacadeAccessor()];
    }
}
