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
use HughCube\Laravel\Lark\Robot\Messages\Interactive;
use HughCube\Laravel\Lark\Robot\Messages\Text;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class Handler extends AbstractProcessingHandler
{
    /**
     * Safe content ceiling.
     *
     * Empirically the Feishu custom-bot content is accepted up to ~150 KiB
     * for BOTH text and interactive cards (measured boundary: text 153323 ok
     * / 153518 rejected, card 152968 ok / 153241 rejected, i.e. ~153600 =
     * 150 * 1024). Past it the hook returns error 19036 "The message exceeds
     * the size limit of 30KB" — the cited "30KB" is loose; the real enforced
     * limit is ~150 KiB and it is the same for cards and text.
     *
     * Feishu chat auto-folds long content (a "show more" expander), so a long
     * card still reads well — there is no presentation reason to downgrade to
     * text. We cap at 120 KB: a wide margin below the measured boundary (room
     * for the card/JSON envelope, huge fault tolerance) while still delivering
     * very long stack traces in full.
     */
    public const MAX_BYTES = 120000;

    /**
     * Appended (in place of the trimmed tail) when a record is truncated.
     */
    public const TRUNCATED_SUFFIX = '…[truncated]';

    protected null|string $robot;

    protected bool $enabled;

    /**
     * Render records as a colored interactive card (by log level).
     *
     * On by default. The (UTF-8 safe, size-capped) record is sent as a card;
     * only if Feishu actually rejects the card does it fall back to a plain
     * Text message. Set to false to always send Text.
     */
    protected bool $card;

    /**
     * @param int|string|Logger::* $level
     */
    public function __construct(
        ?string $robot = null,
        bool $enabled = true,
        $level = Logger::WARNING,
        bool $bubble = true,
        bool $card = true
    ) {
        $this->robot = $robot;
        $this->enabled = $enabled;
        $this->card = $card;

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
            $robot = Lark::robot($this->robot);

            if ($this->card) {
                try {
                    $robot->send($this->cardMessage($record, $formatted));

                    return;
                } catch (\Throwable $cardException) {
                    // Card rejected (e.g. 19036): fall back to plain text.
                }
            }

            $robot->send($this->textMessage($formatted));
        } catch (\Throwable $exception) {
            // swallow on purpose
        }
    }

    /**
     * Build a level-colored interactive card. The body is a plain_text block,
     * so arbitrary log output is never re-parsed as markdown.
     *
     * @param array<mixed>|LogRecord $record
     */
    protected function cardMessage($record, string $formatted): Interactive
    {
        [$levelValue, $levelName, $channel] = $this->describe($record);

        $title = sprintf(
            '[%s] %s',
            '' !== $levelName ? strtoupper($levelName) : 'LOG',
            '' !== $channel ? $channel : 'app'
        );

        return Interactive::card($title, [], $this->color($levelValue))
            ->text($this->truncate($formatted));
    }

    protected function textMessage(string $formatted): Text
    {
        return new Text($this->truncate($formatted));
    }

    /**
     * Extract [level value, level name, channel] from a Monolog 2 array
     * record or a Monolog 3 LogRecord without hard-coupling to either.
     *
     * @param array<mixed>|LogRecord $record
     *
     * @return array{0:int,1:string,2:string}
     */
    protected function describe($record): array
    {
        $level = is_array($record) ? ($record['level'] ?? 0) : $record->level;

        if (is_object($level)) {
            // Monolog\Level enum (Monolog 3).
            $levelValue = (int) ($level->value ?? 0);
            $levelName = method_exists($level, 'getName')
                ? (string) $level->getName()
                : (string) ($level->name ?? '');
        } else {
            $levelValue = (int) $level;
            $levelName = is_array($record) ? (string) ($record['level_name'] ?? '') : '';
        }

        $channel = is_array($record)
            ? (string) ($record['channel'] ?? '')
            : (string) $record->channel;

        return [$levelValue, $levelName, $channel];
    }

    /**
     * Map a Monolog level value to a Feishu card header template color.
     */
    protected function color(int $levelValue): string
    {
        return match (true) {
            $levelValue >= 400 => 'red',     // ERROR / CRITICAL / ALERT / EMERGENCY
            300 === $levelValue => 'orange',  // WARNING
            250 === $levelValue => 'yellow',  // NOTICE
            200 === $levelValue => 'blue',    // INFO
            $levelValue > 0 && $levelValue <= 100 => 'grey', // DEBUG
            default => 'blue',
        };
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
    protected function truncate(string $message, ?int $maxBytes = null): string
    {
        $maxBytes ??= static::MAX_BYTES;

        $message = (string) mb_convert_encoding($message, 'UTF-8', 'UTF-8');

        if (strlen($message) <= $maxBytes) {
            return $message;
        }

        $budget = max($maxBytes - strlen(static::TRUNCATED_SUFFIX), 0);

        return mb_strcut($message, 0, $budget, 'UTF-8') . static::TRUNCATED_SUFFIX;
    }
}
