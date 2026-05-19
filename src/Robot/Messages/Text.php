<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
 */

namespace HughCube\Laravel\Lark\Robot\Messages;

/**
 * 文本消息。
 *
 * @ 提及是文本内容里的内联标签，例如：
 *   <at user_id="all"></at>            提及所有人
 *   <at user_id="ou_xxx">name</at>     按 open_id 提及某人
 *   <at email="x@x.com"></at>          按邮箱提及某人
 *
 * @see https://open.feishu.cn/document/client-docs/bot-v3/add-custom-bot
 */
final class Text extends Message
{
    public function __construct(string $text = '')
    {
        $this->message = [
            'msg_type' => 'text',
            'content'  => [
                'text' => $text,
            ],
        ];
    }

    public static function make(string $text = ''): static
    {
        return new static($text);
    }

    /**
     * 追加一段文本。
     */
    public function text(string $text): static
    {
        $this->message['content']['text'] .= $text;

        return $this;
    }

    /**
     * 追加一段文本并换行。
     */
    public function line(string $text = ''): static
    {
        return $this->text($text . "\n");
    }

    /**
     * 提及群里所有人。
     */
    public function atAll(): static
    {
        return $this->text('<at user_id="all"></at>');
    }

    /**
     * 按 open_id / union_id / user_id 提及某人。
     */
    public function at(string $userId, string $name = ''): static
    {
        return $this->text(sprintf('<at user_id="%s">%s</at>', $userId, $name));
    }

    /**
     * 按邮箱提及某人。
     */
    public function atEmail(string $email, string $name = ''): static
    {
        return $this->text(sprintf('<at email="%s">%s</at>', $email, $name));
    }
}
