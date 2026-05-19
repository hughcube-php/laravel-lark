<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
 */

namespace HughCube\Laravel\Lark\Tests;

use HughCube\Laravel\Lark\Lark;
use HughCube\Laravel\Lark\ServiceProvider as PackageServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * @param Application $app
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PackageServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        /** @var Repository $appConfig */
        $appConfig = $app['config'];
        $appConfig->set(Lark::getFacadeAccessor(), require dirname(__DIR__) . '/config/config.php');
    }

    /**
     * 环境里是否配置了真实可用的 webhook。
     */
    protected function hasWebhook(): bool
    {
        return '' !== (string) (getenv('LARK_ROBOT_WEBHOOK') ?: getenv('LARK_ROBOT_TOKEN') ?: '');
    }

    protected function skipWithoutWebhook(): void
    {
        if (!$this->hasWebhook()) {
            $this->markTestSkipped('LARK_ROBOT_WEBHOOK / LARK_ROBOT_TOKEN is not configured.');
        }
    }
}
