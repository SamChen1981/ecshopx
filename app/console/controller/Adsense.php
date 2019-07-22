<?php

namespace app\console\controller;

/**
 * 站外JS投放的统计程序
 */
class Adsense extends Init
{
    public function index()
    {
        load_helper('order');
        load_lang('admin/ads');

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 站外投放广告的统计
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list' || $_REQUEST['act'] == 'download') {
            admin_priv('ad_manage');

            /* 获取广告数据 */
            $ads_stats = array();
            $sql = "SELECT a.ad_id, a.ad_name, b.* " .
                "FROM " . $GLOBALS['ecs']->table('ad') . " AS a, " . $GLOBALS['ecs']->table('adsense') . " AS b " .
                "WHERE b.from_ad = a.ad_id ORDER by a.ad_name DESC";
            $res = $GLOBALS['db']->query($sql);
            foreach ($res as $rows) {
                /* 获取当前广告所产生的订单总数 */
                $rows['referer'] = addslashes($rows['referer']);
                $sql2 = 'SELECT COUNT(order_id) FROM ' . $GLOBALS['ecs']->table('order_info') . " WHERE from_ad='$rows[ad_id]' AND referer='$rows[referer]'";
                $rows['order_num'] = $GLOBALS['db']->getOne($sql2);

                /* 当前广告所产生的已完成的有效订单 */
                $sql3 = "SELECT COUNT(order_id) FROM " . $GLOBALS['ecs']->table('order_info') .
                    " WHERE from_ad    = '$rows[ad_id]'" .
                    " AND referer = '$rows[referer]' " . order_query_sql('finished');
                $rows['order_confirm'] = $GLOBALS['db']->getOne($sql3);

                $ads_stats[] = $rows;
            }
            $this->assign('ads_stats', $ads_stats);

            /* 站外JS投放商品的统计数据 */
            $goods_stats = array();
            $goods_sql = "SELECT from_ad, referer, clicks FROM " . $GLOBALS['ecs']->table('adsense') .
                " WHERE from_ad = '-1' ORDER by referer DESC";
            $goods_res = $GLOBALS['db']->query($goods_sql);
            foreach ($goods_res as $rows2) {
                /* 获取当前广告所产生的订单总数 */
                $rows2['referer'] = addslashes($rows2['referer']);
                $rows2['order_num'] = $GLOBALS['db']->getOne("SELECT COUNT(order_id) FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE referer='$rows2[referer]'");

                /* 当前广告所产生的已完成的有效订单 */

                $sql = "SELECT COUNT(order_id) FROM " . $GLOBALS['ecs']->table('order_info') .
                    " WHERE referer='$rows2[referer]'" . order_query_sql('finished');
                $rows2['order_confirm'] = $GLOBALS['db']->getOne($sql);

                $rows2['ad_name'] = $GLOBALS['_LANG']['adsense_js_goods'];
                $goods_stats[] = $rows2;
            }
            if ($_REQUEST['act'] == 'download') {
                header("Content-type: application/vnd.ms-excel; charset=utf-8");
                header("Content-Disposition: attachment; filename=ad_statistics.xls");
                $data = "{$GLOBALS['_LANG']['adsense_name']}\t{$GLOBALS['_LANG']['cleck_referer']}\t{$GLOBALS['_LANG']['click_count']}\t{$GLOBALS['_LANG']['confirm_order']}\t{$GLOBALS['_LANG']['gen_order_amount']}\n";
                $res = array_merge($goods_stats, $ads_stats);
                foreach ($res as $row) {
                    $data .= "$row[ad_name]\t$row[referer]\t$row[clicks]\t$row[order_confirm]\t$row[order_num]\n";
                }
                echo ecs_iconv(EC_CHARSET, 'GB2312', $data);

            }
            $this->assign('goods_stats', $goods_stats);

            /* 赋值给模板 */
            $this->assign('action_link', array('href' => 'ads.php?act=list', 'text' => $GLOBALS['_LANG']['ad_list']));
            $this->assign('action_link2', array('href' => 'adsense.php?act=download', 'text' => $GLOBALS['_LANG']['download_ad_statistics']));
            $this->assign('ur_here', $GLOBALS['_LANG']['adsense_js_stats']);
            $this->assign('lang', $GLOBALS['_LANG']);

            /* 显示页面 */
            assign_query_info();
            return $this->display('adsense.view.php');
        }
    }
}
