<?php

namespace app\shop\controller;

/**
 * 购物流程
 */
class Flow extends Init
{
    public function index()
    {
        load_helper('order');

        /* 载入语言文件 */
        load_lang('user');
        load_lang('shopping_flow');

        /*------------------------------------------------------ */
        //-- INPUT
        /*------------------------------------------------------ */

        if (!isset($_REQUEST['step'])) {
            $_REQUEST['step'] = "cart";
        }

        /*------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        $this->assign_template();
        assign_dynamic('flow');
        $position = assign_ur_here(0, $GLOBALS['_LANG']['shopping_flow']);
        $this->assign('page_title', $position['title']);    // 页面标题
        $this->assign('ur_here', $position['ur_here']);  // 当前位置

        $this->assign('categories', get_categories_tree()); // 分类树
        $this->assign('helps', get_shop_help());       // 网店帮助
        $this->assign('lang', $GLOBALS['_LANG']);
        $this->assign('show_marketprice', $GLOBALS['_CFG']['show_marketprice']);
        $this->assign('data_dir', DATA_DIR);       // 数据目录

        /*------------------------------------------------------ */
        //-- 添加商品到购物车
        /*------------------------------------------------------ */
        if ($_REQUEST['step'] == 'add_to_cart') {
            add_log("add_to_cart begin=" . __LINE__ . "=POST:", $_POST);
            $_POST['goods'] = strip_tags(urldecode($_POST['goods']));
            $_POST['goods'] = json_str_iconv($_POST['goods']);

            if (!empty($_REQUEST['goods_id']) && empty($_POST['goods'])) {
                if (!is_numeric($_REQUEST['goods_id']) || intval($_REQUEST['goods_id']) <= 0) {
                    return $this->redirect('/');
                }
                $goods_id = intval($_REQUEST['goods_id']);

            }

            $result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');

            if (empty($_POST['goods'])) {
                $result['error'] = 1;
                die(json_encode($result));
            }

            $goods = json_decode($_POST['goods']);
            add_log("add_to_cart==" . __LINE__ . " =goods:", $goods);
            /* 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台 */
            if (empty($goods->spec) and empty($goods->quick)) {
                $sql = "SELECT a.attr_id, a.attr_name, a.attr_type, " .
                    "g.goods_attr_id, g.attr_value, g.attr_price " .
                    'FROM ' . $GLOBALS['ecs']->table('goods_attr') . ' AS g ' .
                    'LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = g.attr_id ' .
                    "WHERE a.attr_type != 0 AND g.goods_id = '" . $goods->goods_id . "' " .
                    'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';
                add_log("add_to_cart==" . __LINE__ . " =no spec and no quick sql:" . $sql);
                $res = $GLOBALS['db']->getAll($sql);

                if (!empty($res)) {
                    $spe_arr = array();
                    foreach ($res as $row) {
                        $spe_arr[$row['attr_id']]['attr_type'] = $row['attr_type'];
                        $spe_arr[$row['attr_id']]['name'] = $row['attr_name'];
                        $spe_arr[$row['attr_id']]['attr_id'] = $row['attr_id'];
                        $spe_arr[$row['attr_id']]['values'][] = array(
                            'label' => $row['attr_value'],
                            'price' => $row['attr_price'],
                            'format_price' => price_format($row['attr_price'], false),
                            'id' => $row['goods_attr_id']);
                    }
                    $i = 0;
                    $spe_array = array();
                    foreach ($spe_arr as $row) {
                        $spe_array[] = $row;
                    }
                    $result['error'] = ERR_NEED_SELECT_ATTR;
                    $result['goods_id'] = $goods->goods_id;
                    $result['parent'] = $goods->parent;
                    $result['message'] = $spe_array;

                    die(json_encode($result));
                }
            }

            /* 更新：如果是一步购物，先清空购物车 */
            if ($GLOBALS['_CFG']['one_step_buy'] == '1') {
                clear_cart();
            }

            /* 检查：商品数量是否合法 */
            if (!is_numeric($goods->number) || intval($goods->number) <= 0) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['invalid_number'];
            } /* 更新：购物车 */
            else {
                if (!empty($goods->spec)) {
                    foreach ($goods->spec as $key => $val) {
                        $goods->spec[$key] = intval($val);
                    }
                }
                // 更新：添加到购物车
                if (addto_cart($goods->goods_id, $goods->number, $goods->spec, $goods->parent)) {
                    if ($GLOBALS['_CFG']['cart_confirm'] > 2) {
                        $result['message'] = '';
                    } else {
                        $result['message'] = $GLOBALS['_CFG']['cart_confirm'] == 1 ? $GLOBALS['_LANG']['addto_cart_success_1'] : $GLOBALS['_LANG']['addto_cart_success_2'];
                    }

                    $result['content'] = insert_cart_info();
                    $result['one_step_buy'] = $GLOBALS['_CFG']['one_step_buy'];
                } else {
                    $result['message'] = $GLOBALS['err']->last_message();
                    $result['error'] = $GLOBALS['err']->error_no;
                    $result['goods_id'] = stripslashes($goods->goods_id);
                    if (is_array($goods->spec)) {
                        $result['product_spec'] = implode(',', $goods->spec);
                    } else {
                        $result['product_spec'] = $goods->spec;
                    }
                }
            }

            $result['confirm_type'] = !empty($GLOBALS['_CFG']['cart_confirm']) ? $GLOBALS['_CFG']['cart_confirm'] : 2;
            die(json_encode($result));
        } elseif ($_REQUEST['step'] == 'link_buy') {
            $goods_id = intval($_GET['goods_id']);

            if (!cart_goods_exists($goods_id, array())) {
                addto_cart($goods_id);
            }
            return $this->redirect('flow.php');

        } elseif ($_REQUEST['step'] == 'login') {
            load_lang('user');

            $where = "session_id = '" . SESS_ID . "'";
            if ($_SESSION['user_id']) {
                $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
            }

            /*
             * 用户登录注册
             */
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $this->assign('anonymous_buy', $GLOBALS['_CFG']['anonymous_buy']);

                /* 检查是否有赠品，如果有提示登录后重新选择赠品 */
                // $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('cart') .
                //         " WHERE session_id = '" . SESS_ID . "' AND is_gift > 0";
                $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('cart') . " WHERE " . $where . " AND is_gift > 0";
                if ($GLOBALS['db']->getOne($sql) > 0) {
                    $this->assign('need_rechoose_gift', 1);
                }

                /* 检查是否需要注册码 */
                $captcha = intval($GLOBALS['_CFG']['captcha']);
                if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0) {
                    $this->assign('enabled_login_captcha', 1);
                    $this->assign('rand', mt_rand());
                }
                if ($captcha & CAPTCHA_REGISTER) {
                    $this->assign('enabled_register_captcha', 1);
                    $this->assign('rand', mt_rand());
                }
            } else {
                load_helper('passport');
                if (!empty($_POST['act']) && $_POST['act'] == 'signin') {
                    $captcha = intval($GLOBALS['_CFG']['captcha']);
                    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0) {
                        if (empty($_POST['captcha'])) {
                            return $this->show_message($GLOBALS['_LANG']['invalid_captcha']);
                        }

                        /* 检查验证码 */

                        $validator = new captcha();
                        $validator->session_word = 'captcha_login';
                        if (!$validator->check_word($_POST['captcha'])) {
                            return $this->show_message($GLOBALS['_LANG']['invalid_captcha']);
                        }
                    }

                    $_POST['password'] = isset($_POST['password']) ? trim($_POST['password']) : '';
                    if ($GLOBALS['user']->login($_POST['username'], $_POST['password'], isset($_POST['remember']))) {
                        update_cart_offline(); //离线购物车绑定会员id
                        update_user_info();  //更新用户信息
                        recalculate_price(); // 重新计算购物车中的商品价格

                        /* 检查购物车中是否有商品 没有商品则跳转到首页 */
                        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('cart') . " WHERE " . $where;
                        if ($GLOBALS['db']->getOne($sql) > 0) {
                            return $this->redirect('flow.php?step=checkout');
                        } else {
                            return $this->redirect('index.php');
                        }


                    } else {
                        $_SESSION['login_fail']++;
                        return $this->show_message($GLOBALS['_LANG']['signin_failed'], '', 'flow.php?step=login');
                    }
                } elseif (!empty($_POST['act']) && $_POST['act'] == 'signup') {
                    if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
                        if (empty($_POST['captcha'])) {
                            return $this->show_message($GLOBALS['_LANG']['invalid_captcha']);
                        }

                        /* 检查验证码 */

                        $validator = new captcha();
                        if (!$validator->check_word($_POST['captcha'])) {
                            return $this->show_message($GLOBALS['_LANG']['invalid_captcha']);
                        }
                    }

                    if (register(trim($_POST['username']), trim($_POST['password']), trim($_POST['email']))) {
                        /*
                         * TODO BY LANCE
                         * $matrix = new matrix;
                        if ($matrix->get_bind_info('ecos.taocrm')) {
                            $matrix->createMember($_SESSION['user_id'], 'ecos.taocrm');
                        }*/
                        /* 用户注册成功 */
                        return $this->redirect('flow.php?step=consignee');

                    } else {
                        return $GLOBALS['err']->show();
                    }
                } else {
                    // TODO: 非法访问的处理
                }
            }
        } elseif ($_REQUEST['step'] == 'consignee') {
            /*------------------------------------------------------ */
            //-- 收货人信息
            /*------------------------------------------------------ */
            load_helper('transaction');

            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                /* 取得购物类型 */
                $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

                /*
                 * 收货人信息填写界面
                 */

                if (isset($_REQUEST['direct_shopping'])) {
                    $_SESSION['direct_shopping'] = 1;
                }

                /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
                $this->assign('country_list', get_regions());
                $this->assign('shop_country', $GLOBALS['_CFG']['shop_country']);
                $this->assign('shop_province_list', get_regions(1, $GLOBALS['_CFG']['shop_country']));

                /* 获得用户所有的收货人信息 */
                if ($_SESSION['user_id'] > 0) {
                    $consignee_list = get_consignee_list($_SESSION['user_id']);

                    if (count($consignee_list) < 5) {
                        /* 如果用户收货人信息的总数小于 5 则增加一个新的收货人信息 */
                        $consignee_list[] = array('country' => $GLOBALS['_CFG']['shop_country'], 'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '');
                    }
                } else {
                    if (isset($_SESSION['flow_consignee'])) {
                        $consignee_list = array($_SESSION['flow_consignee']);
                    } else {
                        $consignee_list[] = array('country' => $GLOBALS['_CFG']['shop_country']);
                    }
                }
                $this->assign('name_of_region', array($GLOBALS['_CFG']['name_of_region_1'], $GLOBALS['_CFG']['name_of_region_2'], $GLOBALS['_CFG']['name_of_region_3'], $GLOBALS['_CFG']['name_of_region_4']));
                $this->assign('consignee_list', $consignee_list);

                /* 取得每个收货地址的省市区列表 */
                $province_list = array();
                $city_list = array();
                $district_list = array();
                foreach ($consignee_list as $region_id => $consignee) {
                    $consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 0;
                    $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
                    $consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : 0;

                    $province_list[$region_id] = get_regions(1, $consignee['country']);
                    $city_list[$region_id] = get_regions(2, $consignee['province']);
                    $district_list[$region_id] = get_regions(3, $consignee['city']);
                }
                $this->assign('province_list', $province_list);
                $this->assign('city_list', $city_list);
                $this->assign('district_list', $district_list);

                /* 返回收货人页面代码 */
                $this->assign('real_goods_count', exist_real_goods(0, $flow_type) ? 1 : 0);
            } else {
                /*
                 * 保存收货人信息
                 */
                $consignee = array(
                    'address_id' => empty($_POST['address_id']) ? 0 : intval($_POST['address_id']),
                    'consignee' => empty($_POST['consignee']) ? '' : compile_str(trim($_POST['consignee'])),
                    'country' => empty($_POST['country']) ? '' : intval($_POST['country']),
                    'province' => empty($_POST['province']) ? '' : intval($_POST['province']),
                    'city' => empty($_POST['city']) ? '' : intval($_POST['city']),
                    'district' => empty($_POST['district']) ? '' : intval($_POST['district']),
                    'email' => empty($_POST['email']) ? '' : compile_str($_POST['email']),
                    'address' => empty($_POST['address']) ? '' : compile_str($_POST['address']),
                    'zipcode' => empty($_POST['zipcode']) ? '' : compile_str(make_semiangle(trim($_POST['zipcode']))),
                    'tel' => empty($_POST['tel']) ? '' : compile_str(make_semiangle(trim($_POST['tel']))),
                    'mobile' => empty($_POST['mobile']) ? '' : compile_str(make_semiangle(trim($_POST['mobile']))),
                    'sign_building' => empty($_POST['sign_building']) ? '' : compile_str($_POST['sign_building']),
                    'best_time' => empty($_POST['best_time']) ? '' : compile_str($_POST['best_time']),
                );

                if ($_SESSION['user_id'] > 0) {
                    load_helper('transaction');

                    /* 如果用户已经登录，则保存收货人信息 */
                    $consignee['user_id'] = $_SESSION['user_id'];

                    save_consignee($consignee, true);
                }

                /* 保存到session */
                $_SESSION['flow_consignee'] = stripslashes_deep($consignee);

                return $this->redirect('flow.php?step=checkout');

            }
        } elseif ($_REQUEST['step'] == 'drop_consignee') {
            /*------------------------------------------------------ */
            //-- 删除收货人信息
            /*------------------------------------------------------ */
            load_helper('transaction');

            $consignee_id = intval($_GET['id']);

            if (drop_consignee($consignee_id)) {
                return $this->redirect('flow.php?step=consignee');

            } else {
                return $this->show_message($GLOBALS['_LANG']['not_fount_consignee']);
            }
        } elseif ($_REQUEST['step'] == 'checkout') {
            /*------------------------------------------------------ */
            //-- 订单确认
            /*------------------------------------------------------ */

            /* 取得购物类型 */
            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

            /* 团购标志 */
            if ($flow_type == CART_GROUP_BUY_GOODS) {
                $this->assign('is_group_buy', 1);
            } /* 积分兑换商品 */
            elseif ($flow_type == CART_EXCHANGE_GOODS) {
                $this->assign('is_exchange_goods', 1);
            } else {
                //正常购物流程  清空其他购物流程情况
                $_SESSION['flow_order']['extension_code'] = '';
            }

            $where = "session_id = '" . SESS_ID . "'";
            if ($_SESSION['user_id']) {
                $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
            }

            /* 检查购物车中是否有商品 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('cart') .
                " WHERE " . $where . " AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";

            if ($GLOBALS['db']->getOne($sql) == 0) {
                return $this->show_message($GLOBALS['_LANG']['no_goods_in_cart'], '', '', 'warning');
            }

            /*
             * 检查用户是否已经登录
             * 如果用户已经登录了则检查是否有默认的收货地址
             * 如果没有登录则跳转到登录和注册页面
             */
            if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0) {
                /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
                return $this->redirect('flow.php?step=login');

            }

            $consignee = get_consignee($_SESSION['user_id']);

            /* 检查收货人信息是否完整 */
            if (!check_consignee_info($consignee, $flow_type)) {
                /* 如果不完整则转向到收货人信息填写界面 */
                return $this->redirect('flow.php?step=consignee');

            }

            $_SESSION['flow_consignee'] = $consignee;
            $this->assign('consignee', $consignee);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计
            $this->assign('goods_list', $cart_goods);

            /* 对是否允许修改购物车赋值 */
            if ($flow_type != CART_GENERAL_GOODS || $GLOBALS['_CFG']['one_step_buy'] == '1') {
                $this->assign('allow_edit_cart', 0);
            } else {
                $this->assign('allow_edit_cart', 1);
            }

            /*
             * 取得购物流程设置
             */
            $this->assign('config', $GLOBALS['_CFG']);
            /*
             * 取得订单信息
             */
            $order = flow_order_info();
            $this->assign('order', $order);

            /* 计算折扣 */
            if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS) {
                $discount = compute_discount();
                $this->assign('discount', $discount['discount']);
                $favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
                $this->assign('your_discount', sprintf($GLOBALS['_LANG']['your_discount'], $favour_name, price_format($discount['discount'])));
            }

            /*
             * 计算订单的费用
             */
            $total = order_fee($order, $cart_goods, $consignee);

            $this->assign('total', $total);
            $this->assign('shopping_money', sprintf($GLOBALS['_LANG']['shopping_money'], $total['formated_goods_price']));
            $this->assign('market_price_desc', sprintf($GLOBALS['_LANG']['than_market_price'], $total['formated_market_price'], $total['formated_saving'], $total['save_rate']));

            /* 取得配送列表 */
            $region = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
            $shipping_list = available_shipping_list($region);
            $cart_weight_price = cart_weight_price($flow_type);
            $insure_disabled = true;
            $cod_disabled = true;

            // 查看购物车中是否全为免运费商品，若是则把运费赋为零
            $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE " . $where . " AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
            $shipping_count = $GLOBALS['db']->getOne($sql);

            foreach ($shipping_list as $key => $val) {
                $shipping_cfg = unserialize_config($val['configure']);
                $shipping_fee = ($shipping_count == 0 and $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee(
                    $val['shipping_code'],
                    unserialize($val['configure']),
                    $cart_weight_price['weight'],
                    $cart_weight_price['amount'],
                    $cart_weight_price['number']
                );

                $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
                $shipping_list[$key]['shipping_fee'] = $shipping_fee;
                $shipping_list[$key]['free_money'] = price_format($shipping_cfg['free_money'], false);
                $shipping_list[$key]['insure_formated'] = strpos($val['insure'], '%') === false ?
                    price_format($val['insure'], false) : $val['insure'];

                /* 当前的配送方式是否支持保价 */
                if ($val['shipping_id'] == $order['shipping_id']) {
                    $insure_disabled = ($val['insure'] == 0);
                    $cod_disabled = ($val['support_cod'] == 0);
                }
            }

            $this->assign('shipping_list', $shipping_list);
            $this->assign('insure_disabled', $insure_disabled);
            $this->assign('cod_disabled', $cod_disabled);

            /* 取得支付列表 */
            if ($order['shipping_id'] == 0) {
                $cod = true;
                $cod_fee = 0;
            } else {
                $shipping = shipping_info($order['shipping_id']);
                $cod = $shipping['support_cod'];

                if ($cod) {
                    /* 如果是团购，且保证金大于0，不能使用货到付款 */
                    if ($flow_type == CART_GROUP_BUY_GOODS) {
                        $group_buy_id = $_SESSION['extension_id'];
                        if ($group_buy_id <= 0) {
                            return $this->show_message('error group_buy_id');
                        }
                        $group_buy = group_buy_info($group_buy_id);
                        if (empty($group_buy)) {
                            return $this->show_message('group buy not exists: ' . $group_buy_id);
                        }

                        if ($group_buy['deposit'] > 0) {
                            $cod = false;
                            $cod_fee = 0;

                            /* 赋值保证金 */
                            $this->assign('gb_deposit', $group_buy['deposit']);
                        }
                    }

                    if ($cod) {
                        $shipping_area_info = shipping_area_info($order['shipping_id'], $region);
                        $cod_fee = $shipping_area_info['pay_fee'];
                    }
                } else {
                    $cod_fee = 0;
                }
            }

            // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
            $payment_list = available_payment_list(1, $cod_fee, false, '0');
            if (isset($payment_list)) {
                foreach ($payment_list as $key => $payment) {
                    if ($payment['is_cod'] == '1') {
                        $payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
                    }
                    /* 如果有易宝神州行支付 如果订单金额大于300 则不显示 */
                    if ($payment['pay_code'] == 'yeepayszx' && $total['amount'] > 300) {
                        unset($payment_list[$key]);
                    }
                    /* 如果有余额支付 */
                    if ($payment['pay_code'] == 'balance') {
                        /* 如果未登录，不显示 */
                        if ($_SESSION['user_id'] == 0) {
                            unset($payment_list[$key]);
                        } else {
                            if ($_SESSION['flow_order']['pay_id'] == $payment['pay_id']) {
                                $this->assign('disable_surplus', 1);
                            }
                        }
                    }
                    if ($payment['pay_code'] == 'yunqi') {
                        $yunqi_payment = $payment_list[$key];
                        unset($payment_list[$key]);
                    }
                }
            }
            $yunqi_payment and array_unshift($payment_list, $yunqi_payment);
            $this->assign('payment_list', $payment_list);

            /* 取得包装与贺卡 */
            if ($total['real_goods_count'] > 0) {
                /* 只有有实体商品,才要判断包装和贺卡 */
                if (!isset($GLOBALS['_CFG']['use_package']) || $GLOBALS['_CFG']['use_package'] == '1') {
                    /* 如果使用包装，取得包装列表及用户选择的包装 */
                    $this->assign('pack_list', pack_list());
                }

                /* 如果使用贺卡，取得贺卡列表及用户选择的贺卡 */
                if (!isset($GLOBALS['_CFG']['use_card']) || $GLOBALS['_CFG']['use_card'] == '1') {
                    $this->assign('card_list', card_list());
                }
            }

            $user_info = user_info($_SESSION['user_id']);

            /* 如果使用余额，取得用户余额 */
            if ((!isset($GLOBALS['_CFG']['use_surplus']) || $GLOBALS['_CFG']['use_surplus'] == '1')
                && $_SESSION['user_id'] > 0
                && $user_info['user_money'] > 0) {
                // 能使用余额
                $this->assign('allow_use_surplus', 1);
                $this->assign('your_surplus', $user_info['user_money']);
            }

            /* 如果使用积分，取得用户可用积分及本订单最多可以使用的积分 */
            if ((!isset($GLOBALS['_CFG']['use_integral']) || $GLOBALS['_CFG']['use_integral'] == '1')
                && $_SESSION['user_id'] > 0
                && $user_info['pay_points'] > 0
                && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS)) {
                // 能使用积分
                $this->assign('allow_use_integral', 1);
                $this->assign('order_max_integral', $this->flow_available_points());  // 可用积分
                $this->assign('your_integral', $user_info['pay_points']); // 用户积分
            }

            /* 如果使用红包，取得用户可以使用的红包及用户选择的红包 */
            if ((!isset($GLOBALS['_CFG']['use_bonus']) || $GLOBALS['_CFG']['use_bonus'] == '1')
                && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS)) {
                // 取得用户可用红包
                $user_bonus = user_bonus($_SESSION['user_id'], $total['goods_price']);
                if (!empty($user_bonus)) {
                    foreach ($user_bonus as $key => $val) {
                        $user_bonus[$key]['bonus_money_formated'] = price_format($val['type_money'], false);
                    }
                    $this->assign('bonus_list', $user_bonus);
                }

                // 能使用红包
                $this->assign('allow_use_bonus', 1);
            }

            /* 如果使用缺货处理，取得缺货处理列表 */
            if (!isset($GLOBALS['_CFG']['use_how_oos']) || $GLOBALS['_CFG']['use_how_oos'] == '1') {
                if (is_array($GLOBALS['_LANG']['oos']) && !empty($GLOBALS['_LANG']['oos'])) {
                    $this->assign('how_oos_list', $GLOBALS['_LANG']['oos']);
                }
            }

            /* 如果能开发票，取得发票内容列表 */
            if ((!isset($GLOBALS['_CFG']['can_invoice']) || $GLOBALS['_CFG']['can_invoice'] == '1')
                && isset($GLOBALS['_CFG']['invoice_content'])
                && trim($GLOBALS['_CFG']['invoice_content']) != '' && $flow_type != CART_EXCHANGE_GOODS) {
                $inv_content_list = explode("\n", str_replace("\r", '', $GLOBALS['_CFG']['invoice_content']));
                $this->assign('inv_content_list', $inv_content_list);

                $inv_type_list = array();
                foreach ($GLOBALS['_CFG']['invoice_type']['type'] as $key => $type) {
                    if (!empty($type)) {
                        $inv_type_list[$type] = $type . ' [' . floatval($GLOBALS['_CFG']['invoice_type']['rate'][$key]) . '%]';
                    }
                }
                $this->assign('inv_type_list', $inv_type_list);
            }

            /* 保存 session */
            $_SESSION['flow_order'] = $order;
        } elseif ($_REQUEST['step'] == 'select_shipping') {
            /*------------------------------------------------------ */
            //-- 改变配送方式
            /*------------------------------------------------------ */
            $result = array('error' => '', 'content' => '', 'need_insure' => 0);

            /* 取得购物类型 */
            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

            /* 获得收货人信息 */
            $consignee = get_consignee($_SESSION['user_id']);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

            if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            } else {
                /* 取得购物流程设置 */
                $this->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                $order['shipping_id'] = intval($_REQUEST['shipping']);
                $regions = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
                $shipping_info = shipping_area_info($order['shipping_id'], $regions);

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee);
                $this->assign('total', $total);

                /* 取得可以得到的积分和红包 */
                $this->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
                $this->assign('total_bonus', price_format(get_total_bonus(), false));

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->assign('is_group_buy', 1);
                }

                $result['cod_fee'] = $shipping_info['pay_fee'];
                if (strpos($result['cod_fee'], '%') === false) {
                    $result['cod_fee'] = price_format($result['cod_fee'], false);
                }
                $result['need_insure'] = ($shipping_info['insure'] > 0 && !empty($order['need_insure'])) ? 1 : 0;
                $result['content'] = $GLOBALS['smarty']->fetch('library/order_total');
            }

            echo json_encode($result);

        } elseif ($_REQUEST['step'] == 'select_insure') {
            /*------------------------------------------------------ */
            //-- 选定/取消配送的保价
            /*------------------------------------------------------ */

            $result = array('error' => '', 'content' => '', 'need_insure' => 0);

            /* 取得购物类型 */
            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

            /* 获得收货人信息 */
            $consignee = get_consignee($_SESSION['user_id']);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

            if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            } else {
                /* 取得购物流程设置 */
                $this->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                $order['need_insure'] = intval($_REQUEST['insure']);

                /* 保存 session */
                $_SESSION['flow_order'] = $order;

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee);
                $this->assign('total', $total);

                /* 取得可以得到的积分和红包 */
                $this->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
                $this->assign('total_bonus', price_format(get_total_bonus(), false));

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->assign('is_group_buy', 1);
                }

                $result['content'] = $GLOBALS['smarty']->fetch('library/order_total');
            }

            echo json_encode($result);

        } elseif ($_REQUEST['step'] == 'select_payment') {
            /*------------------------------------------------------ */
            //-- 改变支付方式
            /*------------------------------------------------------ */

            $result = array('error' => '', 'content' => '', 'need_insure' => 0, 'payment' => 1);

            /* 取得购物类型 */
            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

            /* 获得收货人信息 */
            $consignee = get_consignee($_SESSION['user_id']);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

            if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            } else {
                /* 取得购物流程设置 */
                $this->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                $order['pay_id'] = intval($_REQUEST['payment']);
                $payment_info = payment_info($order['pay_id']);
                $result['pay_code'] = $payment_info['pay_code'];

                /* 保存 session */
                $_SESSION['flow_order'] = $order;

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee);
                $this->assign('total', $total);

                /* 取得可以得到的积分和红包 */
                $this->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
                $this->assign('total_bonus', price_format(get_total_bonus(), false));

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->assign('is_group_buy', 1);
                }

                $result['content'] = $GLOBALS['smarty']->fetch('library/order_total');
            }

            echo json_encode($result);

        } elseif ($_REQUEST['step'] == 'select_pack') {
            /*------------------------------------------------------ */
            //-- 改变商品包装
            /*------------------------------------------------------ */

            $result = array('error' => '', 'content' => '', 'need_insure' => 0);

            /* 取得购物类型 */
            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

            /* 获得收货人信息 */
            $consignee = get_consignee($_SESSION['user_id']);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

            if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            } else {
                /* 取得购物流程设置 */
                $this->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                $order['pack_id'] = intval($_REQUEST['pack']);

                /* 保存 session */
                $_SESSION['flow_order'] = $order;

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee);
                $this->assign('total', $total);

                /* 取得可以得到的积分和红包 */
                $this->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
                $this->assign('total_bonus', price_format(get_total_bonus(), false));

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->assign('is_group_buy', 1);
                }

                $result['content'] = $GLOBALS['smarty']->fetch('library/order_total');
            }

            echo json_encode($result);

        } elseif ($_REQUEST['step'] == 'select_card') {
            /*------------------------------------------------------ */
            //-- 改变贺卡
            /*------------------------------------------------------ */

            $result = array('error' => '', 'content' => '', 'need_insure' => 0);

            /* 取得购物类型 */
            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

            /* 获得收货人信息 */
            $consignee = get_consignee($_SESSION['user_id']);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

            if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            } else {
                /* 取得购物流程设置 */
                $this->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                $order['card_id'] = intval($_REQUEST['card']);

                /* 保存 session */
                $_SESSION['flow_order'] = $order;

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee);
                $this->assign('total', $total);

                /* 取得可以得到的积分和红包 */
                $this->assign('total_integral', cart_amount(false, $flow_type) - $order['bonus'] - $total['integral_money']);
                $this->assign('total_bonus', price_format(get_total_bonus(), false));

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->assign('is_group_buy', 1);
                }

                $result['content'] = $GLOBALS['smarty']->fetch('library/order_total');
            }

            echo json_encode($result);

        } elseif ($_REQUEST['step'] == 'change_surplus') {
            /*------------------------------------------------------ */
            //-- 改变余额
            /*------------------------------------------------------ */

            $surplus = floatval($_GET['surplus']);
            $user_info = user_info($_SESSION['user_id']);

            if ($user_info['user_money'] + $user_info['credit_line'] < $surplus) {
                $result['error'] = $GLOBALS['_LANG']['surplus_not_enough'];
            } else {
                /* 取得购物类型 */
                $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

                /* 取得购物流程设置 */
                $this->assign('config', $GLOBALS['_CFG']);

                /* 获得收货人信息 */
                $consignee = get_consignee($_SESSION['user_id']);

                /* 对商品信息赋值 */
                $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

                if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
                    $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
                } else {
                    /* 取得订单信息 */
                    $order = flow_order_info();
                    $order['surplus'] = $surplus;

                    /* 计算订单的费用 */
                    $total = order_fee($order, $cart_goods, $consignee);
                    $this->assign('total', $total);

                    /* 团购标志 */
                    if ($flow_type == CART_GROUP_BUY_GOODS) {
                        $this->assign('is_group_buy', 1);
                    }

                    $result['content'] = $GLOBALS['smarty']->fetch('library/order_total');
                }
            }

            die(json_encode($result));
        } elseif ($_REQUEST['step'] == 'change_integral') {
            /*------------------------------------------------------ */
            //-- 改变积分
            /*------------------------------------------------------ */

            $points = floatval($_GET['points']);
            $user_info = user_info($_SESSION['user_id']);

            /* 取得订单信息 */
            $order = flow_order_info();

            $flow_points = $this->flow_available_points();  // 该订单允许使用的积分
            $user_points = $user_info['pay_points']; // 用户的积分总数

            if ($points > $user_points) {
                $result['error'] = $GLOBALS['_LANG']['integral_not_enough'];
            } elseif ($points > $flow_points) {
                $result['error'] = sprintf($GLOBALS['_LANG']['integral_too_much'], $flow_points);
            } else {
                /* 取得购物类型 */
                $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

                $order['integral'] = $points;

                /* 获得收货人信息 */
                $consignee = get_consignee($_SESSION['user_id']);

                /* 对商品信息赋值 */
                $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

                if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
                    $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
                } else {
                    /* 计算订单的费用 */
                    $total = order_fee($order, $cart_goods, $consignee);
                    $this->assign('total', $total);
                    $this->assign('config', $GLOBALS['_CFG']);

                    /* 团购标志 */
                    if ($flow_type == CART_GROUP_BUY_GOODS) {
                        $this->assign('is_group_buy', 1);
                    }

                    $result['content'] = $GLOBALS['smarty']->fetch('library/order_total');
                    $result['error'] = '';
                }
            }

            die(json_encode($result));
        } elseif ($_REQUEST['step'] == 'change_bonus') {
            /*------------------------------------------------------ */
            //-- 改变红包
            /*------------------------------------------------------ */
            $result = array('error' => '', 'content' => '');

            /* 取得购物类型 */
            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

            /* 获得收货人信息 */
            $consignee = get_consignee($_SESSION['user_id']);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

            if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            } else {
                /* 取得购物流程设置 */
                $this->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                $bonus = bonus_info(intval($_GET['bonus']));

                if ((!empty($bonus) && $bonus['user_id'] == $_SESSION['user_id']) || $_GET['bonus'] == 0) {
                    $order['bonus_id'] = intval($_GET['bonus']);
                } else {
                    $order['bonus_id'] = 0;
                    $result['error'] = $GLOBALS['_LANG']['invalid_bonus'];
                }

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee);
                $this->assign('total', $total);

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->assign('is_group_buy', 1);
                }

                $result['content'] = $GLOBALS['smarty']->fetch('library/order_total');
            }

            die(json_encode($result));
        } elseif ($_REQUEST['step'] == 'change_needinv') {
            /*------------------------------------------------------ */
            //-- 改变发票的设置
            /*------------------------------------------------------ */
            $result = array('error' => '', 'content' => '');
            $_GET['inv_type'] = !empty($_GET['inv_type']) ? json_str_iconv(urldecode($_GET['inv_type'])) : '';
            $_GET['invPayee'] = !empty($_GET['invPayee']) ? json_str_iconv(urldecode($_GET['invPayee'])) : '';
            $_GET['inv_content'] = !empty($_GET['inv_content']) ? json_str_iconv(urldecode($_GET['inv_content'])) : '';

            /* 取得购物类型 */
            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

            /* 获得收货人信息 */
            $consignee = get_consignee($_SESSION['user_id']);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

            if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
                die(json_encode($result));
            } else {
                /* 取得购物流程设置 */
                $this->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                if (isset($_GET['need_inv']) && intval($_GET['need_inv']) == 1) {
                    $order['need_inv'] = 1;
                    $order['inv_type'] = trim(stripslashes($_GET['inv_type']));
                    $order['inv_payee'] = trim(stripslashes($_GET['inv_payee']));
                    $order['inv_content'] = trim(stripslashes($_GET['inv_content']));
                } else {
                    $order['need_inv'] = 0;
                    $order['inv_type'] = '';
                    $order['inv_payee'] = '';
                    $order['inv_content'] = '';
                }

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee);
                $this->assign('total', $total);

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->assign('is_group_buy', 1);
                }

                die($GLOBALS['smarty']->fetch('library/order_total'));
            }
        } elseif ($_REQUEST['step'] == 'change_oos') {
            /*------------------------------------------------------ */
            //-- 改变缺货处理时的方式
            /*------------------------------------------------------ */

            /* 取得订单信息 */
            $order = flow_order_info();

            $order['how_oos'] = intval($_GET['oos']);

            /* 保存 session */
            $_SESSION['flow_order'] = $order;
        } elseif ($_REQUEST['step'] == 'check_surplus') {
            /*------------------------------------------------------ */
            //-- 检查用户输入的余额
            /*------------------------------------------------------ */
            $surplus = floatval($_GET['surplus']);
            $user_info = user_info($_SESSION['user_id']);

            if (($user_info['user_money'] + $user_info['credit_line'] < $surplus)) {
                die($GLOBALS['_LANG']['surplus_not_enough']);
            }


        } elseif ($_REQUEST['step'] == 'check_integral') {
            /*------------------------------------------------------ */
            //-- 检查用户输入的余额
            /*------------------------------------------------------ */
            $points = floatval($_GET['integral']);
            $user_info = user_info($_SESSION['user_id']);
            $flow_points = $this->flow_available_points();  // 该订单允许使用的积分
            $user_points = $user_info['pay_points']; // 用户的积分总数

            if ($points > $user_points) {
                die($GLOBALS['_LANG']['integral_not_enough']);
            }

            if ($points > $flow_points) {
                die(sprintf($GLOBALS['_LANG']['integral_too_much'], $flow_points));
            }


        }
        /*------------------------------------------------------ */
        //-- 完成所有订单操作，提交到数据库
        /*------------------------------------------------------ */
        elseif ($_REQUEST['step'] == 'done') {
            add_log("pc create order begin==POST:", $_POST);
            load_helper('clips');
            load_helper('payment');

            $where = "session_id = '" . SESS_ID . "'";
            if ($_SESSION['user_id']) {
                $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
            }

            /* 取得购物类型 */
            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
            add_log("pc create order ==flow_type:" . $flow_type . "==user_id:" . intval($_SESSION['user_id']));
            /* 检查购物车中是否有商品 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('cart') .
                " WHERE " . $where .
                "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";
            if ($GLOBALS['db']->getOne($sql) == 0) {
                return $this->show_message($GLOBALS['_LANG']['no_goods_in_cart'], '', '', 'warning');
            }

            /* 检查商品库存 */
            /* 如果使用库存，且下订单时减库存，则减少库存 */
            if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                $cart_goods_stock = get_cart_goods();
                $_cart_goods_stock = array();
                foreach ($cart_goods_stock['goods_list'] as $value) {
                    $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
                }
                $this->flow_cart_stock($_cart_goods_stock);
                unset($cart_goods_stock, $_cart_goods_stock);
            }

            /*
             * 检查用户是否已经登录
             * 如果用户已经登录了则检查是否有默认的收货地址
             * 如果没有登录则跳转到登录和注册页面
             */
            if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0) {
                /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
                return $this->redirect('flow.php?step=login');

            }

            $consignee = get_consignee($_SESSION['user_id']);

            /* 检查收货人信息是否完整 */
            if (!check_consignee_info($consignee, $flow_type)) {
                /* 如果不完整则转向到收货人信息填写界面 */
                return $this->redirect('flow.php?step=consignee');

            }

            $_POST['how_oos'] = isset($_POST['how_oos']) ? intval($_POST['how_oos']) : 0;
            $_POST['card_message'] = isset($_POST['card_message']) ? compile_str($_POST['card_message']) : '';
            $_POST['inv_type'] = !empty($_POST['inv_type']) ? compile_str($_POST['inv_type']) : '';
            $_POST['inv_payee'] = isset($_POST['inv_payee']) ? compile_str($_POST['inv_payee']) : '';
            $_POST['inv_content'] = isset($_POST['inv_content']) ? compile_str($_POST['inv_content']) : '';
            $_POST['postscript'] = isset($_POST['postscript']) ? compile_str($_POST['postscript']) : '';

            $order = array(
                'shipping_id' => intval($_POST['shipping']),
                'pay_id' => intval($_POST['payment']),
                'pack_id' => isset($_POST['pack']) ? intval($_POST['pack']) : 0,
                'card_id' => isset($_POST['card']) ? intval($_POST['card']) : 0,
                'card_message' => trim($_POST['card_message']),
                'surplus' => isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0.00,
                'integral' => isset($_POST['integral']) ? intval($_POST['integral']) : 0,
                'bonus_id' => isset($_POST['bonus']) ? intval($_POST['bonus']) : 0,
                'need_inv' => empty($_POST['need_inv']) ? 0 : 1,
                'inv_type' => $_POST['inv_type'],
                'inv_payee' => trim($_POST['inv_payee']),
                'inv_content' => $_POST['inv_content'],
                'postscript' => trim($_POST['postscript']),
                'how_oos' => isset($GLOBALS['_LANG']['oos'][$_POST['how_oos']]) ? addslashes($GLOBALS['_LANG']['oos'][$_POST['how_oos']]) : '',
                'need_insure' => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
                'user_id' => $_SESSION['user_id'],
                'add_time' => gmtime(),
                'lastmodify' => gmtime(),
                'order_status' => OS_UNCONFIRMED,
                'shipping_status' => SS_UNSHIPPED,
                'pay_status' => PS_UNPAYED,
                'agency_id' => get_agency_by_regions(array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']))
            );

            if (isset($GLOBALS['_CFG']['use_how_oos']) && $GLOBALS['_CFG']['use_how_oos'] == '0') {
                $order['how_oos'] = '';
            }

            /* 扩展信息 */
            if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) != CART_GENERAL_GOODS) {
                $order['extension_code'] = $_SESSION['extension_code'];
                $order['extension_id'] = $_SESSION['extension_id'];
            } else {
                $order['extension_code'] = '';
                $order['extension_id'] = 0;
            }

            /* 检查积分余额是否合法 */
            $user_id = $_SESSION['user_id'];
            if ($user_id > 0) {
                $user_info = user_info($user_id);

                $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
                if ($order['surplus'] < 0) {
                    $order['surplus'] = 0;
                }

                // 查询用户有多少积分
                $flow_points = $this->flow_available_points();  // 该订单允许使用的积分
                $user_points = $user_info['pay_points']; // 用户的积分总数

                $order['integral'] = min($order['integral'], $user_points, $flow_points);
                if ($order['integral'] < 0) {
                    $order['integral'] = 0;
                }
            } else {
                $order['surplus'] = 0;
                $order['integral'] = 0;
            }

            /* 检查红包是否存在 */
            if ($order['bonus_id'] > 0) {
                $bonus = bonus_info($order['bonus_id']);

                if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type)) {
                    $order['bonus_id'] = 0;
                }
            } elseif (isset($_POST['bonus_sn'])) {
                $bonus_sn = trim($_POST['bonus_sn']);
                $bonus = bonus_info(0, $bonus_sn);
                $now = gmtime();
                if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type) || $now > $bonus['use_end_date']) {
                } else {
                    if ($user_id > 0) {
                        $sql = "UPDATE " . $GLOBALS['ecs']->table('user_bonus') . " SET user_id = '$user_id' WHERE bonus_id = '$bonus[bonus_id]' LIMIT 1";
                        $GLOBALS['db']->query($sql);
                    }
                    $order['bonus_id'] = $bonus['bonus_id'];
                    $order['bonus_sn'] = $bonus_sn;
                }
            }

            /* 订单中的商品 */
            $cart_goods = cart_goods($flow_type);
            add_log("pc create order ==cart_goods==", $cart_goods);
            if (empty($cart_goods)) {
                return $this->show_message($GLOBALS['_LANG']['no_goods_in_cart'], $GLOBALS['_LANG']['back_home'], './', 'warning');
            }

            /* 检查商品总额是否达到最低限购金额 */
            if ($flow_type == CART_GENERAL_GOODS && cart_amount(true, CART_GENERAL_GOODS) < $GLOBALS['_CFG']['min_goods_amount']) {
                return $this->show_message(sprintf($GLOBALS['_LANG']['goods_amount_not_enough'], price_format($GLOBALS['_CFG']['min_goods_amount'], false)));
            }

            /* 收货人信息 */
            foreach ($consignee as $key => $value) {
                $order[$key] = addslashes($value);
            }

            /* 判断是不是实体商品 */
            foreach ($cart_goods as $val) {
                /* 统计实体商品的个数 */
                if ($val['is_real']) {
                    $is_real_good = 1;
                }
            }
            if (isset($is_real_good)) {
                $sql = "SELECT shipping_id FROM " . $GLOBALS['ecs']->table('shipping') . " WHERE shipping_id=" . $order['shipping_id'] . " AND enabled =1";
                if (!$GLOBALS['db']->getOne($sql)) {
                    return $this->show_message($GLOBALS['_LANG']['flow_no_shipping']);
                }
            }
            /* 订单中的总额 */
            $total = order_fee($order, $cart_goods, $consignee);
            $order['bonus'] = $total['bonus'];
            $order['goods_amount'] = $total['goods_price'];
            $order['discount'] = $total['discount'];
            $order['surplus'] = $total['surplus'];
            $order['tax'] = $total['tax'];

            // 购物车中的商品能享受红包支付的总额
            $discount_amout = compute_discount_amount();
            // 红包和积分最多能支付的金额为商品总额
            $temp_amout = $order['goods_amount'] - $discount_amout;
            if ($temp_amout <= 0) {
                $order['bonus_id'] = 0;
            }

            /* 配送方式 */
            if ($order['shipping_id'] > 0) {
                $shipping = shipping_info($order['shipping_id']);
                $order['shipping_name'] = addslashes($shipping['shipping_name']);
            }
            $order['shipping_fee'] = $total['shipping_fee'];
            $order['insure_fee'] = $total['shipping_insure'];

            /* 支付方式 */
            if ($order['pay_id'] > 0) {
                $payment = payment_info($order['pay_id']);
                $order['pay_name'] = addslashes($payment['pay_name']);
            }
            $order['pay_fee'] = $total['pay_fee'];
            $order['cod_fee'] = $total['cod_fee'];

            /* 商品包装 */
            if ($order['pack_id'] > 0) {
                $pack = pack_info($order['pack_id']);
                $order['pack_name'] = addslashes($pack['pack_name']);
            }
            $order['pack_fee'] = $total['pack_fee'];

            /* 祝福贺卡 */
            if ($order['card_id'] > 0) {
                $card = card_info($order['card_id']);
                $order['card_name'] = addslashes($card['card_name']);
            }
            $order['card_fee'] = $total['card_fee'];

            $order['order_amount'] = number_format($total['amount'], 2, '.', '');

            /* 如果全部使用余额支付，检查余额是否足够 */
            if ($payment['pay_code'] == 'balance' && $order['order_amount'] > 0) {
                if ($order['surplus'] > 0) { //余额支付里如果输入了一个金额
                    $order['order_amount'] = $order['order_amount'] + $order['surplus'];
                    $order['surplus'] = 0;
                }
                if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line'])) {
                    return $this->show_message($GLOBALS['_LANG']['balance_not_enough']);
                } else {
                    $order['surplus'] = $order['order_amount'];
                    $order['order_amount'] = 0;
                }
            }

            /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
            if ($order['order_amount'] <= 0) {
                $order['order_status'] = OS_CONFIRMED;
                $order['confirm_time'] = gmtime();
                $order['pay_status'] = PS_PAYED;
                $order['pay_time'] = gmtime();
                $order['order_amount'] = 0;
            }

            $order['integral_money'] = $total['integral_money'];
            $order['integral'] = $total['integral'];

            if ($order['extension_code'] == 'exchange_goods') {
                $order['integral_money'] = 0;
                $order['integral'] = $total['exchange_integral'];
            }

            $order['from_ad'] = !empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
            $order['referer'] = !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : '';

            /* 记录扩展信息 */
            if ($flow_type != CART_GENERAL_GOODS) {
                $order['extension_code'] = $_SESSION['extension_code'];
                $order['extension_id'] = $_SESSION['extension_id'];
            }

            $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
            if (isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 1) {
                //推荐订单分成
                $parent_id = get_affiliate();
                if ($user_id == $parent_id) {
                    $parent_id = 0;
                }
            } elseif (isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 0) {
                //推荐注册分成
                $parent_id = 0;
            } else {
                //分成功能关闭
                $parent_id = 0;
            }
            $order['parent_id'] = $parent_id;

            /* 插入订单表 */
            $error_no = 0;
            do {
                $order['order_sn'] = get_order_sn(); //获取新订单号
                add_log("pc create order ==insert order ==", $order);
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $order, 'INSERT');

                $error_no = $GLOBALS['db']->errno();

                if ($error_no > 0 && $error_no != 1062) {
                    die($GLOBALS['db']->errorMsg());
                }
            } while ($error_no == 1062); //如果是订单号重复则重新提交数据

            $new_order_id = $GLOBALS['db']->insert_id();
            $order['order_id'] = $new_order_id;

            /* 插入订单商品 */
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('order_goods') . "( " .
                "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, " .
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id) " .
                " SELECT '$new_order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, " .
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id" .
                " FROM " . $GLOBALS['ecs']->table('cart') .
                " WHERE " . $where . " AND rec_type = '$flow_type'";
            add_log("pc create order end ==insert order_goods sql:" . $sql);
            $GLOBALS['db']->query($sql);
            /* 修改拍卖活动状态 */
            if ($order['extension_code'] == 'auction') {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_activity') . " SET is_finished='2' WHERE act_id=" . $order['extension_id'];
                $GLOBALS['db']->query($sql);
            }

            /* 处理余额、积分、红包 */
            if ($order['user_id'] > 0 && $order['surplus'] > 0) {
                log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, sprintf($GLOBALS['_LANG']['pay_order'], $order['order_sn']));
                //订单支付后，创建订单到淘打
                /*
                 * TODO BY LANCE
                 * $matrix = new matrix();
                $bind_info = $matrix->get_bind_info(array('taodali'));
                if ($bind_info) {
                    $matrix->createOrder($order['order_sn'], 'taodali');
                }*/
            }
            if ($order['user_id'] > 0 && $order['integral'] > 0) {
                log_account_change($order['user_id'], 0, 0, 0, $order['integral'] * (-1), sprintf($GLOBALS['_LANG']['pay_order'], $order['order_sn']));
                //订单支付后，创建订单到淘打
                /*
                 * TODO BY LANCE
                 * $matrix = new matrix();
                $bind_info = $matrix->get_bind_info(array('taodali'));
                if ($bind_info) {
                    $matrix->createOrder($order['order_sn'], 'taodali');
                }*/
            }


            if ($order['bonus_id'] > 0 && $temp_amout > 0) {
                use_bonus($order['bonus_id'], $new_order_id);
            }

            /* 如果使用库存，且下订单时减库存，则减少库存 */
            if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                change_order_goods_storage($order['order_id'], true, SDT_PLACE);
            }

            /* 给商家发邮件 */
            /* 增加是否给客服发送邮件选项 */
            if ($GLOBALS['_CFG']['send_service_email'] && $GLOBALS['_CFG']['service_email'] != '') {
                $tpl = get_mail_template('remind_of_new_order');
                $this->assign('order', $order);
                $this->assign('goods_list', $cart_goods);
                $this->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                $this->assign('send_date', date($GLOBALS['_CFG']['time_format']));
                $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
                send_mail($GLOBALS['_CFG']['shop_name'], $GLOBALS['_CFG']['service_email'], $tpl['template_subject'], $content, $tpl['is_html']);
            }

            /* 如果需要，发短信 */
            if ($GLOBALS['_CFG']['sms_order_placed'] == '1' && $GLOBALS['_CFG']['sms_shop_mobile'] != '') {
                $sms = new Sms();
                $msg = $order['pay_status'] == PS_UNPAYED ?
                    $GLOBALS['_LANG']['order_placed_sms'] : $GLOBALS['_LANG']['order_placed_sms'] . '[' . $GLOBALS['_LANG']['sms_paid'] . ']';
                $sms->send($GLOBALS['_CFG']['sms_shop_mobile'], sprintf($msg, $order['consignee'], $order['tel']), '', 13, 1);
            }

            /* 如果订单金额为0 处理虚拟卡 */
            if ($order['order_amount'] <= 0) {
                $sql = "SELECT goods_id, goods_name, goods_number AS num FROM " .
                    $GLOBALS['ecs']->table('cart') .
                    " WHERE is_real = 0 AND extension_code = 'virtual_card'" .
                    " AND " . $where . " AND rec_type = '$flow_type'";

                $res = $GLOBALS['db']->getAll($sql);

                $virtual_goods = array();
                foreach ($res as $row) {
                    $virtual_goods['virtual_card'][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
                }

                if ($virtual_goods and $flow_type != CART_GROUP_BUY_GOODS) {
                    /* 虚拟卡发货 */
                    if (virtual_goods_ship($virtual_goods, $msg, $order['order_sn'], true)) {
                        /* 如果没有实体商品，修改发货状态，送积分和红包 */
                        $sql = "SELECT COUNT(*)" .
                            " FROM " . $GLOBALS['ecs']->table('order_goods') .
                            " WHERE order_id = '$order[order_id]' " .
                            " AND is_real = 1";
                        if ($GLOBALS['db']->getOne($sql) <= 0) {
                            /* 修改订单状态 */
                            update_order($order['order_id'], array('shipping_status' => SS_SHIPPED, 'shipping_time' => gmtime()));

                            /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                            if ($order['user_id'] > 0) {
                                /* 取得用户信息 */
                                $user = user_info($order['user_id']);

                                /* 计算并发放积分 */
                                $integral = integral_to_give($order);
                                log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($GLOBALS['_LANG']['order_gift_integral'], $order['order_sn']));

                                /* 发放红包 */
                                send_order_bonus($order['order_id']);
                            }
                        }
                    }
                }
            }

            /* 清空购物车 */
            clear_cart($flow_type);
            /* 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
            clear_all_files();

            /* 插入支付日志 */
            $order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);

            /* 取得支付信息，生成支付代码 */
            if ($order['order_amount'] > 0) {
                $payment = payment_info($order['pay_id']);

                include_once('includes/modules/payment/' . $payment['pay_code'] . '.php');
                $pay_obj = new $payment['pay_code'];
                $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));

                $order['pay_desc'] = $payment['pay_desc'];

                $this->assign('pay_online', $pay_online);
            }
            if (!empty($order['shipping_name'])) {
                $order['shipping_name'] = trim(stripcslashes($order['shipping_name']));
            }

            /* 订单信息 */
            $this->assign('order', $order);
            $this->assign('total', $total);
            $this->assign('goods_list', $cart_goods);
            $this->assign('order_submit_back', sprintf($GLOBALS['_LANG']['order_submit_back'], $GLOBALS['_LANG']['back_home'], $GLOBALS['_LANG']['goto_user_center'])); // 返回提示

            // 对接erp将订单推送到erp
            /*
             * TODO BY LANCE
             * $matrix = new matrix;
            $matrix->createOrder($order['order_sn']);*/

            user_uc_call('add_feed', array($order['order_id'], BUY_GOODS)); //推送feed到uc
            unset($_SESSION['flow_consignee']); // 清除session中保存的收货人信息
            unset($_SESSION['flow_order']);
            unset($_SESSION['direct_shopping']);
        }

        /*------------------------------------------------------ */
        //-- 更新购物车
        /*------------------------------------------------------ */

        elseif ($_REQUEST['step'] == 'update_cart') {
            if (isset($_POST['goods_number']) && is_array($_POST['goods_number'])) {
                $this->flow_update_cart($_POST['goods_number']);
            }

            return $this->show_message($GLOBALS['_LANG']['update_cart_notice'], $GLOBALS['_LANG']['back_to_cart'], 'flow.php');
        }

        /*------------------------------------------------------ */
        //-- 删除购物车中的商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['step'] == 'drop_goods') {
            $rec_id = intval($_GET['id']);
            $this->flow_drop_cart_goods($rec_id);

            return $this->redirect('flow.php');

        } /* 把优惠活动加入购物车 */
        elseif ($_REQUEST['step'] == 'add_favourable') {
            $where = "session_id = '" . SESS_ID . "'";
            if ($_SESSION['user_id']) {
                $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
            }
            /* 取得优惠活动信息 */
            $act_id = intval($_POST['act_id']);
            $favourable = favourable_info($act_id);
            if (empty($favourable)) {
                return $this->show_message($GLOBALS['_LANG']['favourable_not_exist']);
            }

            /* 判断用户能否享受该优惠 */
            if (!$this->favourable_available($favourable)) {
                return $this->show_message($GLOBALS['_LANG']['favourable_not_available']);
            }

            /* 检查购物车中是否已有该优惠 */
            $cart_favourable = $this->cart_favourable();
            if ($this->favourable_used($favourable, $cart_favourable)) {
                return $this->show_message($GLOBALS['_LANG']['favourable_used']);
            }

            /* 赠品（特惠品）优惠 */
            if ($favourable['act_type'] == FAT_GOODS) {
                /* 检查是否选择了赠品 */
                if (empty($_POST['gift'])) {
                    return $this->show_message($GLOBALS['_LANG']['pls_select_gift']);
                }

                /* 检查是否已在购物车 */
                $sql = "SELECT goods_name" .
                    " FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE " . $where .
                    " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
                    " AND is_gift = '$act_id'" .
                    " AND goods_id " . db_create_in($_POST['gift']);
                $gift_name = $GLOBALS['db']->getCol($sql);
                if (!empty($gift_name)) {
                    return $this->show_message(sprintf($GLOBALS['_LANG']['gift_in_cart'], join(',', $gift_name)));
                }

                /* 检查数量是否超过上限 */
                $count = isset($cart_favourable[$act_id]) ? $cart_favourable[$act_id] : 0;
                if ($favourable['act_type_ext'] > 0 && $count + count($_POST['gift']) > $favourable['act_type_ext']) {
                    return $this->show_message($GLOBALS['_LANG']['gift_count_exceed']);
                }

                /* 添加赠品到购物车 */
                foreach ($favourable['gift'] as $gift) {
                    if (in_array($gift['id'], $_POST['gift'])) {
                        $this->add_gift_to_cart($act_id, $gift['id'], $gift['price']);
                    }
                }
            } elseif ($favourable['act_type'] == FAT_DISCOUNT) {
                $this->add_favourable_to_cart($act_id, $favourable['act_name'], $this->cart_favourable_amount($favourable) * (100 - $favourable['act_type_ext']) / 100);
            } elseif ($favourable['act_type'] == FAT_PRICE) {
                $this->add_favourable_to_cart($act_id, $favourable['act_name'], $favourable['act_type_ext']);
            }

            /* 刷新购物车 */
            return $this->redirect('flow.php');

        } elseif ($_REQUEST['step'] == 'clear') {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id='" . SESS_ID . "'";
            if ($_SESSION['user_id']) {
                $sql .= " OR user_id='" . intval($_SESSION['user_id']) . "'";
            }
            $GLOBALS['db']->query($sql);

            return $this->redirect('/');
        } elseif ($_REQUEST['step'] == 'drop_to_collect') {
            $where = "session_id = '" . SESS_ID . "'";
            if ($_SESSION['user_id'] > 0) {
                $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
                $rec_id = intval($_GET['id']);
                $goods_id = $GLOBALS['db']->getOne("SELECT  goods_id FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id = '$rec_id' AND " . $where);
                $count = $GLOBALS['db']->getOne("SELECT goods_id FROM " . $GLOBALS['ecs']->table('collect_goods') . " WHERE user_id = '$_SESSION[user_id]' AND goods_id = '$goods_id'");
                if (empty($count)) {
                    $time = gmtime();
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('collect_goods') . " (user_id, goods_id, add_time)" .
                        "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";
                    $GLOBALS['db']->query($sql);
                }
                $this->flow_drop_cart_goods($rec_id);
            }
            return $this->redirect('flow.php');

        } /* 验证红包序列号 */
        elseif ($_REQUEST['step'] == 'validate_bonus') {
            $bonus_sn = trim($_REQUEST['bonus_sn']);
            if (is_numeric($bonus_sn)) {
                $bonus = bonus_info(0, $bonus_sn);
            } else {
                $bonus = array();
            }

//    if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0)
//    {
//        die($GLOBALS['_LANG']['bonus_sn_error']);
//    }
//    if ($bonus['min_goods_amount'] > cart_amount())
//    {
//        die(sprintf($GLOBALS['_LANG']['bonus_min_amount_error'], price_format($bonus['min_goods_amount'], false)));
//    }
//    die(sprintf($GLOBALS['_LANG']['bonus_is_ok'], price_format($bonus['type_money'], false)));
            $bonus_kill = price_format($bonus['type_money'], false);

            $result = array('error' => '', 'content' => '');

            /* 取得购物类型 */
            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

            /* 获得收货人信息 */
            $consignee = get_consignee($_SESSION['user_id']);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

            if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            } else {
                /* 取得购物流程设置 */
                $this->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();


                if (((!empty($bonus) && $bonus['user_id'] == $_SESSION['user_id']) || ($bonus['type_money'] > 0 && empty($bonus['user_id']))) && $bonus['order_id'] <= 0) {
                    //$order['bonus_kill'] = $bonus['type_money'];
                    $now = gmtime();
                    if ($now > $bonus['use_end_date']) {
                        $order['bonus_id'] = '';
                        $result['error'] = $GLOBALS['_LANG']['bonus_use_expire'];
                    } else {
                        $order['bonus_id'] = $bonus['bonus_id'];
                        $order['bonus_sn'] = $bonus_sn;
                    }
                } else {
                    //$order['bonus_kill'] = 0;
                    $order['bonus_id'] = '';
                    $result['error'] = $GLOBALS['_LANG']['invalid_bonus'];
                }

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee);

                if ($total['goods_price'] < $bonus['min_goods_amount']) {
                    $order['bonus_id'] = '';
                    /* 重新计算订单 */
                    $total = order_fee($order, $cart_goods, $consignee);
                    $result['error'] = sprintf($GLOBALS['_LANG']['bonus_min_amount_error'], price_format($bonus['min_goods_amount'], false));
                }

                $this->assign('total', $total);

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->assign('is_group_buy', 1);
                }

                $result['content'] = $GLOBALS['smarty']->fetch('library/order_total');
            }
            die(json_encode($result));
        }
        /*------------------------------------------------------ */
        //-- 添加礼包到购物车
        /*------------------------------------------------------ */
        elseif ($_REQUEST['step'] == 'add_package_to_cart') {
            $_POST['package_info'] = json_str_iconv($_POST['package_info']);

            $result = array('error' => 0, 'message' => '', 'content' => '', 'package_id' => '');

            if (empty($_POST['package_info'])) {
                $result['error'] = 1;
                die(json_encode($result));
            }

            $package = json_decode($_POST['package_info']);

            /* 如果是一步购物，先清空购物车 */
            if ($GLOBALS['_CFG']['one_step_buy'] == '1') {
                clear_cart();
            }

            /* 商品数量是否合法 */
            if (!is_numeric($package->number) || intval($package->number) <= 0) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['invalid_number'];
            } else {
                /* 添加到购物车 */
                if (add_package_to_cart($package->package_id, $package->number)) {
                    if ($GLOBALS['_CFG']['cart_confirm'] > 2) {
                        $result['message'] = '';
                    } else {
                        $result['message'] = $GLOBALS['_CFG']['cart_confirm'] == 1 ? $GLOBALS['_LANG']['addto_cart_success_1'] : $GLOBALS['_LANG']['addto_cart_success_2'];
                    }

                    $result['content'] = insert_cart_info();
                    $result['one_step_buy'] = $GLOBALS['_CFG']['one_step_buy'];
                } else {
                    $result['message'] = $GLOBALS['err']->last_message();
                    $result['error'] = $GLOBALS['err']->error_no;
                    $result['package_id'] = stripslashes($package->package_id);
                }
            }
            $result['confirm_type'] = !empty($GLOBALS['_CFG']['cart_confirm']) ? $GLOBALS['_CFG']['cart_confirm'] : 2;
            die(json_encode($result));
        } elseif ($_REQUEST['step'] == 'repurchase') {
            $order_id = intval($_POST['order_id']);
            $order_id = json_str_iconv($order_id);
            $user_id = $_SESSION['user_id'];
            $order = $GLOBALS['db']->getOne('SELECT count(*) FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE order_id = ' . $order_id . ' and user_id = ' . $user_id);
            if (!$order) {
                $result = array('error' => 1, 'message' => $GLOBALS['_LANG']['repurchase_fail']);
                die(json_encode($result));
            }

            $GLOBALS['db']->query('DELETE FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE rec_type = " . CART_REPURCHASE);
            $order_goods = $GLOBALS['db']->getAll("SELECT goods_id, goods_number, goods_attr_id, parent_id FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = " . $order_id);
            $result = array('error' => 0, 'message' => '');
            foreach ($order_goods as $goods) {
                $spec = empty($goods['goods_attr_id']) ? array() : explode(',', $goods['goods_attr_id']);
                if (!addto_cart($goods['goods_id'], $goods['goods_number'], $spec, $goods['parent_id'], CART_REPURCHASE)) {
                    $result = false;
                    $result = array('error' => 1, 'message' => $GLOBALS['_LANG']['repurchase_fail']);
                }
            }
            die(json_encode($result));
        } else {
            $flow_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : CART_GENERAL_GOODS;
            $flow_type = strip_tags($flow_type);
            $flow_type = json_str_iconv($flow_type);
            /* 标记购物流程为普通商品 */
            $_SESSION['flow_type'] = $flow_type;

            /* 如果是一步购物，跳到结算中心 */
            if ($GLOBALS['_CFG']['one_step_buy'] == '1') {
                return $this->redirect('flow.php?step=checkout');

            }

            /* 取得商品列表，计算合计 */
            $cart_goods = get_cart_goods($flow_type);
            $this->assign('goods_list', $cart_goods['goods_list']);
            $this->assign('total', $cart_goods['total']);

            //购物车的描述的格式化
            $this->assign('shopping_money', sprintf($GLOBALS['_LANG']['shopping_money'], $cart_goods['total']['goods_price']));
            $this->assign('market_price_desc', sprintf(
                $GLOBALS['_LANG']['than_market_price'],
                $cart_goods['total']['market_price'],
                $cart_goods['total']['saving'],
                $cart_goods['total']['save_rate']
            ));

            // 显示收藏夹内的商品
            if ($_SESSION['user_id'] > 0) {
                load_helper('clips');
                $collection_goods = get_collection_goods($_SESSION['user_id']);
                $this->assign('collection_goods', $collection_goods);
                $where = "user_id = '" . intval($_SESSION['user_id']) . "' ";
            } else {
                $where = "session_id = '" . SESS_ID . "' ";
            }

            /* 取得优惠活动 */
            $favourable_list = $this->favourable_list($_SESSION['user_rank']);
            usort($favourable_list, [$this, 'cmp_favourable']);

            $this->assign('favourable_list', $favourable_list);

            /* 计算折扣 */
            $discount = compute_discount();
            $this->assign('discount', $discount['discount']);
            $favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
            $this->assign('your_discount', sprintf($GLOBALS['_LANG']['your_discount'], $favour_name, price_format($discount['discount'])));

            /* 增加是否在购物车里显示商品图 */
            $this->assign('show_goods_thumb', $GLOBALS['_CFG']['show_goods_in_cart']);

            /* 增加是否在购物车里显示商品属性 */
            $this->assign('show_goods_attribute', $GLOBALS['_CFG']['show_attr_in_cart']);

            /* 购物车中商品配件列表 */
            //取得购物车中基本件ID
            // $sql = "SELECT goods_id " .
            //         "FROM " . $GLOBALS['ecs']->table('cart') .
            //         " WHERE session_id = '" . SESS_ID . "' " .
            //         "AND rec_type = '" . $flow_type . "' " .
            //         "AND is_gift = 0 " .
            //         "AND extension_code <> 'package_buy' " .
            //         "AND parent_id = 0 ";
            $sql = "SELECT goods_id " .
                "FROM " . $GLOBALS['ecs']->table('cart') .
                "WHERE " . $where .
                "AND rec_type = '" . $flow_type . "' " .
                "AND is_gift = 0 " .
                "AND extension_code <> 'package_buy' " .
                "AND parent_id = 0 ";
            $parent_list = $GLOBALS['db']->getCol($sql);

            $fittings_list = get_goods_fittings($parent_list);

            $this->assign('fittings_list', $fittings_list);
        }

        $this->assign('currency_format', $GLOBALS['_CFG']['currency_format']);
        $this->assign('integral_scale', $GLOBALS['_CFG']['integral_scale']);
        $this->assign('step', $_REQUEST['step']);
        assign_dynamic('shopping_flow');

        return $this->fetch('flow');
    }

    /**
     * 获得用户的可用积分
     *
     * @access  private
     * @return  integral
     */
    private function flow_available_points()
    {
        $where = "c.session_id = '" . SESS_ID . "'";
        if ($_SESSION['user_id']) {
            $where = "c.user_id = '" . intval($_SESSION['user_id']) . "'";
        }
        $sql = "SELECT SUM(g.integral * c.goods_number) " .
            "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE " . $where . " AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";

        $val = intval($GLOBALS['db']->getOne($sql));

        return integral_of_value($val);
    }

    /**
     * 更新购物车中的商品数量
     *
     * @access  public
     * @param array $arr
     * @return  void
     */
    private function flow_update_cart($arr)
    {
        $where = "session_id = '" . SESS_ID . "'";
        if ($_SESSION['user_id']) {
            $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
        }

        /* 处理 */
        foreach ($arr as $key => $val) {
            $val = intval(make_semiangle($val));
            if ($val <= 0 || !is_numeric($key)) {
                continue;
            }

            //查询：
            $sql = "SELECT `goods_id`, `goods_attr_id`, `product_id`, `extension_code` FROM" . $GLOBALS['ecs']->table('cart') .
                " WHERE rec_id='$key' AND " . $where;
            $goods = $GLOBALS['db']->getRow($sql);

            $sql = "SELECT g.goods_name, g.goods_number " .
                "FROM " . $GLOBALS['ecs']->table('goods') . " AS g, " .
                $GLOBALS['ecs']->table('cart') . " AS c " .
                "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
            $row = $GLOBALS['db']->getRow($sql);

            //查询：系统启用了库存，检查输入的商品数量是否有效
            if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy') {
                if ($row['goods_number'] < $val) {
                    return $this->show_message(sprintf(
                        $GLOBALS['_LANG']['stock_insufficiency'],
                        $row['goods_name'],
                        $row['goods_number'],
                        $row['goods_number']
                    ));

                }
                /* 是货品 */
                $goods['product_id'] = trim($goods['product_id']);
                if (!empty($goods['product_id'])) {
                    $sql = "SELECT product_number FROM " . $GLOBALS['ecs']->table('products') . " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $goods['product_id'] . "'";

                    $product_number = $GLOBALS['db']->getOne($sql);
                    if ($product_number < $val) {
                        return $this->show_message(sprintf(
                            $GLOBALS['_LANG']['stock_insufficiency'],
                            $row['goods_name'],
                            $product_number['product_number'],
                            $product_number['product_number']
                        ));

                    }
                }
            } elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy') {
                if (judge_package_stock($goods['goods_id'], $val)) {
                    return $this->show_message($GLOBALS['_LANG']['package_stock_insufficiency']);

                }
            }

            /* 查询：检查该项是否为基本件 以及是否存在配件 */
            /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_id goods_number为1 */
            $sql = "SELECT b.goods_number, b.rec_id
                FROM " . $GLOBALS['ecs']->table('cart') . " a, " . $GLOBALS['ecs']->table('cart') . " b
                WHERE a.rec_id = '$key'
                AND a." . $where . "
                AND a.extension_code <> 'package_buy'
                AND b.parent_id = a.goods_id
                AND b." . $where;

            $offers_accessories_res = $GLOBALS['db']->query($sql);

            //订货数量大于0
            if ($val > 0) {
                /* 判断是否为超出数量的优惠价格的配件 删除*/
                $row_num = 1;
                foreach ($offers_accessories_res as $offers_accessories_row) {
                    if ($row_num > $val) {
                        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                            " WHERE " . $where .
                            "AND rec_id = '" . $offers_accessories_row['rec_id'] . "' LIMIT 1";
                        $GLOBALS['db']->query($sql);
                    }

                    $row_num++;
                }

                /* 处理超值礼包 */
                if ($goods['extension_code'] == 'package_buy') {
                    //更新购物车中的商品数量
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('cart') .
                        " SET goods_number = '$val' WHERE rec_id='$key' AND " . $where;
                } /* 处理普通商品或非优惠的配件 */
                else {
                    $attr_id = empty($goods['goods_attr_id']) ? array() : explode(',', $goods['goods_attr_id']);
                    $goods_price = get_final_price($goods['goods_id'], $val, true, $attr_id);

                    //更新购物车中的商品数量
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('cart') .
                        " SET goods_number = '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND " . $where;
                }
            } //订货数量等于0
            else {
                /* 如果是基本件并且有优惠价格的配件则删除优惠价格的配件 */
                foreach ($offers_accessories_res as $offers_accessories_row) {
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                        " WHERE " . $where .
                        "AND rec_id = '" . $offers_accessories_row['rec_id'] . "' LIMIT 1";
                    $GLOBALS['db']->query($sql);
                }

                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE rec_id='$key' AND " . $where;
            }

            $GLOBALS['db']->query($sql);
        }

        /* 删除所有赠品 */
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE " . $where . " AND is_gift <> 0";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 检查订单中商品库存
     *
     * @access  public
     * @param array $arr
     *
     * @return  void
     */
    private function flow_cart_stock($arr)
    {
        $where = "session_id = '" . SESS_ID . "'";
        if ($_SESSION['user_id']) {
            $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
        }
        foreach ($arr as $key => $val) {
            $val = intval(make_semiangle($val));
            if ($val <= 0 || !is_numeric($key)) {
                continue;
            }

            $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM" . $GLOBALS['ecs']->table('cart') .
                " WHERE rec_id='$key' AND " . $where;
            $goods = $GLOBALS['db']->getRow($sql);

            $sql = "SELECT g.goods_name, g.goods_number, c.product_id " .
                "FROM " . $GLOBALS['ecs']->table('goods') . " AS g, " .
                $GLOBALS['ecs']->table('cart') . " AS c " .
                "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
            $row = $GLOBALS['db']->getRow($sql);

            //系统启用了库存，检查输入的商品数量是否有效
            if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy') {
                if ($row['goods_number'] < $val) {
                    return $this->show_message(sprintf(
                        $GLOBALS['_LANG']['stock_insufficiency'],
                        $row['goods_name'],
                        $row['goods_number'],
                        $row['goods_number']
                    ));

                }

                /* 是货品 */
                $row['product_id'] = trim($row['product_id']);
                if (!empty($row['product_id'])) {
                    $sql = "SELECT product_number FROM " . $GLOBALS['ecs']->table('products') . " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
                    $product_number = $GLOBALS['db']->getOne($sql);
                    if ($product_number < $val) {
                        return $this->show_message(sprintf(
                            $GLOBALS['_LANG']['stock_insufficiency'],
                            $row['goods_name'],
                            $row['goods_number'],
                            $row['goods_number']
                        ));

                    }
                }
            } elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy') {
                if (judge_package_stock($goods['goods_id'], $val)) {
                    return $this->show_message($GLOBALS['_LANG']['package_stock_insufficiency']);

                }
            }
        }
    }

    /**
     * 删除购物车中的商品
     *
     * @access  public
     * @param integer $id
     * @return  void
     */
    private function flow_drop_cart_goods($id)
    {
        $where = "session_id = '" . SESS_ID . "'";
        if ($_SESSION['user_id']) {
            $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
        }

        /* 取得商品id */
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id = '$id'";
        $row = $GLOBALS['db']->getRow($sql);
        if ($row) {
            //如果是超值礼包
            if ($row['extension_code'] == 'package_buy') {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE " . $where .
                    "AND rec_id = '$id' LIMIT 1";
            } //如果是普通商品，同时删除所有赠品及其配件
            elseif ($row['parent_id'] == 0 && $row['is_gift'] == 0) {
                /* 检查购物车中该普通商品的不可单独销售的配件并删除 */
                $sql = "SELECT c.rec_id
                    FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('group_goods') . " AS gg, " . $GLOBALS['ecs']->table('goods') . " AS g
                    WHERE gg.parent_id = '" . $row['goods_id'] . "'
                    AND c.goods_id = gg.goods_id
                    AND c.parent_id = '" . $row['goods_id'] . "'
                    AND c.extension_code <> 'package_buy'
                    AND gg.goods_id = g.goods_id
                    AND g.is_alone_sale = 0";
                $res = $GLOBALS['db']->query($sql);
                $_del_str = $id . ',';
                foreach ($res as $id_alone_sale_goods) {
                    $_del_str .= $id_alone_sale_goods['rec_id'] . ',';
                }
                $_del_str = trim($_del_str, ',');

                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE " . $where .
                    "AND (rec_id IN ($_del_str) OR parent_id = '$row[goods_id]' OR is_gift <> 0)";
            } //如果不是普通商品，只删除该商品即可
            else {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE " . $where .
                    "AND rec_id = '$id' LIMIT 1";
            }

            $GLOBALS['db']->query($sql);
        }

        $this->flow_clear_cart_alone();
    }

    /**
     * 删除购物车中不能单独销售的商品
     *
     * @access  public
     * @return  void
     */
    private function flow_clear_cart_alone()
    {
        $where = "session_id = '" . SESS_ID . "'";
        if ($_SESSION['user_id']) {
            $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
        }
        /* 查询：购物车中所有不可以单独销售的配件 */
        $sql = "SELECT c.rec_id, gg.parent_id
            FROM " . $GLOBALS['ecs']->table('cart') . " AS c
                LEFT JOIN " . $GLOBALS['ecs']->table('group_goods') . " AS gg ON c.goods_id = gg.goods_id
                LEFT JOIN" . $GLOBALS['ecs']->table('goods') . " AS g ON c.goods_id = g.goods_id
            WHERE c." . $where . "
            AND c.extension_code <> 'package_buy'
            AND gg.parent_id > 0
            AND g.is_alone_sale = 0";
        $res = $GLOBALS['db']->query($sql);
        $rec_id = array();
        foreach ($res as $row) {
            $rec_id[$row['rec_id']][] = $row['parent_id'];
        }

        if (empty($rec_id)) {
            return;
        }

        /* 查询：购物车中所有商品 */
        $sql = "SELECT DISTINCT goods_id
            FROM " . $GLOBALS['ecs']->table('cart') . "
            WHERE " . $where . "
            AND extension_code <> 'package_buy'";
        $res = $GLOBALS['db']->query($sql);
        $cart_good = array();
        foreach ($res as $row) {
            $cart_good[] = $row['goods_id'];
        }

        if (empty($cart_good)) {
            return;
        }

        /* 如果购物车中不可以单独销售配件的基本件不存在则删除该配件 */
        $del_rec_id = '';
        foreach ($rec_id as $key => $value) {
            foreach ($value as $v) {
                if (in_array($v, $cart_good)) {
                    continue 2;
                }
            }

            $del_rec_id = $key . ',';
        }
        $del_rec_id = trim($del_rec_id, ',');

        if ($del_rec_id == '') {
            return;
        }

        /* 删除 */
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . "
            WHERE " . $where . "
            AND rec_id IN ($del_rec_id)";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 比较优惠活动的函数，用于排序（把可用的排在前面）
     * @param array $a 优惠活动a
     * @param array $b 优惠活动b
     * @return  int     相等返回0，小于返回-1，大于返回1
     */
    private function cmp_favourable($a, $b)
    {
        if ($a['available'] == $b['available']) {
            if ($a['sort_order'] == $b['sort_order']) {
                return 0;
            } else {
                return $a['sort_order'] < $b['sort_order'] ? -1 : 1;
            }
        } else {
            return $a['available'] ? -1 : 1;
        }
    }

    /**
     * 取得某用户等级当前时间可以享受的优惠活动
     * @param int $user_rank 用户等级id，0表示非会员
     * @return  array
     */
    private function favourable_list($user_rank)
    {
        /* 购物车中已有的优惠活动及数量 */
        $used_list = $this->cart_favourable();

        /* 当前用户可享受的优惠活动 */
        $favourable_list = array();
        $user_rank = ',' . $user_rank . ',';
        $now = gmtime();
        $sql = "SELECT * " .
            "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND start_time <= '$now' AND end_time >= '$now'" .
            " AND act_type = '" . FAT_GOODS . "'" .
            " ORDER BY sort_order";
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $favourable) {
            $favourable['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['start_time']);
            $favourable['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['end_time']);
            $favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
            $favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);
            $favourable['gift'] = unserialize($favourable['gift']);

            foreach ($favourable['gift'] as $key => $value) {
                $favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);
                $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_on_sale = 1 AND goods_id = " . $value['id'];
                $is_sale = $GLOBALS['db']->getOne($sql);
                if (!$is_sale) {
                    unset($favourable['gift'][$key]);
                }
            }

            $favourable['act_range_desc'] = $this->act_range_desc($favourable);
            $favourable['act_type_desc'] = sprintf($GLOBALS['_LANG']['fat_ext'][$favourable['act_type']], $favourable['act_type_ext']);

            /* 是否能享受 */
            $favourable['available'] = $this->favourable_available($favourable);
            if ($favourable['available']) {
                /* 是否尚未享受 */
                $favourable['available'] = !$this->favourable_used($favourable, $used_list);
            }

            $favourable_list[] = $favourable;
        }

        return $favourable_list;
    }

    /**
     * 根据购物车判断是否可以享受某优惠活动
     * @param array $favourable 优惠活动信息
     * @return  bool
     */
    private function favourable_available($favourable)
    {
        /* 会员等级是否符合 */
        $user_rank = $_SESSION['user_rank'];
        if (strpos(',' . $favourable['user_rank'] . ',', ',' . $user_rank . ',') === false) {
            return false;
        }

        /* 优惠范围内的商品总额 */
        $amount = $this->cart_favourable_amount($favourable);

        /* 金额上限为0表示没有上限 */
        return $amount >= $favourable['min_amount'] &&
            ($amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0);
    }

    /**
     * 取得优惠范围描述
     * @param array $favourable 优惠活动
     * @return  string
     */
    private function act_range_desc($favourable)
    {
        if ($favourable['act_range'] == FAR_BRAND) {
            $sql = "SELECT brand_name FROM " . $GLOBALS['ecs']->table('brand') .
                " WHERE brand_id " . db_create_in($favourable['act_range_ext']);
            return join(',', $GLOBALS['db']->getCol($sql));
        } elseif ($favourable['act_range'] == FAR_CATEGORY) {
            $sql = "SELECT cat_name FROM " . $GLOBALS['ecs']->table('category') .
                " WHERE cat_id " . db_create_in($favourable['act_range_ext']);
            return join(',', $GLOBALS['db']->getCol($sql));
        } elseif ($favourable['act_range'] == FAR_GOODS) {
            $sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') .
                " WHERE goods_id " . db_create_in($favourable['act_range_ext']);
            return join(',', $GLOBALS['db']->getCol($sql));
        } else {
            return '';
        }
    }

    /**
     * 取得购物车中已有的优惠活动及数量
     * @return  array
     */
    private function cart_favourable()
    {
        $where = "session_id = '" . SESS_ID . "'";
        if ($_SESSION['user_id']) {
            $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
        }
        $list = array();
        $sql = "SELECT is_gift, COUNT(*) AS num " .
            "FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE " . $where .
            " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
            " AND is_gift > 0" .
            " GROUP BY is_gift";
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            $list[$row['is_gift']] = $row['num'];
        }

        return $list;
    }

    /**
     * 购物车中是否已经有某优惠
     * @param array $favourable 优惠活动
     * @param array $cart_favourable购物车中已有的优惠活动及数量
     */
    private function favourable_used($favourable, $cart_favourable)
    {
        if ($favourable['act_type'] == FAT_GOODS) {
            return isset($cart_favourable[$favourable['act_id']]) &&
                $cart_favourable[$favourable['act_id']] >= $favourable['act_type_ext'] &&
                $favourable['act_type_ext'] > 0;
        } else {
            return isset($cart_favourable[$favourable['act_id']]);
        }
    }

    /**
     * 添加优惠活动（赠品）到购物车
     * @param int $act_id 优惠活动id
     * @param int $id 赠品id
     * @param float $price 赠品价格
     */
    private function add_gift_to_cart($act_id, $id, $price)
    {
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . " (" .
            "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, " .
            "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) " .
            "SELECT '$_SESSION[user_id]', '" . SESS_ID . "', goods_id, goods_sn, goods_name, market_price, " .
            "'$price', 1, is_real, extension_code, 0, '$act_id', '" . CART_GENERAL_GOODS . "' " .
            "FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_id = '$id'";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 添加优惠活动（非赠品）到购物车
     * @param int $act_id 优惠活动id
     * @param string $act_name 优惠活动name
     * @param float $amount 优惠金额
     */
    private function add_favourable_to_cart($act_id, $act_name, $amount)
    {
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . "(" .
            "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, " .
            "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) " .
            "VALUES('$_SESSION[user_id]', '" . SESS_ID . "', 0, '', '$act_name', 0, " .
            "'" . (-1) * $amount . "', 1, 0, '', 0, '$act_id', '" . CART_GENERAL_GOODS . "')";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 取得购物车中某优惠活动范围内的总金额
     * @param array $favourable 优惠活动
     * @return  float
     */
    private function cart_favourable_amount($favourable)
    {
        $where = "session_id = '" . SESS_ID . "'";
        if ($_SESSION['user_id']) {
            $where = "user_id = '" . intval($_SESSION['user_id']) . "'";
        }
        /* 查询优惠范围内商品总额的sql */
        $sql = "SELECT SUM(c.goods_price * c.goods_number) " .
            "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE c.goods_id = g.goods_id " .
            "AND c." . $where .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
            "AND c.is_gift = 0 " .
            "AND c.goods_id > 0 ";

        /* 根据优惠范围修正sql */
        if ($favourable['act_range'] == FAR_ALL) {
            // sql do not change
        } elseif ($favourable['act_range'] == FAR_CATEGORY) {
            /* 取得优惠范围分类的所有下级分类 */
            $id_list = array();
            $cat_list = explode(',', $favourable['act_range_ext']);
            foreach ($cat_list as $id) {
                $id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0, false)));
            }

            $sql .= "AND g.cat_id " . db_create_in($id_list);
        } elseif ($favourable['act_range'] == FAR_BRAND) {
            $id_list = explode(',', $favourable['act_range_ext']);

            $sql .= "AND g.brand_id " . db_create_in($id_list);
        } else {
            $id_list = explode(',', $favourable['act_range_ext']);

            $sql .= "AND g.goods_id " . db_create_in($id_list);
        }

        /* 优惠范围内的商品总额 */
        return $GLOBALS['db']->getOne($sql);
    }
}
