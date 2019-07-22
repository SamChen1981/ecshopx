<?php

namespace app\shop\controller;

/**
 * 超值礼包列表
 */
class Package extends Init
{
    public function index()
    {
        load_helper('order');
        load_helper('transaction');

        /* 载入语言文件 */
        load_lang('shopping_flow');
        load_lang('user');
        load_lang('admin/package');

        /*------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        assign_template();
        assign_dynamic('package');
        $position = assign_ur_here(0, $GLOBALS['_LANG']['shopping_package']);
        $GLOBALS['smarty']->assign('page_title', $position['title']);    // 页面标题
        $GLOBALS['smarty']->assign('ur_here', $position['ur_here']);  // 当前位置

        /* 读出所有礼包信息 */

        $now = gmtime();

        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('goods_activity') . " WHERE `start_time` <= '$now' AND `end_time` >= '$now' AND `act_type` = '4' ORDER BY `end_time`";
        $res = $GLOBALS['db']->query($sql);

        $list = array();
        foreach ($res as $row) {
            $row['start_time'] = local_date('Y-m-d H:i', $row['start_time']);
            $row['end_time'] = local_date('Y-m-d H:i', $row['end_time']);
            $ext_arr = unserialize($row['ext_info']);
            unset($row['ext_info']);
            if ($ext_arr) {
                foreach ($ext_arr as $key => $val) {
                    $row[$key] = $val;
                }
            }

            $sql = "SELECT pg.package_id, pg.goods_id, pg.goods_number, pg.admin_id, " .
                " g.goods_sn, g.goods_name, g.market_price, g.goods_thumb, " .
                " IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price " .
                " FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg " .
                "   LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g " .
                "   ON g.goods_id = pg.goods_id " .
                " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                " WHERE pg.package_id = " . $row['act_id'] . " " .
                " ORDER BY pg.goods_id";

            $goods_res = $GLOBALS['db']->getAll($sql);

            $subtotal = 0;
            foreach ($goods_res as $key => $val) {
                $goods_res[$key]['goods_thumb'] = get_image_path($val['goods_id'], $val['goods_thumb'], true);
                $goods_res[$key]['market_price'] = price_format($val['market_price']);
                $goods_res[$key]['rank_price'] = price_format($val['rank_price']);
                $subtotal += $val['rank_price'] * $val['goods_number'];
            }


            $row['goods_list'] = $goods_res;
            $row['subtotal'] = price_format($subtotal);
            $row['saving'] = price_format(($subtotal - $row['package_price']));
            $row['package_price'] = price_format($row['package_price']);

            $list[] = $row;
        }

        $GLOBALS['smarty']->assign('list', $list);

        $GLOBALS['smarty']->assign('helps', get_shop_help());       // 网店帮助
        $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);

        $GLOBALS['smarty']->assign('feed_url', ($GLOBALS['_CFG']['rewrite'] == 1) ? "feed-typepackage.xml" : 'feed.php?type=package'); // RSS URL
        return $GLOBALS['smarty']->display('package.view.php');
    }
}
