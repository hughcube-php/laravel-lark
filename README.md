<h1 align="center"> laravel-lark </h1>

<p align="center">
    <a href="https://github.com/hughcube-php/laravel-lark/actions?query=workflow%3ATest">
        <img src="https://github.com/hughcube-php/laravel-lark/workflows/Test/badge.svg" alt="Test Actions status">
    </a>
    <a href="https://github.com/hughcube-php/laravel-lark/actions?query=workflow%3ALint">
        <img src="https://github.com/hughcube-php/laravel-lark/workflows/Lint/badge.svg" alt="Lint Actions status">
    </a>
    <a href="https://github.com/hughcube-php/laravel-lark/blob/master/LICENSE">
        <img src="https://img.shields.io/badge/license-MIT-428f7e.svg" alt="License">
    </a>
</p>

Send [Lark / Feishu (飞书)](https://www.feishu.cn/) **custom robot (自定义机器人) webhook** messages
from Laravel & Lumen. Supports signature verification and every custom-bot
message type: `text`, `post`, `image`, `interactive` and `share_chat`.

## Installing

```shell
$ composer require hughcube/laravel-lark -vvv
```

The service provider and `Lark` facade are auto-discovered. Publish the config
if you want to tweak it:

```shell
$ php artisan vendor:publish --tag=lark-config
```

## Configuration

```php
return [
    'defaults' => [
        'http' => [
            'timeout'         => 10.0,
            'connect_timeout' => 10.0,
        ],
    ],

    'robots' => [
        'default' => [
            'enabled' => env('LARK_ROBOT_ENABLED', true),

            // The full webhook url, OR just the hook token (either works).
            'webhook' => env('LARK_ROBOT_WEBHOOK', ''),
            'token'   => env('LARK_ROBOT_TOKEN', ''),

            // The signing secret from the bot's security setting (optional).
            'secret'  => env('LARK_ROBOT_SECRET', ''),
        ],
    ],
];
```

Set in `.env`:

```dotenv
LARK_ROBOT_WEBHOOK=https://open.feishu.cn/open-apis/bot/v2/hook/xxxxxxxx-xxxx-xxxx
LARK_ROBOT_SECRET=your-signing-secret
```

## Usage

```php
use HughCube\Laravel\Lark\Lark;
use HughCube\Laravel\Lark\Robot\Messages\Text;
use HughCube\Laravel\Lark\Robot\Messages\Post;
use HughCube\Laravel\Lark\Robot\Messages\Image;
use HughCube\Laravel\Lark\Robot\Messages\Interactive;
use HughCube\Laravel\Lark\Robot\Messages\ShareChat;

// Plain text (helper)
Lark::robot()->text('hello from laravel-lark');

// Text with mentions and multiple lines
Lark::robot()->send(
    Text::make()
        ->line('Deploy finished ✅')
        ->atAll()
        ->at('ou_xxxxxxxx', 'Tom')
        ->atEmail('dev@example.com')
);

// Rich text (post)
Lark::robot()->send(
    Post::make('Release v1.0.0')
        ->line('All checks passed.')
        ->link('changelog', 'https://example.com/changelog')
);

// Message card
Lark::robot()->send(
    Interactive::card('Build report', [])->markdown('**status:** success')
);

// Image / share chat (key & id obtained from the Lark open api)
Lark::robot()->send(new Image('img_xxxxxxxx'));
Lark::robot()->send(new ShareChat('oc_xxxxxxxx'));

// A named robot from the `robots` config
Lark::robot('alerts')->text('disk almost full');
```

### Signature

When `secret` is configured the client signs every request the way the Feishu
custom bot expects:

```
sign = base64( HMAC-SHA256( key = "{timestamp}\n{secret}", data = "" ) )
```

`timestamp` is the current Unix time **in seconds**; `timestamp` and `sign` are
sent in the request body.

### Logging channel

Pipe Monolog records to a robot with `HughCube\Laravel\Lark\Log\Handler`:

```php
// config/logging.php
'channels' => [
    'lark' => [
        'driver'  => 'monolog',
        'handler' => HughCube\Laravel\Lark\Log\Handler::class,
        'with'    => ['robot' => 'default', 'enabled' => true],
        'level'   => 'warning',
    ],
],
```

## Message size limit

The Feishu documentation only implies a ~30 KB content limit, but the custom
robot actually accepts much larger `text` payloads. This package ships a live
probe (`tests/MaxLengthTest.php`) that binary-searches the real boundary
against a webhook. Measured result:

| metric | value |
| --- | --- |
| last accepted | **153,323 bytes** |
| first rejected | 153,518 bytes |
| effective limit | **≈ 150 KiB (153,600 bytes)** |

The bundled `Log\Handler` trims to `20000` bytes — far below the limit so the
signed JSON envelope can never push a notification over, and so log messages
stay readable in chat.

> Custom bots are also frequency limited (≈ 5 req/s, ≈ 100 req/min per bot).

## Testing

Unit tests run anywhere. The live tests (`@group live`) only run when a webhook
is configured, otherwise they self-skip:

```shell
export LARK_ROBOT_WEBHOOK="https://open.feishu.cn/open-apis/bot/v2/hook/xxxx"
export LARK_ROBOT_SECRET="xxxx"

composer test                       # everything
vendor/bin/phpunit --exclude-group live   # unit only
```

In CI the webhook is provided via the `LARK_WEBHOOK` / `LARK_SECRET` GitHub
Actions secrets, and the live group is restricted to a single matrix cell to
respect the bot frequency limit.

## License

MIT
