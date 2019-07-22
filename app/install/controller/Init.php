<?php

/* 报告所有错误 */
@ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

/* 清除所有和文件操作相关的状态信息 */
clearstatcache();

/* 定义站点根 */
define('ROOT_PATH', str_replace('install/includes/init.php', '', str_replace('\\', '/', __FILE__)));

/* https 检测https */
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    define('FORCE_SSL_LOGIN', true);
    define('FORCE_SSL_ADMIN', true);
} else {
    if (isset($_SERVER['HTTP_ORIGIN']) && substr($_SERVER['HTTP_ORIGIN'], 0, 5)=='https') {
        $_SERVER['HTTPS'] = 'on';
        define('FORCE_SSL_LOGIN', true);
        define('FORCE_SSL_ADMIN', true);
    }
}

if (isset($_SERVER['PHP_SELF'])) {
    define('PHP_SELF', $_SERVER['PHP_SELF']);
} else {
    define('PHP_SELF', $_SERVER['SCRIPT_NAME']);
}

/* 定义版本的编码 */
define('EC_CHARSET', 'utf-8');
define('EC_DB_CHARSET', 'utf8');

load_helper('base');
load_helper('common');
load_helper('time');

/* 创建错误处理对象 */
$err = new ecs_error('message.view.php');

/* 初始化模板引擎 */
$smarty = new template(ROOT_PATH . 'install/templates/');

load_helper('installer', 'install');

/* 发送HTTP头部，保证浏览器识别UTF8编码 */
header('Content-type: text/html; charset='.EC_CHARSET);

@set_time_limit(360);
