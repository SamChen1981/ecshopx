<?php

namespace app\shop\controller;

/**
 * 支付响应页面
 */
class Respond extends Init
{
    public function index()
    {
        load_helper('payment');
        load_helper('order');
        /* 支付方式代码 */
        $pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : '';

        //获取首信支付方式
        if (empty($pay_code) && !empty($_REQUEST['v_pmode']) && !empty($_REQUEST['v_pstring'])) {
            $pay_code = 'cappay';
        }

        //pc微信扫码的判断
        if (empty($pay_code)) {
            $xml = file_get_contents('php://input');
            if (strpos($xml, '<xml><appid>') !== false && strpos($xml, 'openid') !== false) {
                $pay_code = 'wxpaynative';
            }
        }

        //获取快钱神州行支付方式
        if (empty($pay_code) && ($_REQUEST['ext1'] == 'shenzhou') && ($_REQUEST['ext2'] == 'ecshop')) {
            $pay_code = 'shenzhou';
        }
        //获取天工支付方式
        if (empty($pay_code)) {
            if ($_GET['metadata'] == 'tiangong') {
                $pay_code = 'tiangong';
            }
            if ($_GET['metadata'] == 'tiangongwx') {
                $pay_code = 'tiangongwx';
            }

            if ($_GET['metadata'] == 'yunqiwx') {
                $pay_code = 'yunqi';
            }
            if (isset($_REQUEST['Version']) && $_REQUEST['Version'] == '20140728') {
                $pay_code = 'chinapay';
            }
        }
        /* 参数是否为空 */
        if (empty($pay_code)) {
            $msg = $GLOBALS['_LANG']['pay_not_exist'];
        } else {
            /* 检查code里面有没有问号 */
            if (strpos($pay_code, '?') !== false) {
                $arr1 = explode('?', $pay_code);
                $arr2 = explode('=', $arr1[1]);

                $_REQUEST['code'] = $arr1[0];
                $_REQUEST[$arr2[0]] = $arr2[1];
                $_GET['code'] = $arr1[0];
                $_GET[$arr2[0]] = $arr2[1];
                $pay_code = $arr1[0];
            }

            /* 判断是否启用 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('payment') . " WHERE pay_code = '$pay_code' AND enabled = 1";
            if ($GLOBALS['db']->getOne($sql) == 0) {
                $msg = $GLOBALS['_LANG']['pay_disabled'];
            } else {
                $plugin_file = dirname(__FILE__) . '/includes/modules/payment/' . $pay_code . '.php';
                /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
                if (file_exists($plugin_file)) {
                    /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
                    include_once($plugin_file);

                    $payment = new $pay_code();
                    $msg = (@$payment->respond()) ? $GLOBALS['_LANG']['pay_success'] : $GLOBALS['_LANG']['pay_fail'];
                } else {
                    $msg = $GLOBALS['_LANG']['pay_not_exist'];
                }
            }
        }

        assign_template();
        $position = assign_ur_here();
        $GLOBALS['smarty']->assign('page_title', $position['title']);   // 页面标题
        $GLOBALS['smarty']->assign('ur_here', $position['ur_here']); // 当前位置
        $GLOBALS['smarty']->assign('page_title', $position['title']);   // 页面标题
        $GLOBALS['smarty']->assign('ur_here', $position['ur_here']); // 当前位置
        $GLOBALS['smarty']->assign('helps', get_shop_help());      // 网店帮助

        $GLOBALS['smarty']->assign('message', $msg);
        $GLOBALS['smarty']->assign('shop_url', $GLOBALS['ecs']->url());

        $GLOBALS['smarty']->display('respond.dwt');
    }
}
