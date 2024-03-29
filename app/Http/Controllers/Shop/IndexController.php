<?php

namespace app\shop\controller;

/**
 * 首页文件
 */
class Index extends Init
{
    public function index()
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

        $uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|cldc|midp|mobile)/i";

        if (($ua == '' || preg_match($uachar, $ua)) && !strpos(strtolower($_SERVER['REQUEST_URI']), 'wap')) {
            $Loaction = 'h5/';

            if (!empty($Loaction)) {
                return $this->redirect($Loaction);


            }
        }

        //判断是否有ajax请求
        $act = !empty($_GET['act']) ? $_GET['act'] : '';
        if ($act == 'cat_rec') {
            $rec_array = array(1 => 'best', 2 => 'new', 3 => 'hot');
            $rec_type = !empty($_REQUEST['rec_type']) ? intval($_REQUEST['rec_type']) : '1';
            $cat_id = !empty($_REQUEST['cid']) ? intval($_REQUEST['cid']) : '0';
            $result = array('error' => 0, 'content' => '', 'type' => $rec_type, 'cat_id' => $cat_id);

            $children = get_children($cat_id);
            $this->assign($rec_array[$rec_type] . '_goods', get_category_recommend_goods($rec_array[$rec_type], $children));    // 推荐商品
            $this->assign('cat_rec_sign', 1);
            $result['content'] = $GLOBALS['smarty']->fetch('library/recommend_' . $rec_array[$rec_type] . '');
            die(json_encode($result));
        }

        /*------------------------------------------------------ */
        //-- 判断是否存在缓存，如果存在则调用缓存，反之读取相应内容
        /*------------------------------------------------------ */
        /* 缓存编号 */
        $cache_id = sprintf('%X', crc32($_SESSION['user_rank'] . '-' . $GLOBALS['_CFG']['lang']));

        if (!$GLOBALS['smarty']->is_cached('index', $cache_id)) {
            $this->assign_template();

            $position = assign_ur_here();
            $this->assign('page_title', $position['title']);    // 页面标题
            $this->assign('ur_here', $position['ur_here']);  // 当前位置

            /* meta information */
            $this->assign('keywords', htmlspecialchars($GLOBALS['_CFG']['shop_keywords']));
            $this->assign('description', htmlspecialchars($GLOBALS['_CFG']['shop_desc']));
            $this->assign('flash_theme', $GLOBALS['_CFG']['flash_theme']);  // Flash轮播图片模板

            $this->assign('feed_url', ($GLOBALS['_CFG']['rewrite'] == 1) ? 'feed.xml' : 'feed.php'); // RSS URL

            $this->assign('categories', get_categories_tree()); // 分类树
            $this->assign('helps', get_shop_help());       // 网店帮助
            $this->assign('top_goods', get_top10());           // 销售排行

            $this->assign('best_goods', get_recommend_goods('best'));    // 推荐商品
            $this->assign('new_goods', get_recommend_goods('new'));     // 最新商品
            $this->assign('hot_goods', get_recommend_goods('hot'));     // 热点文章
            $this->assign('promotion_goods', get_promote_goods()); // 特价商品
            $this->assign('brand_list', get_brands());
            $this->assign('promotion_info', get_promotion_info()); // 增加一个动态显示所有促销信息的标签栏

            $this->assign('invoice_list', $this->index_get_invoice_query());  // 发货查询
            $this->assign('new_articles', $this->index_get_new_articles());   // 最新文章
            $this->assign('group_buy_goods', $this->index_get_group_buy());      // 团购商品
            $this->assign('auction_list', $this->index_get_auction());        // 拍卖活动
            $this->assign('shop_notice', $GLOBALS['_CFG']['shop_notice']);       // 商店公告

            /* 首页主广告设置 */
            $this->assign('index_ad', $GLOBALS['_CFG']['index_ad']);
            if ($GLOBALS['_CFG']['index_ad'] == 'cus') {
                $sql = 'SELECT ad_type, content, url FROM ' . $GLOBALS['ecs']->table("ad_custom") . ' WHERE ad_status = 1';
                $ad = $GLOBALS['db']->getRow($sql, true);
                $this->assign('ad', $ad);
            }

            /* links */
            $links = $this->index_get_links();
            $this->assign('img_links', $links['img']);
            $this->assign('txt_links', $links['txt']);
            $this->assign('data_dir', DATA_DIR);       // 数据目录

            /* 首页推荐分类 */
            $cat_recommend_res = $GLOBALS['db']->getAll("SELECT c.cat_id, c.cat_name, cr.recommend_type FROM " . $GLOBALS['ecs']->table("cat_recommend") . " AS cr INNER JOIN " . $GLOBALS['ecs']->table("category") . " AS c ON cr.cat_id=c.cat_id");
            if (!empty($cat_recommend_res)) {
                $cat_rec_array = array();
                foreach ($cat_recommend_res as $cat_recommend_data) {
                    $cat_rec[$cat_recommend_data['recommend_type']][] = array('cat_id' => $cat_recommend_data['cat_id'], 'cat_name' => $cat_recommend_data['cat_name']);
                }
                $this->assign('cat_rec', $cat_rec);
            }

            /* 页面中的动态内容 */
            assign_dynamic('index');
        }

        return $this->fetch('index', $cache_id);
    }

    /**
     * 调用发货单查询
     *
     * @access  private
     * @return  array
     */
    private function index_get_invoice_query()
    {
        $sql = 'SELECT o.order_sn, o.invoice_no, s.shipping_code FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o' .
            ' LEFT JOIN ' . $GLOBALS['ecs']->table('shipping') . ' AS s ON s.shipping_id = o.shipping_id' .
            " WHERE invoice_no > '' AND shipping_status = " . SS_SHIPPED .
            ' ORDER BY shipping_time DESC LIMIT 10';
        $all = $GLOBALS['db']->getAll($sql);

        foreach ($all as $key => $row) {
            $plugin = ROOT_PATH . 'includes/modules/shipping/' . $row['shipping_code'] . '.php';

            if (file_exists($plugin)) {
                include_once($plugin);

                $shipping = new $row['shipping_code'];
                $all[$key]['invoice_no'] = $shipping->query((string)$row['invoice_no']);
            }
        }

        clearstatcache();

        return $all;
    }

    /**
     * 获得最新的文章列表。
     *
     * @access  private
     * @return  array
     */
    private function index_get_new_articles()
    {
        $sql = 'SELECT a.article_id, a.title, ac.cat_name, a.add_time, a.file_url, a.open_type, ac.cat_id, ac.cat_name ' .
            ' FROM ' . $GLOBALS['ecs']->table('article') . ' AS a, ' .
            $GLOBALS['ecs']->table('article_cat') . ' AS ac' .
            ' WHERE a.is_open = 1 AND a.cat_id = ac.cat_id AND ac.cat_type = 1' .
            ' ORDER BY a.article_type DESC, a.add_time DESC LIMIT ' . $GLOBALS['_CFG']['article_number'];
        $res = $GLOBALS['db']->getAll($sql);

        $arr = array();
        foreach ($res as $idx => $row) {
            $arr[$idx]['id'] = $row['article_id'];
            $arr[$idx]['title'] = $row['title'];
            $arr[$idx]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ?
                sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $row['title'];
            $arr[$idx]['cat_name'] = $row['cat_name'];
            $arr[$idx]['add_time'] = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);
            $arr[$idx]['url'] = $row['open_type'] != 1 ?
                build_uri('article', array('aid' => $row['article_id']), $row['title']) : trim($row['file_url']);
            $arr[$idx]['cat_url'] = build_uri('article_cat', array('acid' => $row['cat_id']), $row['cat_name']);
        }

        return $arr;
    }

    /**
     * 获得最新的团购活动
     *
     * @access  private
     * @return  array
     */
    private function index_get_group_buy()
    {
        $time = gmtime();
        $limit = 10; // TODO BY LANCE TEST get_library_number('group_buy', 'index');

        $group_buy_list = array();
        if ($limit > 0) {
            $sql = 'SELECT gb.act_id AS group_buy_id, gb.goods_id, gb.ext_info, gb.goods_name, g.goods_thumb, g.goods_img ' .
                'FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' AS gb, ' .
                $GLOBALS['ecs']->table('goods') . ' AS g ' .
                "WHERE gb.act_type = '" . GAT_GROUP_BUY . "' " .
                "AND g.goods_id = gb.goods_id " .
                "AND gb.start_time <= '" . $time . "' " .
                "AND gb.end_time >= '" . $time . "' " .
                "AND g.is_delete = 0 " .
                "ORDER BY gb.act_id DESC " .
                "LIMIT $limit";
            $res = $GLOBALS['db']->query($sql);

            foreach ($res as $row) {
                /* 如果缩略图为空，使用默认图片 */
                $row['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
                $row['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);

                /* 根据价格阶梯，计算最低价 */
                $ext_info = unserialize($row['ext_info']);
                $price_ladder = $ext_info['price_ladder'];
                if (!is_array($price_ladder) || empty($price_ladder)) {
                    $row['last_price'] = price_format(0);
                } else {
                    foreach ($price_ladder as $amount_price) {
                        $price_ladder[$amount_price['amount']] = $amount_price['price'];
                    }
                }
                ksort($price_ladder);
                $row['last_price'] = price_format(end($price_ladder));
                $row['url'] = build_uri('group_buy', array('gbid' => $row['group_buy_id']));
                $row['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                    sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                $row['short_style_name'] = add_style($row['short_name'], '');
                $group_buy_list[] = $row;
            }
        }

        return $group_buy_list;
    }

    /**
     * 取得拍卖活动列表
     * @return  array
     */
    private function index_get_auction()
    {
        $now = gmtime();
        $limit = 10; // TODO BY LANCE TEST get_library_number('auction', 'index');
        $sql = "SELECT a.act_id, a.goods_id, a.goods_name, a.ext_info, g.goods_thumb " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS a," .
            $GLOBALS['ecs']->table('goods') . " AS g" .
            " WHERE a.goods_id = g.goods_id" .
            " AND a.act_type = '" . GAT_AUCTION . "'" .
            " AND a.is_finished = 0" .
            " AND a.start_time <= '$now'" .
            " AND a.end_time >= '$now'" .
            " AND g.is_delete = 0" .
            " ORDER BY a.start_time DESC" .
            " LIMIT $limit";
        $res = $GLOBALS['db']->query($sql);

        $list = array();
        foreach ($res as $row) {
            $ext_info = unserialize($row['ext_info']);
            $arr = array_merge($row, $ext_info);
            $arr['formated_start_price'] = price_format($arr['start_price']);
            $arr['formated_end_price'] = price_format($arr['end_price']);
            $arr['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr['url'] = build_uri('auction', array('auid' => $arr['act_id']));
            $arr['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                sub_str($arr['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $arr['goods_name'];
            $arr['short_style_name'] = add_style($arr['short_name'], '');
            $list[] = $arr;
        }

        return $list;
    }

    /**
     * 获得所有的友情链接
     *
     * @access  private
     * @return  array
     */
    private function index_get_links()
    {
        $sql = 'SELECT link_logo, link_name, link_url FROM ' . $GLOBALS['ecs']->table('friend_link') . ' ORDER BY show_order';
        $res = $GLOBALS['db']->getAll($sql);

        $links['img'] = $links['txt'] = array();

        foreach ($res as $row) {
            if (!empty($row['link_logo'])) {
                $links['img'][] = array('name' => $row['link_name'],
                    'url' => $row['link_url'],
                    'logo' => $row['link_logo']);
            } else {
                $links['txt'][] = array('name' => $row['link_name'],
                    'url' => $row['link_url']);
            }
        }

        return $links;
    }
}
