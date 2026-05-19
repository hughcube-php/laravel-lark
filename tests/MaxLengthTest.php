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
use HughCube\Laravel\Lark\Robot\Messages\Text;

/**
 * 实测飞书自定义机器人能接受的最大文本内容。
 *
 * 飞书文档称内容上限约 30 KB；本用例对真实 webhook 做二分查找定位
 * 真实边界并打印出来，同时通过探测间隔休眠把请求控制在频控之内
 * （<= 5 次/秒，<= 100 次/分）。
 *
 * @group live
 */
class MaxLengthTest extends TestCase
{
    /**
     * 每次探测之间的停顿，用于遵守自定义机器人的频控。
     */
    private const PROBE_INTERVAL_US = 900_000;

    private function sendTextOfSize(int $bytes): bool
    {
        $payload = str_repeat('a', $bytes);

        try {
            $results = Lark::robot()->send(new Text($payload));
            $code = $results['code'] ?? $results['StatusCode'] ?? -1;

            return 0 === (int) $code;
        } catch (\Throwable $e) {
            /*
             * 区分“内容过长”与“瞬时频控”。命中频控信号时退避并重试
             * 一次，保证边界查找的准确性。
             */
            if ($this->looksLikeRateLimit($e->getMessage())) {
                sleep(5);

                try {
                    $results = Lark::robot()->send(new Text($payload));
                    $code = $results['code'] ?? $results['StatusCode'] ?? -1;

                    return 0 === (int) $code;
                } catch (\Throwable $e2) {
                    return false;
                }
            }

            return false;
        }
    }

    private function looksLikeRateLimit(string $message): bool
    {
        $message = strtolower($message);

        foreach (['frequency', 'rate limit', 'too many', '9499', 'limit'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function testProbeMaxTextLength(): void
    {
        $this->skipWithoutWebhook();

        // 极小的消息必须永远成功，否则就是配置坏了。
        $this->assertTrue(
            $this->sendTextOfSize(16),
            'A 16-byte text message should always be accepted.'
        );
        usleep(self::PROBE_INTERVAL_US);

        $low = 16;            // 已知可通过
        $high = 200_000;      // 假定过大

        // 确认上界确实会被拒绝；必要时继续扩大。
        $guard = 0;
        while ($this->sendTextOfSize($high) && $guard < 4) {
            $low = $high;
            $high *= 2;
            ++$guard;
            usleep(self::PROBE_INTERVAL_US);
        }

        // 二分查找边界，精度到 ~256 字节。
        while ($high - $low > 256) {
            $mid = intdiv($low + $high, 2);

            if ($this->sendTextOfSize($mid)) {
                $low = $mid;
            } else {
                $high = $mid;
            }

            usleep(self::PROBE_INTERVAL_US);
        }

        fwrite(STDERR, sprintf(
            "\n[laravel-lark] Feishu custom robot max text content: ~%d bytes (last accepted=%d, first rejected=%d)\n",
            $low,
            $low,
            $high
        ));

        // 合理性检查：真实上限远大于 1 KB 且小于 1 MB。
        $this->assertGreaterThan(1024, $low);
        $this->assertLessThan(1_048_576, $low);
    }
}
