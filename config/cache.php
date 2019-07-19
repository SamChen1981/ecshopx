<?php

return [
    // 驱动方式
    'type' => env('CACHE_DRIVER', 'File'),
    // 缓存保存目录
    'path' => '',
    // 缓存前缀
    'prefix' => '',
    // 缓存有效期 0表示永久缓存
    'expire' => 0,
];
