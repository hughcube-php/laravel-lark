<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
 */

namespace HughCube\Laravel\Lark\Tests;

use HughCube\Laravel\Lark\Robot\Client;
use HughCube\Laravel\Lark\Robot\Messages\Image;
use HughCube\Laravel\Lark\Robot\Messages\Interactive;
use HughCube\Laravel\Lark\Robot\Messages\Post;
use HughCube\Laravel\Lark\Robot\Messages\ShareChat;
use HughCube\Laravel\Lark\Robot\Messages\Text;

class MessageTest extends TestCase
{
    public function testText(): void
    {
        $message = (new Text('hello'))->getMessage();

        $this->assertSame('text', $message['msg_type']);
        $this->assertSame('hello', $message['content']['text']);
    }

    public function testTextBuilderAndMentions(): void
    {
        $message = Text::make()
            ->line('line1')
            ->atAll()
            ->at('ou_123', 'Tom')
            ->atEmail('a@b.com')
            ->getMessage();

        $this->assertStringContainsString('line1', $message['content']['text']);
        $this->assertStringContainsString('<at user_id="all"></at>', $message['content']['text']);
        $this->assertStringContainsString('<at user_id="ou_123">Tom</at>', $message['content']['text']);
        $this->assertStringContainsString('<at email="a@b.com"></at>', $message['content']['text']);
    }

    public function testPost(): void
    {
        $message = Post::make('title')
            ->line('para1')
            ->link('lark', 'https://www.feishu.cn')
            ->getMessage();

        $this->assertSame('post', $message['msg_type']);
        $this->assertSame('title', $message['content']['post']['zh_cn']['title']);
        $this->assertSame('text', $message['content']['post']['zh_cn']['content'][0][0]['tag']);
        $this->assertSame('a', $message['content']['post']['zh_cn']['content'][1][0]['tag']);
    }

    public function testImage(): void
    {
        $message = (new Image('img_xxx'))->getMessage();

        $this->assertSame('image', $message['msg_type']);
        $this->assertSame('img_xxx', $message['content']['image_key']);
    }

    public function testShareChat(): void
    {
        $message = (new ShareChat('oc_xxx'))->getMessage();

        $this->assertSame('share_chat', $message['msg_type']);
        $this->assertSame('oc_xxx', $message['content']['share_chat_id']);
    }

    public function testInteractive(): void
    {
        $message = Interactive::card('Title', [])
            ->markdown('**bold**')
            ->getMessage();

        $this->assertSame('interactive', $message['msg_type']);
        $this->assertSame('Title', $message['card']['header']['title']['content']);
        $this->assertSame('lark_md', $message['card']['elements'][0]['text']['tag']);
    }

    public function testSignAlgorithm(): void
    {
        $client = new Client(['webhook' => 'https://example.com/hook']);

        $timestamp = 1599360473;
        $secret = 'test-secret';

        $expected = base64_encode(hash_hmac('sha256', '', $timestamp . "\n" . $secret, true));

        $this->assertSame($expected, $client->sign($timestamp, $secret));
        // 32 字节 sha256 摘要的 base64 结果固定为 44 个字符。
        $this->assertSame(44, strlen($client->sign($timestamp, $secret)));
    }

    public function testWebhookResolution(): void
    {
        $byUrl = new Client(['webhook' => 'https://open.feishu.cn/open-apis/bot/v2/hook/abc']);
        $this->assertSame('https://open.feishu.cn/open-apis/bot/v2/hook/abc', $byUrl->getWebhook());

        $byToken = new Client(['token' => 'abc']);
        $this->assertSame(Client::HOOK_PREFIX . 'abc', $byToken->getWebhook());
    }

    public function testDisabledRobotIsSkipped(): void
    {
        $client = new Client(['enabled' => false, 'token' => 'abc']);

        $result = $client->send(new Text('should not be sent'));

        $this->assertSame(0, $result['code']);
    }
}
