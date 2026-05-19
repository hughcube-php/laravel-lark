<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
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
     * 安全的内容上限。
     *
     * 实测飞书自定义机器人的内容上限对【文本】和【交互卡片】是一致的，
     * 约 ~150 KiB（实测边界：文本 153323 通过 / 153518 拒绝，卡片
     * 152968 通过 / 153241 拒绝，即 ~153600 = 150 * 1024）。超限后
     * 接口返回 error 19036 "The message exceeds the size limit of 30KB"
     * ——文案里的 "30KB" 是虚的，真实限制约 ~150 KiB，且卡片与文本相同。
     *
     * 飞书会话会自动折叠超长内容（"展开更多"），所以长卡片依然好读，
     * 没有理由因为长而降级成文本。这里上限取 120 KB：在实测边界之下
     * 留足余量（容纳卡片/JSON 外壳，容错充足），同时仍能完整投递很长
     * 的堆栈信息。
     */
    public const MAX_BYTES = 120000;

    /**
     * 内容被截断时，替换掉尾部并追加的标记。
     */
    public const TRUNCATED_SUFFIX = '…[truncated]';

    protected null|string $robot;

    protected bool $enabled;

    /**
     * 是否将日志渲染为按级别上色的交互卡片。
     *
     * 默认开启。记录经 UTF-8 安全截断、限长后作为卡片发送；仅当飞书
     * 真的拒绝了该卡片时，才回退为纯文本消息。设为 false 则始终发文本。
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

        /* 防止日志回环：绝不让日志通道抛出异常。 */
        try {
            $formatted = (string) (is_array($record) ? $record['formatted'] : $record->formatted);
            $robot = Lark::robot($this->robot);

            if ($this->card) {
                try {
                    $robot->send($this->cardMessage($record, $formatted));

                    return;
                } catch (\Throwable $cardException) {
                    // 卡片被拒（例如 19036）：回退为纯文本。
                }
            }

            $robot->send($this->textMessage($formatted));
        } catch (\Throwable $exception) {
            // 故意吞掉异常
        }
    }

    /**
     * 构建按级别上色的交互卡片。正文用 plain_text 块，
     * 因此任意日志输出都不会被当作 markdown 再次解析。
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
     * 从 Monolog 2 的数组记录或 Monolog 3 的 LogRecord 中提取
     * [级别值, 级别名, 通道]，且不与任一版本强耦合。
     *
     * @param array<mixed>|LogRecord $record
     *
     * @return array{0:int,1:string,2:string}
     */
    protected function describe($record): array
    {
        $level = is_array($record) ? ($record['level'] ?? 0) : $record->level;

        if (is_object($level)) {
            // Monolog\Level 枚举（Monolog 3）。
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
     * 将 Monolog 级别值映射为飞书卡片标题栏的颜色模板。
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
     * 把记录裁剪到安全大小，且绝不产生乱码。
     *
     * 步骤：
     *  1. 先净化为合法 UTF-8（堆栈里可能夹带原始字节），保证后续
     *     裁剪与 json_encode 既不乱码也不失败；
     *  2. 用 mb_strcut 并显式指定 UTF-8 裁剪——它在字符边界处停止，
     *     所以多字节字符（如中文）绝不会被从中间切断；
     *  3. 追加标记，让阅读者知道尾部已被丢弃。
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
