<?php

namespace app\shop\controller;

/**
 * 拍卖前台文件
 */
class Auction extends Init
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
        //-- 拍卖活动列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 取得拍卖活动总数 */
            $count = $this->auction_count();
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
            if (!$GLOBALS['smarty']->is_cached('auction_list', $cache_id)) {
                if ($count > 0) {
                    /* 取得当前页的拍卖活动 */
                    $auction_list = $this->auction_list($size, $page);
                    $this->assign('auction_list', $auction_list);

                    /* 设置分页链接 */
                    $pager = get_pager('auction.php', array('act' => 'list'), $count, $page, $size);
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
                $this->assign('feed_url', ($GLOBALS['_CFG']['rewrite'] == 1) ? "feed-typeauction.xml" : 'feed.php?type=auction'); // RSS URL

                assign_dynamic('auction_list');
            }

            /* 显示模板 */
            return $this->fetch('auction_list', $cache_id);
        }

        /*------------------------------------------------------ */
        //-- 拍卖商品 --> 商品详情
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'view') {
            /* 取得参数：拍卖活动id */
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if ($id <= 0) {
                return $this->redirect('/');

            }

            /* 取得拍卖活动信息 */
            $auction = auction_info($id);
            if (empty($auction)) {
                return $this->redirect('/');

            }

            /* 缓存id：语言，拍卖活动id，状态，如果是进行中，还要最后出价的时间（如果有的话） */
            $cache_id = $GLOBALS['_CFG']['lang'] . '-' . $id . '-' . $auction['status_no'];
            if ($auction['status_no'] == UNDER_WAY) {
                if (isset($auction['last_bid'])) {
                    $cache_id = $cache_id . '-' . $auction['last_bid']['bid_time'];
                }
            } elseif ($auction['status_no'] == FINISHED && $auction['last_bid']['bid_user'] == $_SESSION['user_id']
                && $auction['order_count'] == 0) {
                $auction['is_winner'] = 1;
                $cache_id = $cache_id . '-' . $auction['last_bid']['bid_time'] . '-1';
            }

            $cache_id = sprintf('%X', crc32($cache_id));

            /* 如果没有缓存，生成缓存 */
            if (!$GLOBALS['smarty']->is_cached('auction', $cache_id)) {
                //取货品信息
                if ($auction['product_id'] > 0) {
                    $goods_specifications = get_specifications_list($auction['goods_id']);

                    $good_products = get_good_products($auction['goods_id'], 'AND product_id = ' . $auction['product_id']);

                    $_good_products = explode('|', $good_products[0]['goods_attr']);
                    $products_info = '';
                    foreach ($_good_products as $value) {
                        $products_info .= ' ' . $goods_specifications[$value]['attr_name'] . '：' . $goods_specifications[$value]['attr_value'];
                    }
                    $this->assign('products_info', $products_info);
                    unset($goods_specifications, $good_products, $_good_products, $products_info);
                }

                $auction['gmt_end_time'] = local_strtotime($auction['end_time']);
                $this->assign('auction', $auction);

                /* 取得拍卖商品信息 */
                $goods_id = $auction['goods_id'];
                $goods = goods_info($goods_id);
                if (empty($goods)) {
                    return $this->redirect('/');

                }
                $goods['url'] = build_uri('goods', array('gid' => $goods_id), $goods['goods_name']);
                $this->assign('auction_goods', $goods);

                /* 出价记录 */
                $this->assign('auction_log', auction_log($id));

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

                assign_dynamic('auction');
            }

            //更新商品点击次数
            $sql = 'UPDATE ' . $GLOBALS['ecs']->table('goods') . ' SET click_count = click_count + 1 ' .
                "WHERE goods_id = '" . $auction['goods_id'] . "'";
            $GLOBALS['db']->query($sql);

            $this->assign('now_time', gmtime());           // 当前系统时间
            return $this->fetch('auction', $cache_id);
        }

        /*------------------------------------------------------ */
        //-- 拍卖商品 --> 出价
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bid') {
            load_helper('order');

            /* 取得参数：拍卖活动id */
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if ($id <= 0) {
                return $this->redirect('/');

            }

            /* 取得拍卖活动信息 */
            $auction = auction_info($id);
            if (empty($auction)) {
                return $this->redirect('/');

            }

            /* 活动是否正在进行 */
            if ($auction['status_no'] != UNDER_WAY) {
                return $this->show_message($GLOBALS['_LANG']['au_not_under_way'], '', '', 'error');
            }

            /* 是否登录 */
            $user_id = $_SESSION['user_id'];
            if ($user_id <= 0) {
                return $this->show_message($GLOBALS['_LANG']['au_bid_after_login']);
            }
            $user = user_info($user_id);

            /* 取得出价 */
            $bid_price = isset($_POST['price']) ? round(floatval($_POST['price']), 2) : 0;
            if ($bid_price <= 0) {
                return $this->show_message($GLOBALS['_LANG']['au_bid_price_error'], '', '', 'error');
            }

            /* 如果有一口价且出价大于等于一口价，则按一口价算 */
            $is_ok = false; // 出价是否ok
            if ($auction['end_price'] > 0) {
                if ($bid_price >= $auction['end_price']) {
                    $bid_price = $auction['end_price'];
                    $is_ok = true;
                }
            }

            /* 出价是否有效：区分第一次和非第一次 */
            if (!$is_ok) {
                if ($auction['bid_user_count'] == 0) {
                    /* 第一次要大于等于起拍价 */
                    $min_price = $auction['start_price'];
                } else {
                    /* 非第一次出价要大于等于最高价加上加价幅度，但不能超过一口价 */
                    $min_price = $auction['last_bid']['bid_price'] + $auction['amplitude'];
                    if ($auction['end_price'] > 0) {
                        $min_price = min($min_price, $auction['end_price']);
                    }
                }

                if ($bid_price < $min_price) {
                    return $this->show_message(sprintf($GLOBALS['_LANG']['au_your_lowest_price'], price_format($min_price, false)), '', '', 'error');
                }
            }

            /* 检查联系两次拍卖人是否相同 */
            if ($auction['last_bid']['bid_user'] == $user_id && $bid_price != $auction['end_price']) {
                return $this->show_message($GLOBALS['_LANG']['au_bid_repeat_user'], '', '', 'error');
            }

            /* 是否需要保证金 */
            if ($auction['deposit'] > 0) {
                /* 可用资金够吗 */
                if ($user['user_money'] < $auction['deposit']) {
                    return $this->show_message($GLOBALS['_LANG']['au_user_money_short'], '', '', 'error');
                }

                /* 如果不是第一个出价，解冻上一个用户的保证金 */
                if ($auction['bid_user_count'] > 0) {
                    log_account_change(
                        $auction['last_bid']['bid_user'],
                        $auction['deposit'],
                        (-1) * $auction['deposit'],
                        0,
                        0,
                        sprintf($GLOBALS['_LANG']['au_unfreeze_deposit'], $auction['act_name'])
                    );
                }

                /* 冻结当前用户的保证金 */
                log_account_change(
                    $user_id,
                    (-1) * $auction['deposit'],
                    $auction['deposit'],
                    0,
                    0,
                    sprintf($GLOBALS['_LANG']['au_freeze_deposit'], $auction['act_name'])
                );
            }

            /* 插入出价记录 */
            $auction_log = array(
                'act_id' => $id,
                'bid_user' => $user_id,
                'bid_price' => $bid_price,
                'bid_time' => gmtime()
            );
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('auction_log'), $auction_log, 'INSERT');

            /* 出价是否等于一口价 */
            if ($bid_price == $auction['end_price']) {
                /* 结束拍卖活动 */
                $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_activity') . " SET is_finished = 1 WHERE act_id = '$id' LIMIT 1";
                $GLOBALS['db']->query($sql);
            }

            /* 跳转到活动详情页 */
            return $this->redirect('auction.php?act=view&id=$id');

        }

        /*------------------------------------------------------ */
        //-- 拍卖商品 --> 购买
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'buy') {
            /* 查询：取得参数：拍卖活动id */
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if ($id <= 0) {
                return $this->redirect('/');

            }

            /* 查询：取得拍卖活动信息 */
            $auction = auction_info($id);
            if (empty($auction)) {
                return $this->redirect('/');

            }

            /* 查询：活动是否已结束 */
            if ($auction['status_no'] != FINISHED) {
                return $this->show_message($GLOBALS['_LANG']['au_not_finished'], '', '', 'error');
            }

            /* 查询：有人出价吗 */
            if ($auction['bid_user_count'] <= 0) {
                return $this->show_message($GLOBALS['_LANG']['au_no_bid'], '', '', 'error');
            }

            /* 查询：是否已经有订单 */
            if ($auction['order_count'] > 0) {
                return $this->show_message($GLOBALS['_LANG']['au_order_placed']);
            }

            /* 查询：是否登录 */
            $user_id = $_SESSION['user_id'];
            if ($user_id <= 0) {
                return $this->show_message($GLOBALS['_LANG']['au_buy_after_login']);
            }

            /* 查询：最后出价的是该用户吗 */
            if ($auction['last_bid']['bid_user'] != $user_id) {
                return $this->show_message($GLOBALS['_LANG']['au_final_bid_not_you'], '', '', 'error');
            }

            /* 查询：取得商品信息 */
            $goods = goods_info($auction['goods_id']);

            /* 查询：处理规格属性 */
            $goods_attr = '';
            $goods_attr_id = '';
            if ($auction['product_id'] > 0) {
                $product_info = get_good_products($auction['goods_id'], 'AND product_id = ' . $auction['product_id']);

                $goods_attr_id = str_replace('|', ',', $product_info[0]['goods_attr']);

                $attr_list = array();
                $sql = "SELECT a.attr_name, g.attr_value " .
                    "FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS g, " .
                    $GLOBALS['ecs']->table('attribute') . " AS a " .
                    "WHERE g.attr_id = a.attr_id " .
                    "AND g.goods_attr_id " . db_create_in($goods_attr_id);
                $res = $GLOBALS['db']->query($sql);
                foreach ($res as $row) {
                    $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
                }
                $goods_attr = join(chr(13) . chr(10), $attr_list);
            } else {
                $auction['product_id'] = 0;
            }

            /* 清空购物车中所有拍卖商品 */
            load_helper('order');
            clear_cart(CART_AUCTION_GOODS);

            /* 加入购物车 */
            $cart = array(
                'user_id' => $user_id,
                'session_id' => SESS_ID,
                'goods_id' => $auction['goods_id'],
                'goods_sn' => addslashes($goods['goods_sn']),
                'goods_name' => addslashes($goods['goods_name']),
                'market_price' => $goods['market_price'],
                'goods_price' => $auction['last_bid']['bid_price'],
                'goods_number' => 1,
                'goods_attr' => $goods_attr,
                'goods_attr_id' => $goods_attr_id,
                'is_real' => $goods['is_real'],
                'extension_code' => addslashes($goods['extension_code']),
                'parent_id' => 0,
                'rec_type' => CART_AUCTION_GOODS,
                'is_gift' => 0
            );
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $cart, 'INSERT');

            /* 记录购物流程类型：团购 */
            $_SESSION['flow_type'] = CART_AUCTION_GOODS;
            $_SESSION['extension_code'] = 'auction';
            $_SESSION['extension_id'] = $id;

            /* 进入收货人页面 */
            return $this->redirect('flow.php?step=consignee');

        }
    }

    /**
     * 取得拍卖活动数量
     * @return  int
     */
    private function auction_count()
    {
        $now = gmtime();
        $sql = "SELECT COUNT(*) " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') .
            "WHERE act_type = '" . GAT_AUCTION . "' " .
            "AND start_time <= '$now' AND end_time >= '$now' AND is_finished < 2";

        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * 取得某页的拍卖活动
     * @param int $size 每页记录数
     * @param int $page 当前页
     * @return  array
     */
    private function auction_list($size, $page)
    {
        $auction_list = array();
        $auction_list['finished'] = $auction_list['finished'] = array();

        $now = gmtime();
        $sql = "SELECT a.*, IFNULL(g.goods_thumb, '') AS goods_thumb " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS a " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON a.goods_id = g.goods_id " .
            "WHERE a.act_type = '" . GAT_AUCTION . "' " .
            "AND a.start_time <= '$now' AND a.end_time >= '$now' AND a.is_finished < 2 ORDER BY a.act_id DESC";
        $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);
        foreach ($res as $row) {
            $ext_info = unserialize($row['ext_info']);
            $auction = array_merge($row, $ext_info);
            $auction['status_no'] = auction_status($auction);

            $auction['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $auction['start_time']);
            $auction['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $auction['end_time']);
            $auction['formated_start_price'] = price_format($auction['start_price']);
            $auction['formated_end_price'] = price_format($auction['end_price']);
            $auction['formated_deposit'] = price_format($auction['deposit']);
            $auction['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $auction['url'] = build_uri('auction', array('auid' => $auction['act_id']));

            if ($auction['status_no'] < 2) {
                $auction_list['under_way'][] = $auction;
            } else {
                $auction_list['finished'][] = $auction;
            }
        }

        $auction_list = @array_merge($auction_list['under_way'], $auction_list['finished']);

        return $auction_list;
    }
}
