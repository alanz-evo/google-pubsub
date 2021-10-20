<?php

return [
    'connections' => [
        // @see Google\Cloud\PubSub\PubSubClient::__construct
        'default' => [],
    ],

    'publishers' => [
        'default' => [
            'connection' => 'default',
        ],
    ],

    'subscribers' => [
        'default' => [
            'subscriber' => 'mysubscriber',
            'connection' => 'default',
        ],
    ],

    'listeners' => [
        // 設定範例
        // 'sample' => [
        //     'messageLockSec' => 30,  // 每則 Message 在處理時的鎖定時間，避免同時間處理到同一則 Message 用
        //     'max_messages' => 1,  // 每次從 PubSub 抓下來的 Message 數量
        //     'handler' => 'App\\Handler',  // 負責處理 Message 的處理者
        //     'throwable_handler' => 'App\\ThrowableHandler',  // 當處理者發生錯誤時，要處理錯誤的處理者
        //     'subscriber' => 'sample',
        // ],
    ],
];
