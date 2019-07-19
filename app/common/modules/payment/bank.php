<?php

/**
 * 银行汇款（转帐）插件
 */

$payment_lang = ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/payment/bank.php';

if (file_exists($payment_lang)) {
    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code'] = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc'] = 'bank_desc';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod'] = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online'] = '0';

    /* 作者 */
    $modules[$i]['author'] = 'ECSHOP TEAM';

    /* 网址 */
    $modules[$i]['website'] = 'http://www.ecshop.com';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config'] = array();

    return;
}

/**
 * 类
 */
class bank
{
    public function __construct()
    {
        $this->bank();
    }

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function bank()
    {
    }

    /**
     * 提交函数
     */
    public function get_code()
    {
        return '';
    }

    /**
     * 处理函数
     */
    public function response()
    {
        return;
    }
}
