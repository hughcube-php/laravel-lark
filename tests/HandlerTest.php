<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
 */

namespace HughCube\Laravel\Lark\Tests;

use HughCube\Laravel\Lark\Log\Handler;

class HandlerTest extends TestCase
{
    /**
     * 一个暴露 protected 成员的匿名子类。
     */
    private function handler(): Handler
    {
        return new class ('default', true, 300, true, true) extends Handler {
            public function callTruncate(string $message): string
            {
                return $this->truncate($message);
            }

            /**
             * @param array<mixed> $record
             */
            public function callCard(array $record): object
            {
                return $this->cardMessage($record, (string) ($record['formatted'] ?? ''));
            }

            public function callText(string $formatted): object
            {
                return $this->textMessage($formatted);
            }

            public function callColor(int $level): string
            {
                return $this->color($level);
            }
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function record(string $formatted, int $level = 400, string $name = 'ERROR'): array
    {
        return [
            'formatted'  => $formatted,
            'level'      => $level,
            'level_name' => $name,
            'channel'    => 'testing',
        ];
    }

    public function testShortMessageIsUntouched(): void
    {
        $message = '短消息 hello';

        $this->assertSame($message, $this->handler()->callTruncate($message));
    }

    public function testLongChineseIsTruncatedWithoutMojibake(): void
    {
        // ~150 KB 的中文，每个字在 UTF-8 下占 3 字节。
        $message = str_repeat('飞书机器人告警', 8000);

        $result = $this->handler()->callTruncate($message);

        $this->assertLessThanOrEqual(Handler::MAX_BYTES, strlen($result));
        $this->assertTrue(mb_check_encoding($result, 'UTF-8'));
        $this->assertStringEndsWith(Handler::TRUNCATED_SUFFIX, $result);

        $body = substr($result, 0, -strlen(Handler::TRUNCATED_SUFFIX));
        $this->assertTrue(mb_check_encoding($body, 'UTF-8'));
        $this->assertStringNotContainsString("\u{FFFD}", $body);
        // mb_strcut 保留完整字符：字节长度是 3 的倍数。
        $this->assertSame(0, strlen($body) % 3);
    }

    public function testInvalidBytesAreSanitised(): void
    {
        $message = "ok \xB0\xA1 tail";

        $result = $this->handler()->callTruncate($message);

        $this->assertTrue(mb_check_encoding($result, 'UTF-8'));
    }

    public function testCardMessageIsLevelColoredPlainText(): void
    {
        $payload = $this->handler()
            ->callCard($this->record('something failed 出错了', 400, 'ERROR'))
            ->getMessage();

        $this->assertSame('interactive', $payload['msg_type']);
        $this->assertSame('red', $payload['card']['header']['template']);
        $this->assertSame('[ERROR] testing', $payload['card']['header']['title']['content']);
        // plain_text —— 日志内容绝不会被当成 markdown 解析。
        $this->assertSame('plain_text', $payload['card']['elements'][0]['text']['tag']);
        $this->assertSame(
            'something failed 出错了',
            $payload['card']['elements'][0]['text']['content']
        );
    }

    public function testTextMessageIsAlwaysText(): void
    {
        $payload = $this->handler()->callText('boom')->getMessage();

        $this->assertSame('text', $payload['msg_type']);
        $this->assertSame('boom', $payload['content']['text']);
    }

    public function testColorMapping(): void
    {
        $h = $this->handler();

        $this->assertSame('red', $h->callColor(600));    // EMERGENCY
        $this->assertSame('red', $h->callColor(400));     // ERROR
        $this->assertSame('orange', $h->callColor(300));  // WARNING
        $this->assertSame('yellow', $h->callColor(250));  // NOTICE
        $this->assertSame('blue', $h->callColor(200));    // INFO
        $this->assertSame('grey', $h->callColor(100));    // DEBUG
    }

    public function testLongCardBodyIsTruncatedSafely(): void
    {
        $payload = $this->handler()
            ->callCard($this->record(str_repeat('日志', 100000), 300, 'WARNING'))
            ->getMessage();

        $content = $payload['card']['elements'][0]['text']['content'];

        $this->assertLessThanOrEqual(Handler::MAX_BYTES, strlen($content));
        $this->assertTrue(mb_check_encoding($content, 'UTF-8'));
        $this->assertSame('orange', $payload['card']['header']['template']);
    }
}
