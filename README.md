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

在 Laravel / Lumen 中向[飞书（Lark）](https://www.feishu.cn/)**自定义机器人 webhook**
发送消息。支持签名校验，并覆盖自定义机器人全部消息类型：`text`、`post`、
`image`、`interactive`、`share_chat`。

## 安装

```shell
$ composer require hughcube/laravel-lark -vvv
```

ServiceProvider 与 `Lark` 门面会被自动发现。如需自定义配置，发布配置文件：

```shell
$ php artisan vendor:publish --tag=lark-config
```

## 配置

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

            // 完整的 webhook 地址，或仅 hook token（二选一即可）。
            'webhook' => env('LARK_ROBOT_WEBHOOK', ''),
            'token'   => env('LARK_ROBOT_TOKEN', ''),

            // 机器人安全设置里的签名密钥（可选）。
            'secret'  => env('LARK_ROBOT_SECRET', ''),
        ],
    ],
];
```

在 `.env` 中设置：

```dotenv
LARK_ROBOT_WEBHOOK=https://open.feishu.cn/open-apis/bot/v2/hook/xxxxxxxx-xxxx-xxxx
LARK_ROBOT_SECRET=your-signing-secret
```

## 用法

```php
use HughCube\Laravel\Lark\Lark;
use HughCube\Laravel\Lark\Robot\Messages\Text;
use HughCube\Laravel\Lark\Robot\Messages\Post;
use HughCube\Laravel\Lark\Robot\Messages\Image;
use HughCube\Laravel\Lark\Robot\Messages\Interactive;
use HughCube\Laravel\Lark\Robot\Messages\ShareChat;

// 纯文本（便捷方法）
Lark::robot()->text('hello from laravel-lark');

// 带 @ 提及、多行的文本
Lark::robot()->send(
    Text::make()
        ->line('Deploy finished ✅')
        ->atAll()
        ->at('ou_xxxxxxxx', 'Tom')
        ->atEmail('dev@example.com')
);

// 富文本（post）
Lark::robot()->send(
    Post::make('Release v1.0.0')
        ->line('All checks passed.')
        ->link('changelog', 'https://example.com/changelog')
);

// 消息卡片
Lark::robot()->send(
    Interactive::card('Build report', [])->markdown('**status:** success')
);

// 图片 / 分享群名片（image_key 与 chat_id 通过飞书开放接口获取）
Lark::robot()->send(new Image('img_xxxxxxxx'));
Lark::robot()->send(new ShareChat('oc_xxxxxxxx'));

// 使用 `robots` 配置里的某个具名机器人
Lark::robot('alerts')->text('disk almost full');
```

### 签名

配置了 `secret` 时，客户端会按飞书自定义机器人的要求对每个请求签名：

```
sign = base64( HMAC-SHA256( key = "{timestamp}\n{secret}", data = "" ) )
```

`timestamp` 为当前 Unix 时间戳（**单位：秒**）；`timestamp` 与 `sign` 都放在
请求体中发送。

### 日志通道

用 `HughCube\Laravel\Lark\Log\Handler` 把 Monolog 日志推送给机器人：

```php
// config/logging.php
'channels' => [
    'lark' => [
        'driver'  => 'monolog',
        'handler' => HughCube\Laravel\Lark\Log\Handler::class,
        // 'card' => true（默认）发送按级别上色的卡片；
        // 设为 false 则始终发送纯文本。
        'with'    => ['robot' => 'default', 'enabled' => true, 'card' => true],
        'level'   => 'warning',
    ],
],
```

## 消息体积上限

飞书的报错文案写的是 “30KB”，但自定义机器人实际能接受大得多的内容——
而且 `text` 与 `interactive` 卡片的上限**完全一致**。`tests/MaxLengthTest.php`
对真实 webhook 二分查找出 `text` 的真实边界；卡片边界用同样方式探测。实测：

| 消息类型 | 最后接受 | 首次拒绝 | 有效上限 |
| --- | --- | --- | --- |
| text | **153,323 字节** | 153,518 字节 | **≈ 150 KiB** |
| interactive 卡片 | **152,968 字节** | 153,241 字节 | **≈ 150 KiB** |

超过边界后接口返回 `error 19036 — The message exceeds the size limit of 30KB`
（文案里的 “30KB” 是虚的；真实上限约 ~150 KiB，≈ 153,600 = 150 × 1024）。

随包提供的 `Log\Handler` **默认发送按级别上色的交互卡片**（正文用
`plain_text`，日志内容绝不会被当作 markdown 再次解析），并以 UTF-8 安全方式
截断到 `120000` 字节——在边界之下留足余量，JSON 外壳绝不会把它顶超，且中文
绝不会被从半个字符处切断。飞书会自动折叠超长内容，所以长卡片依然好读；万一
飞书拒绝了该卡片，会自动回退为纯 `Text` 消息。在通道 `with` 中传
`['card' => false]` 可始终发送纯文本。

> 自定义机器人还有频控（约 5 次/秒，约 100 次/分钟，按机器人计）。

## 测试

单元测试在任何环境都能跑。实时用例（`@group live`）仅在配置了 webhook 时运行，
否则自动跳过：

```shell
export LARK_ROBOT_WEBHOOK="https://open.feishu.cn/open-apis/bot/v2/hook/xxxx"
export LARK_ROBOT_SECRET="xxxx"

composer test                       # 全部
vendor/bin/phpunit --exclude-group live   # 仅单元测试
```

CI 中 webhook 通过 `LARK_WEBHOOK` / `LARK_SECRET` 两个 GitHub Actions secret
提供，且实时用例被限定在矩阵的单个单元格上运行，以遵守机器人频控。

## 许可

MIT
