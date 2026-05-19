<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
 */

namespace HughCube\Laravel\Lark\Robot\Messages;

abstract class Message
{
    /**
     * @var array<mixed>
     */
    protected array $message = [];

    /**
     * 作为请求体发送的原始消息内容。
     *
     * @return array<mixed>
     */
    public function getMessage(): array
    {
        return $this->message;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->getMessage();
    }
}
