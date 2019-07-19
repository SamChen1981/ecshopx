<?php

$path = __DIR__ . '/vendor/topthink/framework/library/think/';

$rules = [
    'App.php' => [
        '\'application\'' => '\'app\''
    ],
    'Loader.php' => [
        '\'think\'' => '\'artisan\''
    ],
];

foreach ($rules as $file => $rule) {
    $content = file_get_contents($path . $file);
    $content = str_replace(array_keys($rule), array_values($rule), $content);
    file_put_contents($path . $file, $content);
}
