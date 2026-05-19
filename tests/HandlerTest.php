<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace HughCube\Laravel\Lark\Tests;

use HughCube\Laravel\Lark\Log\Handler;

class HandlerTest extends TestCase
{
    /**
     * An anonymous subclass that exposes the protected truncate().
     */
    private function handler(): Handler
    {
        return new class ('default', true) extends Handler {
            public function call(string $message): string
            {
                return $this->truncate($message);
            }
        };
    }

    public function testShortMessageIsUntouched(): void
    {
        $message = '短消息 hello';

        $this->assertSame($message, $this->handler()->call($message));
    }

    public function testLongChineseIsTruncatedWithoutMojibake(): void
    {
        // ~60 KB of Chinese, every char is 3 bytes in UTF-8.
        $message = str_repeat('飞书机器人告警', 3000);

        $result = $this->handler()->call($message);

        // Stays within budget.
        $this->assertLessThanOrEqual(Handler::MAX_BYTES, strlen($result));

        // Still valid UTF-8 -> no garbled tail.
        $this->assertTrue(mb_check_encoding($result, 'UTF-8'));

        // Marker present, and the body before it is a clean character cut
        // (no replacement / question-mark substitution from a split glyph).
        $this->assertStringEndsWith(Handler::TRUNCATED_SUFFIX, $result);

        $body = substr($result, 0, -strlen(Handler::TRUNCATED_SUFFIX));
        $this->assertTrue(mb_check_encoding($body, 'UTF-8'));
        $this->assertStringNotContainsString("\u{FFFD}", $body);
        // mb_strcut keeps whole characters: byte length is a multiple of 3.
        $this->assertSame(0, strlen($body) % 3);
    }

    public function testInvalidBytesAreSanitised(): void
    {
        // A raw invalid byte (as could appear inside a binary stack trace).
        $message = "ok \xB0\xA1 tail";

        $result = $this->handler()->call($message);

        $this->assertTrue(mb_check_encoding($result, 'UTF-8'));
    }
}
