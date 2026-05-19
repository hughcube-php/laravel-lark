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
 * Interactive message card.
 *
 * @see https://open.feishu.cn/document/common-capabilities/message-card/message-cards-content
 */
final class Interactive extends Message
{
    /**
     * @param array<string, mixed> $card
     */
    public function __construct(array $card = [])
    {
        $this->message = [
            'msg_type' => 'interactive',
            'card'     => $card,
        ];
    }

    /**
     * @param array<string, mixed> $card
     */
    public static function make(array $card = []): static
    {
        return new static($card);
    }

    /**
     * Build a card from a header title and a list of elements.
     *
     * @param array<int, array<string, mixed>> $elements
     */
    public static function card(string $title, array $elements, string $template = 'blue'): static
    {
        return new static([
            'config' => ['wide_screen_mode' => true],
            'header' => [
                'template' => $template,
                'title'    => ['tag' => 'plain_text', 'content' => $title],
            ],
            'elements' => $elements,
        ]);
    }

    /**
     * Append an element to the card.
     *
     * @param array<string, mixed> $element
     */
    public function element(array $element): static
    {
        $this->message['card']['elements'][] = $element;

        return $this;
    }

    /**
     * Append a lark_md content block (content is parsed as markdown).
     */
    public function markdown(string $content): static
    {
        return $this->element([
            'tag'  => 'div',
            'text' => ['tag' => 'lark_md', 'content' => $content],
        ]);
    }

    /**
     * Append a plain_text content block (content is rendered literally,
     * never interpreted as markdown — safe for arbitrary log output).
     */
    public function text(string $content): static
    {
        return $this->element([
            'tag'  => 'div',
            'text' => ['tag' => 'plain_text', 'content' => $content],
        ]);
    }
}
