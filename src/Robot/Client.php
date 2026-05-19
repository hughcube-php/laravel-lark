<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
 */

namespace HughCube\Laravel\Lark\Robot;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\Laravel\Lark\Robot\Messages\Message;
use HughCube\Laravel\Lark\Robot\Messages\Text;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use RuntimeException;

/**
 * 飞书（Lark）自定义机器人客户端。
 *
 * @see https://open.feishu.cn/document/client-docs/bot-v3/add-custom-bot
 */
class Client
{
    /**
     * 仅配置了 hook token 时使用的默认 webhook 前缀。
     */
    public const HOOK_PREFIX = 'https://open.feishu.cn/open-apis/bot/v2/hook/';

    /**
     * @var array<mixed>
     */
    protected array $config;

    protected ?HttpClient $httpClient = null;

    /**
     * @param array<mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = array_replace_recursive($this->defaultConfig(), $config);
    }

    /**
     * 默认配置。
     *
     * @return array<mixed>
     */
    public function defaultConfig(): array
    {
        return [
            'enabled' => true,
            'http'    => [
                RequestOptions::TIMEOUT         => 10.0,
                RequestOptions::CONNECT_TIMEOUT => 10.0,
                RequestOptions::READ_TIMEOUT    => 10.0,
                RequestOptions::HTTP_ERRORS     => false,
            ],
        ];
    }

    protected function getHttpClient(): HttpClient
    {
        if (!$this->httpClient instanceof HttpClient) {
            $this->httpClient = new HttpClient((array) $this->get('http', []));
        }

        return $this->httpClient;
    }

    /**
     * @param array-key $key
     */
    protected function get($key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    public function isEnabled(): bool
    {
        return (bool) $this->get('enabled', true);
    }

    /**
     * 解析最终的 webhook 地址。
     */
    public function getWebhook(): string
    {
        $webhook = (string) $this->get('webhook', '');
        if ('' !== $webhook) {
            return $webhook;
        }

        $token = (string) $this->get('token', '');
        if ('' !== $token) {
            return static::HOOK_PREFIX . $token;
        }

        throw new InvalidArgumentException('The lark robot webhook/token is not configured.');
    }

    /**
     * 生成飞书签名。
     *
     * 算法：key = "{timestamp}\n{secret}"，data = ""（空串），sha256 后再 base64。
     *
     * @see https://open.feishu.cn/document/client-docs/bot-v3/add-custom-bot
     */
    public function sign(int $timestamp, string $secret): string
    {
        return base64_encode(hash_hmac('sha256', '', $timestamp . "\n" . $secret, true));
    }

    /**
     * 通过自定义机器人 webhook 发送一条消息。
     *
     * @throws GuzzleException
     *
     * @return array<mixed>
     */
    public function send(Message $message): array
    {
        if (!$this->isEnabled()) {
            return ['code' => 0, 'msg' => 'skipped: robot disabled', 'data' => []];
        }

        $body = $message->getMessage();

        $secret = (string) $this->get('secret', '');
        if ('' !== $secret) {
            $timestamp = time();
            $body['timestamp'] = (string) $timestamp;
            $body['sign'] = $this->sign($timestamp, $secret);
        }

        $response = $this->getHttpClient()->post($this->getWebhook(), [
            RequestOptions::JSON => $body,
        ]);

        $contents = $response->getBody()->getContents();
        $results = json_decode($contents, true);

        if (!is_array($results)) {
            throw new RuntimeException(sprintf(
                'Lark robot: unable to decode response (HTTP %d): %s',
                $response->getStatusCode(),
                $contents
            ));
        }

        /*
         * v2 接口返回 "code"，历史上也可能返回 "StatusCode"，
         * 其中任意一个为 0 即视为成功。
         */
        $code = $results['code'] ?? $results['StatusCode'] ?? null;
        if (null === $code) {
            throw new RuntimeException('Lark robot: unknown response: ' . $contents);
        }

        if (0 !== (int) $code) {
            $msg = $results['msg'] ?? $results['StatusMessage'] ?? 'unknown error';
            throw new RuntimeException(
                sprintf('Lark robot error [%s]: %s', $code, $msg),
                (int) $code
            );
        }

        return $results;
    }

    /**
     * 便捷方法：直接发送一条纯文本消息。
     *
     * @throws GuzzleException
     *
     * @return array<mixed>
     */
    public function text(string $text): array
    {
        return $this->send(new Text($text));
    }
}
