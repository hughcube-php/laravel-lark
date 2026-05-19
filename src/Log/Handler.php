<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace HughCube\Laravel\Lark\Log;

use HughCube\Laravel\Lark\Lark;
use HughCube\Laravel\Lark\Robot\Messages\Text;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class Handler extends AbstractProcessingHandler
{
    /**
     * Keep the message far below the custom robot limit.
     *
     * Empirically the Feishu custom-bot text content is accepted up to
     * ~150 KiB (measured boundary: 153323 bytes ok / 153518 bytes rejected,
     * i.e. ~153600 = 150 * 1024), well above the 30 KB the docs imply.
     *
     * We deliberately trim *much* lower: a log line is sent as JSON, where
     * Guzzle escapes every Chinese character to "\uXXXX" (6 bytes each), so
     * the on-the-wire size of a CJK message is ~2x its UTF-8 length. Staying
     * at 15 KB keeps even an all-Chinese message comfortably within the limit
     * (huge fault tolerance), keeps chat notifications readable, and never
     * lets the signature/timestamp envelope tip the request over.
     */
    public const MAX_BYTES = 15000;

    /**
     * Appended (in place of the trimmed tail) when a record is truncated.
     */
    public const TRUNCATED_SUFFIX = '…[truncated]';

    protected null|string $robot;

    protected bool $enabled;

    /**
     * @param int|string|Logger::* $level
     */
    public function __construct(
        ?string $robot = null,
        bool $enabled = true,
        $level = Logger::WARNING,
        bool $bubble = true
    ) {
        $this->robot = $robot;
        $this->enabled = $enabled;

        parent::__construct($level, $bubble);
    }

    /**
     * @param array<mixed>|LogRecord $record
     */
    protected function write($record): void
    {
        if (!$this->enabled) {
            return;
        }

        /* Prevent loop errors: never let the log channel throw. */
        try {
            $formatted = (string) (is_array($record) ? $record['formatted'] : $record->formatted);

            Lark::robot($this->robot)->send(new Text($this->truncate($formatted)));
        } catch (\Throwable $exception) {
            // swallow on purpose
        }
    }

    /**
     * Trim a record to a safe size without ever producing mojibake.
     *
     * Steps:
     *  1. sanitise to valid UTF-8 (a stack trace can carry raw bytes), so the
     *     cut and the later json_encode never garble or fail;
     *  2. cut with mb_strcut + explicit UTF-8 — it stops on a character
     *     boundary, so a multibyte (e.g. Chinese) glyph is never split;
     *  3. append a marker so the reader knows the tail was dropped.
     */
    protected function truncate(string $message): string
    {
        $message = (string) mb_convert_encoding($message, 'UTF-8', 'UTF-8');

        if (strlen($message) <= static::MAX_BYTES) {
            return $message;
        }

        $budget = static::MAX_BYTES - strlen(static::TRUNCATED_SUFFIX);
        $budget = max($budget, 0);

        return mb_strcut($message, 0, $budget, 'UTF-8') . static::TRUNCATED_SUFFIX;
    }
}
