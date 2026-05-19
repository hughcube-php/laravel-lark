<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
 */

return [
    /*
     * 所有机器人共享的配置。作为基底合并，每个机器人各自的配置会
     * 覆盖这里的同名键。
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
             * 可以填完整的 webhook 地址，也可以只填 hook token；
             * 只填 token 时会自动拼出完整地址。
             *
             * webhook: https://open.feishu.cn/open-apis/bot/v2/hook/xxxxxxxx
             * token:   xxxxxxxx
             */
            'webhook' => env('LARK_ROBOT_WEBHOOK', ''),
            'token'   => env('LARK_ROBOT_TOKEN', ''),

            /*
             * 自定义机器人安全设置里配置的签名密钥。
             * 未开启签名校验时留空即可。
             */
            'secret' => env('LARK_ROBOT_SECRET', ''),
        ],
    ],
];
