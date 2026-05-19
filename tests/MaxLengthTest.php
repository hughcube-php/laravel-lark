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
use HughCube\Laravel\Lark\Robot\Messages\Text;

/**
 * Empirically probe the maximum text content the Feishu custom robot accepts.
 *
 * The Feishu documentation states the message content limit is around 30 KB.
 * This test binary-searches the real boundary against the live webhook and
 * prints it, while staying inside the bot frequency limit (<= 5 req/s,
 * <= 100 req/min) by sleeping between probes.
 *
 * @group live
 */
class MaxLengthTest extends TestCase
{
    /**
     * Pause between probes to respect the custom bot frequency control.
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
             * Distinguish "content too long" from transient frequency
             * control. On a rate-limit signal, back off and retry once so
             * the boundary search stays accurate.
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

        // A tiny message must always succeed; otherwise config is broken.
        $this->assertTrue(
            $this->sendTextOfSize(16),
            'A 16-byte text message should always be accepted.'
        );
        usleep(self::PROBE_INTERVAL_US);

        $low = 16;            // known good
        $high = 200_000;      // assumed too large

        // Make sure the upper bound is actually rejected; widen if needed.
        $guard = 0;
        while ($this->sendTextOfSize($high) && $guard < 4) {
            $low = $high;
            $high *= 2;
            ++$guard;
            usleep(self::PROBE_INTERVAL_US);
        }

        // Binary search the boundary to ~256 byte precision.
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

        // Sanity: the real limit is well above 1 KB and below 1 MB.
        $this->assertGreaterThan(1024, $low);
        $this->assertLessThan(1_048_576, $low);
    }
}
