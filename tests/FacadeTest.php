<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
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
