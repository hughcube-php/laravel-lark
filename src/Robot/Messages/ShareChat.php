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
 * Share chat (group business card) message.
 *
 * @see https://open.feishu.cn/document/client-docs/bot-v3/add-custom-bot
 */
final class ShareChat extends Message
{
    public function __construct(string $shareChatId)
    {
        $this->message = [
            'msg_type' => 'share_chat',
            'content'  => [
                'share_chat_id' => $shareChatId,
            ],
        ];
    }

    public static function make(string $shareChatId): static
    {
        return new static($shareChatId);
    }
}
