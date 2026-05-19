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
 * 分享群名片（share_chat）消息。
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
