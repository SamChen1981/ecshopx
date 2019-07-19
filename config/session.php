<?php

return [
    'id' => '',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // SESSION 前缀
    'prefix' => 'shop',
    // 驱动方式 支持redis memcache memcached
    'type' => env('SESSION_DRIVER', ''),
    // 是否自动开启 SESSION
    'auto_start' => true,
];
