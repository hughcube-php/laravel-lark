<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
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
 * Lark (Feishu) custom robot client.
 *
 * @see https://open.feishu.cn/document/client-docs/bot-v3/add-custom-bot
 */
class Client
{
    /**
     * Default endpoint prefix used when only a hook token is configured.
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
     * Resolve the final webhook url.
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
     * Generate the Feishu signature.
     *
     * key  = "{timestamp}\n{secret}", data = "" (empty), algo = sha256, then base64.
     *
     * @see https://open.feishu.cn/document/client-docs/bot-v3/add-custom-bot
     */
    public function sign(int $timestamp, string $secret): string
    {
        return base64_encode(hash_hmac('sha256', '', $timestamp . "\n" . $secret, true));
    }

    /**
     * Send a message through the custom robot webhook.
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
         * The hook returns "code" on the v2 endpoint and historically also
         * "StatusCode". Treat either being zero as success.
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
     * Convenience helper to send a plain text message.
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
