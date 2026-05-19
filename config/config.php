<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

return [
    /*
     * Config shared by every robot. Merged as the base, each robot's own
     * config overrides the matching keys here.
     */
    'defaults' => [
        'http' => [
            'timeout'         => 10.0,
            'connect_timeout' => 10.0,
        ],
    ],

    'robots' => [
        'default' => [
            'enabled' => env('LARK_ROBOT_ENABLED', true),

            /*
             * Either the full webhook url, or just the hook token. When only
             * the token is given the url is built automatically.
             *
             * webhook: https://open.feishu.cn/open-apis/bot/v2/hook/xxxxxxxx
             * token:   xxxxxxxx
             */
            'webhook' => env('LARK_ROBOT_WEBHOOK', ''),
            'token'   => env('LARK_ROBOT_TOKEN', ''),

            /*
             * The signing secret configured on the custom bot security setting.
             * Leave empty when signature verification is not enabled.
             */
            'secret' => env('LARK_ROBOT_SECRET', ''),
        ],
    ],
];
