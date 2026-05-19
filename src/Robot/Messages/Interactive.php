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
 * 交互式消息卡片。
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
     * 用标题和元素列表快速构建一张卡片。
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
     * 向卡片追加一个元素。
     *
     * @param array<string, mixed> $element
     */
    public function element(array $element): static
    {
        $this->message['card']['elements'][] = $element;

        return $this;
    }

    /**
     * 追加一个 lark_md 内容块（内容会按 markdown 解析）。
     */
    public function markdown(string $content): static
    {
        return $this->element([
            'tag'  => 'div',
            'text' => ['tag' => 'lark_md', 'content' => $content],
        ]);
    }

    /**
     * 追加一个 plain_text 内容块（内容按字面渲染，
     * 绝不按 markdown 解析——适合承载任意日志输出）。
     */
    public function text(string $content): static
    {
        return $this->element([
            'tag'  => 'div',
            'text' => ['tag' => 'plain_text', 'content' => $content],
        ]);
    }
}
