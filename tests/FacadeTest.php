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
use HughCube\Laravel\Lark\Manager;
use HughCube\Laravel\Lark\Robot\Client as Robot;

class FacadeTest extends TestCase
{
    public function testIsFacade(): void
    {
        $this->assertInstanceOf(Manager::class, Lark::getFacadeRoot());
    }

    public function testRobot(): void
    {
        $this->assertInstanceOf(Robot::class, Lark::robot());
    }

    public function testRobotIsCached(): void
    {
        $this->assertSame(Lark::robot(), Lark::robot('default'));
    }
}
