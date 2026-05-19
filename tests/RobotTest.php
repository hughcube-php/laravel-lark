<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace HughCube\Laravel\Lark\Tests;

use GuzzleHttp\Exception\GuzzleException;
use HughCube\Laravel\Lark\Lark;
use HughCube\Laravel\Lark\Robot\Client as Robot;
use HughCube\Laravel\Lark\Robot\Messages\Interactive;
use HughCube\Laravel\Lark\Robot\Messages\Post;
use HughCube\Laravel\Lark\Robot\Messages\Text;

/**
 * @group live
 */
class RobotTest extends TestCase
{
    protected function getRobot(): Robot
    {
        return Lark::robot();
    }

    protected function assertSuccess(array $results): void
    {
        $code = $results['code'] ?? $results['StatusCode'] ?? null;
        $this->assertNotNull($code, 'response should carry a status code');
        $this->assertSame(0, (int) $code, json_encode($results));
    }

    /**
     * @throws GuzzleException
     */
    public function testSendText(): void
    {
        $this->skipWithoutWebhook();

        $results = $this->getRobot()->send(
            new Text('[laravel-lark] text test @ ' . date('Y-m-d H:i:s'))
        );

        $this->assertSuccess($results);
        usleep(300_000);
    }

    /**
     * @throws GuzzleException
     */
    public function testSendPost(): void
    {
        $this->skipWithoutWebhook();

        $results = $this->getRobot()->send(
            Post::make('laravel-lark post test')
                ->line('first paragraph ' . date('H:i:s'))
                ->link('feishu', 'https://www.feishu.cn')
        );

        $this->assertSuccess($results);
        usleep(300_000);
    }

    /**
     * @throws GuzzleException
     */
    public function testSendInteractive(): void
    {
        $this->skipWithoutWebhook();

        $results = $this->getRobot()->send(
            Interactive::card('laravel-lark card test', [])
                ->markdown('**laravel-lark** interactive card ' . date('H:i:s'))
        );

        $this->assertSuccess($results);
        usleep(300_000);
    }
}
