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
 * Rich text (post) message.
 *
 * Content is a list of paragraphs, each paragraph is a list of nodes:
 *   ['tag' => 'text', 'text' => '...']
 *   ['tag' => 'a', 'text' => '...', 'href' => 'https://...']
 *   ['tag' => 'at', 'user_id' => 'ou_xxx']
 *   ['tag' => 'img', 'image_key' => 'img_xxx']
 *
 * @see https://open.feishu.cn/document/client-docs/bot-v3/add-custom-bot
 */
final class Post extends Message
{
    protected string $locale;

    /**
     * @param array<int, array<int, array<string, mixed>>> $content
     */
    public function __construct(string $title = '', array $content = [], string $locale = 'zh_cn')
    {
        $this->locale = $locale;
        $this->message = [
            'msg_type' => 'post',
            'content'  => [
                'post' => [
                    $locale => [
                        'title'   => $title,
                        'content' => $content,
                    ],
                ],
            ],
        ];
    }

    public static function make(string $title = '', string $locale = 'zh_cn'): static
    {
        return new static($title, [], $locale);
    }

    public function title(string $title): static
    {
        $this->message['content']['post'][$this->locale]['title'] = $title;

        return $this;
    }

    /**
     * Add one paragraph (a list of nodes).
     *
     * @param array<int, array<string, mixed>> $nodes
     */
    public function paragraph(array $nodes): static
    {
        $this->message['content']['post'][$this->locale]['content'][] = $nodes;

        return $this;
    }

    /**
     * Add a paragraph that contains a single text node.
     */
    public function line(string $text): static
    {
        return $this->paragraph([['tag' => 'text', 'text' => $text]]);
    }

    /**
     * Add a paragraph that contains a single link node.
     */
    public function link(string $text, string $href): static
    {
        return $this->paragraph([['tag' => 'a', 'text' => $text, 'href' => $href]]);
    }
}
