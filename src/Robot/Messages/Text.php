<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace HughCube\Laravel\Lark\Robot\Messages;

/**
 * Text message.
 *
 * Mentions are inline tags inside the text content, e.g.
 *   <at user_id="all"></at>            mention everyone
 *   <at user_id="ou_xxx">name</at>     mention a user by open_id
 *   <at email="x@x.com"></at>          mention a user by email
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
     * Append a chunk of text.
     */
    public function text(string $text): static
    {
        $this->message['content']['text'] .= $text;

        return $this;
    }

    /**
     * Append a chunk of text followed by a newline.
     */
    public function line(string $text = ''): static
    {
        return $this->text($text . "\n");
    }

    /**
     * Mention everyone in the chat.
     */
    public function atAll(): static
    {
        return $this->text('<at user_id="all"></at>');
    }

    /**
     * Mention a user by open_id / union_id / user_id.
     */
    public function at(string $userId, string $name = ''): static
    {
        return $this->text(sprintf('<at user_id="%s">%s</at>', $userId, $name));
    }

    /**
     * Mention a user by email.
     */
    public function atEmail(string $email, string $name = ''): static
    {
        return $this->text(sprintf('<at email="%s">%s</at>', $email, $name));
    }
}
