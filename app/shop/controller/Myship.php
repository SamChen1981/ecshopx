<?php

namespace app\shop\controller;

/**
 * 支付配送DEMO
 */
class Myship extends Init
{
    public function index()
    {
        load_helper('order');
        load_helper('transaction');

        /* 载入语言文件 */
        load_lang('shopping_flow');
        load_lang('user');

        /*------------------------------------------------------ */
        //-- INPUT
        /*------------------------------------------------------ */

        if ($_SESSION['user_id'] > 0) {
            $consignee_list = get_consignee_list($_SESSION['user_id']);

            $choose['country'] = isset($_POST['country']) ? intval($_POST['country']) : $consignee_list[0]['country'];
            $choose['province'] = isset($_POST['province']) ? intval($_POST['province']) : $consignee_list[0]['province'];
            $choose['city'] = isset($_POST['city']) ? intval($_POST['city']) : $consignee_list[0]['city'];
            $choose['district'] = isset($_POST['district']) ? intval($_POST['district']) : (isset($consignee_list[0]['district']) ? $consignee_list[0]['district'] : 0);
        } else {
            $choose['country'] = isset($_POST['country']) ? intval($_POST['country']) : $GLOBALS['_CFG']['shop_country'];
            $choose['province'] = isset($_POST['province']) ? intval($_POST['province']) : 2;
            $choose['city'] = isset($_POST['city']) ? intval($_POST['city']) : 35;
            $choose['district'] = isset($_POST['district']) ? intval($_POST['district']) : 417;
        }

        /*------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        assign_template();
        assign_dynamic('myship');
        $position = assign_ur_here(0, $GLOBALS['_LANG']['shopping_myship']);
        $GLOBALS['smarty']->assign('page_title', $position['title']);    // 页面标题
        $GLOBALS['smarty']->assign('ur_here', $position['ur_here']);  // 当前位置

        $GLOBALS['smarty']->assign('helps', get_shop_help());       // 网店帮助
        $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);

        $GLOBALS['smarty']->assign('choose', $choose);

        $province_list[null] = get_regions(1, $choose['country']);
        $city_list[null] = get_regions(2, $choose['province']);
        $district_list[null] = get_regions(3, $choose['city']);

        $GLOBALS['smarty']->assign('province_list', $province_list);
        $GLOBALS['smarty']->assign('city_list', $city_list);
        $GLOBALS['smarty']->assign('district_list', $district_list);

        /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
        $GLOBALS['smarty']->assign('country_list', get_regions());

        /* 取得配送列表 */
        $region = array($choose['country'], $choose['province'], $choose['city'], $choose['district']);
        $shipping_list = available_shipping_list($region);
        $cart_weight_price = 0;
        $insure_disabled = true;
        $cod_disabled = true;

        foreach ($shipping_list as $key => $val) {
            $shipping_cfg = unserialize_config($val['configure']);
            $shipping_fee = shipping_fee(
                $val['shipping_code'],
                unserialize($val['configure']),
                $cart_weight_price['weight'],
                $cart_weight_price['amount']
            );

            $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
            $shipping_list[$key]['fee'] = $shipping_fee;
            $shipping_list[$key]['free_money'] = price_format($shipping_cfg['free_money'], false);
            $shipping_list[$key]['insure_formated'] = strpos($val['insure'], '%') === false ?
                price_format($val['insure'], false) : $val['insure'];
        }

        $GLOBALS['smarty']->assign('shipping_list', $shipping_list);

        return $GLOBALS['smarty']->display('myship.view.php');
    }
}
