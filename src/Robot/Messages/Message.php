<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace HughCube\Laravel\Lark\Robot\Messages;

abstract class Message
{
    /**
     * @var array<mixed>
     */
    protected array $message = [];

    /**
     * The raw message payload sent as the request body.
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
