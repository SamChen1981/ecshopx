<?php

namespace app\shop\controller;

/**
 * 活动列表
 * Class Activity
 * @package app\shop\controller
 */
class Activity extends Init
{
    public function index()
    {
        load_helper('order');
        load_helper('transaction');

        /* 载入语言文件 */
        load_lang('shopping_flow');
        load_lang('user');

        assign_template();
        assign_dynamic('activity');
        $position = assign_ur_here(0, $GLOBALS['_LANG']['shopping_activity']);
        $GLOBALS['smarty']->assign('page_title', $position['title']);    // 页面标题
        $GLOBALS['smarty']->assign('ur_here', $position['ur_here']);  // 当前位置

        /* 取得用户等级 */
        $user_rank_list = array();
        $user_rank_list[0] = $GLOBALS['_LANG']['not_user'];
        $sql = "SELECT rank_id, rank_name FROM " . $GLOBALS['ecs']->table('user_rank');
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            $user_rank_list[$row['rank_id']] = $row['rank_name'];
        }

        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('favourable_activity') . " ORDER BY `sort_order` ASC,`end_time` DESC";
        $res = $GLOBALS['db']->query($sql);

        $list = array();
        foreach ($res as $row) {
            $row['start_time'] = local_date('Y-m-d H:i', $row['start_time']);
            $row['end_time'] = local_date('Y-m-d H:i', $row['end_time']);

            //享受优惠会员等级
            $user_rank = explode(',', $row['user_rank']);
            $row['user_rank'] = array();
            foreach ($user_rank as $val) {
                if (isset($user_rank_list[$val])) {
                    $row['user_rank'][] = $user_rank_list[$val];
                }
            }

            //优惠范围类型、内容
            if ($row['act_range'] != FAR_ALL && !empty($row['act_range_ext'])) {
                if ($row['act_range'] == FAR_CATEGORY) {
                    $row['act_range'] = $GLOBALS['_LANG']['far_category'];
                    $row['program'] = 'category.php?id=';
                    $sql = "SELECT cat_id AS id, cat_name AS name FROM " . $GLOBALS['ecs']->table('category') .
                        " WHERE cat_id " . db_create_in($row['act_range_ext']);
                } elseif ($row['act_range'] == FAR_BRAND) {
                    $row['act_range'] = $GLOBALS['_LANG']['far_brand'];
                    $row['program'] = 'brand.php?id=';
                    $sql = "SELECT brand_id AS id, brand_name AS name FROM " . $GLOBALS['ecs']->table('brand') .
                        " WHERE brand_id " . db_create_in($row['act_range_ext']);
                } else {
                    $row['act_range'] = $GLOBALS['_LANG']['far_goods'];
                    $row['program'] = 'goods.php?id=';
                    $sql = "SELECT goods_id AS id, goods_name AS name FROM " . $GLOBALS['ecs']->table('goods') .
                        " WHERE goods_id " . db_create_in($row['act_range_ext']);
                }
                $act_range_ext = $GLOBALS['db']->getAll($sql);
                $row['act_range_ext'] = $act_range_ext;
            } else {
                $row['act_range'] = $GLOBALS['_LANG']['far_all'];
            }

            //优惠方式
            switch ($row['act_type']) {
                case 0:
                    $row['act_type'] = $GLOBALS['_LANG']['fat_goods'];
                    $row['gift'] = unserialize($row['gift']);
                    if (is_array($row['gift'])) {
                        foreach ($row['gift'] as $k => $v) {
                            $row['gift'][$k]['thumb'] = get_image_path($v['id'], $GLOBALS['db']->getOne("SELECT goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '" . $v['id'] . "'"), true);
                        }
                    }
                    break;
                case 1:
                    $row['act_type'] = $GLOBALS['_LANG']['fat_price'];
                    $row['act_type_ext'] .= $GLOBALS['_LANG']['unit_yuan'];
                    $row['gift'] = array();
                    break;
                case 2:
                    $row['act_type'] = $GLOBALS['_LANG']['fat_discount'];
                    $row['act_type_ext'] .= "%";
                    $row['gift'] = array();
                    break;
            }

            $list[] = $row;
        }

        $GLOBALS['smarty']->assign('list', $list);

        $GLOBALS['smarty']->assign('helps', get_shop_help());       // 网店帮助
        $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);

        $GLOBALS['smarty']->assign('feed_url', ($GLOBALS['_CFG']['rewrite'] == 1) ? "feed-typeactivity.xml" : 'feed.php?type=activity'); // RSS URL
        $GLOBALS['smarty']->display('activity.dwt');
    }
}
