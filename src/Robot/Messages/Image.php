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
 * Image message. The image_key must be uploaded via the Lark open api first.
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
