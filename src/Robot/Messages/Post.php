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
 * 富文本（post）消息。
 *
 * content 是段落列表，每个段落是节点列表：
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
     * 追加一个段落（节点列表）。
     *
     * @param array<int, array<string, mixed>> $nodes
     */
    public function paragraph(array $nodes): static
    {
        $this->message['content']['post'][$this->locale]['content'][] = $nodes;

        return $this;
    }

    /**
     * 追加一个只含单个文本节点的段落。
     */
    public function line(string $text): static
    {
        return $this->paragraph([['tag' => 'text', 'text' => $text]]);
    }

    /**
     * 追加一个只含单个链接节点的段落。
     */
    public function link(string $text, string $href): static
    {
        return $this->paragraph([['tag' => 'a', 'text' => $text, 'href' => $href]]);
    }
}
