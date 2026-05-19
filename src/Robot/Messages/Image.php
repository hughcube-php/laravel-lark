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
 * 图片消息。image_key 需先通过飞书开放接口上传图片获得。
 *
 * @see https://open.feishu.cn/document/server-docs/im-v1/image/create
 */
final class Image extends Message
{
    public function __construct(string $imageKey)
    {
        $this->message = [
            'msg_type' => 'image',
            'content'  => [
                'image_key' => $imageKey,
            ],
        ];
    }

    public static function make(string $imageKey): static
    {
        return new static($imageKey);
    }
}
