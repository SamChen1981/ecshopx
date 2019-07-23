<?php

namespace app\shop\controller;

/**
 * 生成验证码
 */
class Captcha extends Init
{
    public function index()
    {
        $img = new captcha(ROOT_PATH . 'data/captcha/', $GLOBALS['_CFG']['captcha_width'], $GLOBALS['_CFG']['captcha_height']);
        @ob_end_clean(); //清除之前出现的多余输入
        if (isset($_REQUEST['is_login'])) {
            $img->session_word = 'captcha_login';
        }
        $img->generate_image();
    }
}
