<?php

namespace app\shop\controller;

/**
 * 团购商品前台文件
 */
class GroupBuy extends Init
{
    public function index()
    {
        /*------------------------------------------------------ */
        //-- act 操作项的初始化
        /*------------------------------------------------------ */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        }

        /*------------------------------------------------------ */
        //-- 团购商品 --> 团购活动商品列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 取得团购活动总数 */
            $count = $this->group_buy_count();
            if ($count > 0) {
                /* 取得每页记录数 */
                $size = isset($GLOBALS['_CFG']['page_size']) && intval($GLOBALS['_CFG']['page_size']) > 0 ? intval($GLOBALS['_CFG']['page_size']) : 10;

                /* 计算总页数 */
                $page_count = ceil($count / $size);

                /* 取得当前页 */
                $page = isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
                $page = $page > $page_count ? $page_count : $page;

                /* 缓存id：语言 - 每页记录数 - 当前页 */
                $cache_id = $GLOBALS['_CFG']['lang'] . '-' . $size . '-' . $page;
                $cache_id = sprintf('%X', crc32($cache_id));
            } else {
                /* 缓存id：语言 */
                $cache_id = $GLOBALS['_CFG']['lang'];
                $cache_id = sprintf('%X', crc32($cache_id));
            }

            /* 如果没有缓存，生成缓存 */
            if (!$GLOBALS['smarty']->is_cached('group_buy_list', $cache_id)) {
                if ($count > 0) {
                    /* 取得当前页的团购活动 */
                    $gb_list = $this->group_buy_list($size, $page);
                    $this->assign('gb_list', $gb_list);

                    /* 设置分页链接 */
                    $pager = get_pager('group_buy.php', array('act' => 'list'), $count, $page, $size);
                    $this->assign('pager', $pager);
                }

                /* 模板赋值 */
                $this->assign('cfg', $GLOBALS['_CFG']);
                $this->assign_template();
                $position = assign_ur_here();
                $this->assign('page_title', $position['title']);    // 页面标题
                $this->assign('ur_here', $position['ur_here']);  // 当前位置
                $this->assign('categories', get_categories_tree()); // 分类树
                $this->assign('helps', get_shop_help());       // 网店帮助
                $this->assign('top_goods', get_top10());           // 销售排行
                $this->assign('promotion_info', get_promotion_info());
                $this->assign('feed_url', ($GLOBALS['_CFG']['rewrite'] == 1) ? "feed-typegroup_buy.xml" : 'feed.php?type=group_buy'); // RSS URL

                assign_dynamic('group_buy_list');
            }

            /* 显示模板 */
            return $this->fetch('group_buy_list', $cache_id);
        }

        /*------------------------------------------------------ */
        //-- 团购商品 --> 商品详情
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'view') {
            /* 取得参数：团购活动id */
            $group_buy_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if ($group_buy_id <= 0) {
                return $this->redirect('/');

            }

            /* 取得团购活动信息 */
            $group_buy = group_buy_info($group_buy_id);

            if (empty($group_buy)) {
                return $this->redirect('/');

            }

            /* 缓存id：语言，团购活动id，状态，（如果是进行中）当前数量和是否登录 */
            $cache_id = $GLOBALS['_CFG']['lang'] . '-' . $group_buy_id . '-' . $group_buy['status'];
            if ($group_buy['status'] == GBS_UNDER_WAY) {
                $cache_id = $cache_id . '-' . $group_buy['valid_goods'] . '-' . intval($_SESSION['user_id'] > 0);
            }
            $cache_id = sprintf('%X', crc32($cache_id));

            /* 如果没有缓存，生成缓存 */
            if (!$GLOBALS['smarty']->is_cached('group_buy_goods', $cache_id)) {
                $group_buy['gmt_end_date'] = $group_buy['end_date'];
                $this->assign('group_buy', $group_buy);

                /* 取得团购商品信息 */
                $goods_id = $group_buy['goods_id'];
                $goods = goods_info($goods_id);
                if (empty($goods)) {
                    return $this->redirect('/');

                }
                $goods['url'] = build_uri('goods', array('gid' => $goods_id), $goods['goods_name']);
                $this->assign('gb_goods', $goods);

                /* 取得商品的规格 */
                $properties = get_goods_properties($goods_id);
                $this->assign('specification', $properties['spe']); // 商品规格

                //模板赋值
                $this->assign('cfg', $GLOBALS['_CFG']);
                $this->assign_template();

                $position = assign_ur_here(0, $goods['goods_name']);
                $this->assign('page_title', $position['title']);    // 页面标题
                $this->assign('ur_here', $position['ur_here']);  // 当前位置

                $this->assign('categories', get_categories_tree()); // 分类树
                $this->assign('helps', get_shop_help());       // 网店帮助
                $this->assign('top_goods', get_top10());           // 销售排行
                $this->assign('promotion_info', get_promotion_info());
                assign_dynamic('group_buy_goods');
            }

            //更新商品点击次数
            $sql = 'UPDATE ' . $GLOBALS['ecs']->table('goods') . ' SET click_count = click_count + 1 ' .
                "WHERE goods_id = '" . $group_buy['goods_id'] . "'";
            $GLOBALS['db']->query($sql);

            $this->assign('now_time', gmtime());           // 当前系统时间
            return $this->fetch('group_buy_goods', $cache_id);
        }

        /*------------------------------------------------------ */
        //-- 团购商品 --> 购买
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'buy') {
            /* 查询：判断是否登录 */
            if ($_SESSION['user_id'] <= 0) {
                return $this->show_message($GLOBALS['_LANG']['gb_error_login'], '', '', 'error');
            }

            /* 查询：取得参数：团购活动id */
            $group_buy_id = isset($_POST['group_buy_id']) ? intval($_POST['group_buy_id']) : 0;
            if ($group_buy_id <= 0) {
                return $this->redirect('/');

            }

            /* 查询：取得数量 */
            $number = isset($_POST['number']) ? intval($_POST['number']) : 1;
            $number = $number < 1 ? 1 : $number;

            /* 查询：取得团购活动信息 */
            $group_buy = group_buy_info($group_buy_id, $number);

            if (empty($group_buy)) {
                return $this->redirect('/');

            }

            /* 查询：检查团购活动是否是进行中 */
            if ($group_buy['status'] != GBS_UNDER_WAY) {
                return $this->show_message($GLOBALS['_LANG']['gb_error_status'], '', '', 'error');
            }

            /* 查询：取得团购商品信息 */
            $goods = goods_info($group_buy['goods_id']);
            if (empty($goods)) {
                return $this->redirect('/');

            }

            /* 查询：判断数量是否足够 */
            if (($group_buy['restrict_amount'] > 0 && $number > ($group_buy['restrict_amount'] - $group_buy['valid_goods'])) || $number > $goods['goods_number']) {
                return $this->show_message($GLOBALS['_LANG']['gb_error_goods_lacking'], '', '', 'error');
            }

            /* 查询：取得规格 */
            $specs = '';
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'spec_') !== false) {
                    $specs .= ',' . intval($value);
                }
            }
            $specs = trim($specs, ',');

            /* 查询：如果商品有规格则取规格商品信息 配件除外 */
            if ($specs) {
                $_specs = explode(',', $specs);
                $product_info = get_products_info($goods['goods_id'], $_specs);
            }

            empty($product_info) ? $product_info = array('product_number' => 0, 'product_id' => 0) : '';

            /* 查询：判断指定规格的货品数量是否足够 */
            if ($specs && $number > $product_info['product_number']) {
                return $this->show_message($GLOBALS['_LANG']['gb_error_goods_lacking'], '', '', 'error');
            }

            /* 查询：查询规格名称和值，不考虑价格 */
            $attr_list = array();
            $sql = "SELECT a.attr_name, g.attr_value " .
                "FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS g, " .
                $GLOBALS['ecs']->table('attribute') . " AS a " .
                "WHERE g.attr_id = a.attr_id " .
                "AND g.goods_attr_id " . db_create_in($specs);
            $res = $GLOBALS['db']->query($sql);
            foreach ($res as $row) {
                $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
            }
            $goods_attr = join(chr(13) . chr(10), $attr_list);

            /* 更新：清空购物车中所有团购商品 */
            load_helper('order');
            clear_cart(CART_GROUP_BUY_GOODS);

            /* 更新：加入购物车 */
            $goods_price = $group_buy['deposit'] > 0 ? $group_buy['deposit'] : $group_buy['cur_price'];

            $cart = array(
                'user_id' => $_SESSION['user_id'],
                'session_id' => SESS_ID,
                'goods_id' => $group_buy['goods_id'],
                'product_id' => $product_info['product_id'],
                'goods_sn' => addslashes($goods['goods_sn']),
                'goods_name' => addslashes($goods['goods_name']),
                'market_price' => $goods['market_price'],
                'goods_price' => $goods_price,
                'goods_number' => $number,
                'goods_attr' => addslashes($goods_attr),
                'goods_attr_id' => $specs,
                'is_real' => $goods['is_real'],
                'extension_code' => addslashes($goods['extension_code']),
                'parent_id' => 0,
                'rec_type' => CART_GROUP_BUY_GOODS,
                'is_gift' => 0
            );
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $cart, 'INSERT');

            /* 更新：记录购物流程类型：团购 */
            $_SESSION['flow_type'] = CART_GROUP_BUY_GOODS;
            $_SESSION['extension_code'] = 'group_buy';
            $_SESSION['extension_id'] = $group_buy_id;

            /* 进入收货人页面 */
            return $this->redirect('flow.php?step=consignee');

        }
    }

    /* 取得团购活动总数 */
    private function group_buy_count()
    {
        $now = gmtime();
        $sql = "SELECT COUNT(*) " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') .
            "WHERE act_type = '" . GAT_GROUP_BUY . "' " .
            "AND start_time <= '$now' AND is_finished < 3";

        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * 取得某页的所有团购活动
     * @param int $size 每页记录数
     * @param int $page 当前页
     * @return  array
     */
    private function group_buy_list($size, $page)
    {
        /* 取得团购活动 */
        $gb_list = array();
        $now = gmtime();
        $sql = "SELECT b.*, IFNULL(g.goods_thumb, '') AS goods_thumb, b.act_id AS group_buy_id, " .
            "b.start_time AS start_date, b.end_time AS end_date " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS b " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON b.goods_id = g.goods_id " .
            "WHERE b.act_type = '" . GAT_GROUP_BUY . "' " .
            "AND b.start_time <= '$now' AND b.is_finished < 3 ORDER BY b.act_id DESC";
        $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);
        foreach ($res as $group_buy) {
            $ext_info = unserialize($group_buy['ext_info']);
            $group_buy = array_merge($group_buy, $ext_info);

            /* 格式化时间 */
            $group_buy['formated_start_date'] = local_date($GLOBALS['_CFG']['time_format'], $group_buy['start_date']);
            $group_buy['formated_end_date'] = local_date($GLOBALS['_CFG']['time_format'], $group_buy['end_date']);

            /* 格式化保证金 */
            $group_buy['formated_deposit'] = price_format($group_buy['deposit'], false);

            /* 处理价格阶梯 */
            $price_ladder = $group_buy['price_ladder'];
            if (!is_array($price_ladder) || empty($price_ladder)) {
                $price_ladder = array(array('amount' => 0, 'price' => 0));
            } else {
                foreach ($price_ladder as $key => $amount_price) {
                    $price_ladder[$key]['formated_price'] = price_format($amount_price['price']);
                }
            }
            $group_buy['price_ladder'] = $price_ladder;

            /* 处理图片 */
            if (empty($group_buy['goods_thumb'])) {
                $group_buy['goods_thumb'] = get_image_path($group_buy['goods_id'], $group_buy['goods_thumb'], true);
            }
            /* 处理链接 */
            $group_buy['url'] = build_uri('group_buy', array('gbid' => $group_buy['group_buy_id']));
            /* 加入数组 */
            $gb_list[] = $group_buy;
        }

        return $gb_list;
    }
}
