<?php

if (!function_exists('base_path')) {
    /**
     * 获取项目根目录
     *
     * @param string $path
     * @return string
     */
    function base_path($path = '')
    {
        return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('app_path')) {
    /**
     * 获取应用目录
     *
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        return base_path('app') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('config_path')) {
    /**
     * 获取配置目录
     *
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return base_path('config') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('public_path')) {
    /**
     * 获取web根目录
     *
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        return base_path('public') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('runtime_path')) {
    /**
     * 获取运行时目录
     *
     * @param string $path
     * @return string
     */
    function runtime_path($path = '')
    {
        return base_path('runtime') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('load_helper')) {
    /**
     * 加载函数库
     *
     * @param $name
     * @param null $module
     */
    function load_helper($name, $module = null)
    {
        $helpers = is_array($name) ? $name : [$name];
        $path = is_null($module) ? app_path('common/helpers') : app_path($module . 'common');

        foreach ($helpers as $helper) {
            $helperFile = $path . '/' . $helper . '.php';
            if (file_exists($helperFile)) {
                require $helperFile;
            }
        }
    }
}

if (!function_exists('load_lang')) {
    /**
     * 加载语言包
     *
     * @param $name
     * @param null $module
     */
    function load_lang($name, $module = null)
    {
        static $_LANG = [];

        $language = is_array($name) ? $name : [$name];
        $path = is_null($module) ? base_path('resource/lang') : app_path($module . 'lang');

        foreach ($language as $lang) {
            $langFile = $path . '/' . config('app.default_lang') . '/' . $lang . '.php';
            if (file_exists($langFile)) {
                $_LANG = array_merge($_LANG, require $langFile);
            }
        }

        $GLOBALS['_LANG'] = $_LANG;
    }
}

require_once __DIR__ . '/safety.php';
