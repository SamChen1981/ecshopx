<?php

namespace app\console\controller;

/**
 * 红包类型的处理
 */
class Bonus extends Init
{
    public function index()
    {


        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /* 初始化$exc对象 */
        $exc = new Exchange($GLOBALS['ecs']->table('bonus_type'), $GLOBALS['db'], 'type_id', 'type_name');

        /*------------------------------------------------------ */
        //-- 红包类型列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->assign('ur_here', $GLOBALS['_LANG']['04_bonustype_list']);
            $this->assign('action_link', array('text' => $GLOBALS['_LANG']['bonustype_add'], 'href' => 'bonus.php?act=add'));
            $this->assign('full_page', 1);

            $list = $this->get_type_list();

            $this->assign('type_list', $list['item']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            assign_query_info();
            return $this->fetch('bonus_type');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            $list = $this->get_type_list();

            $this->assign('type_list', $list['item']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->fetch('bonus_type.htm'),
                '',
                array('filter' => $list['filter'], 'page_count' => $list['page_count'])
            );
        }

        /*------------------------------------------------------ */
        //-- 编辑红包类型名称
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_type_name') {
            check_authz_json('bonus_manage');

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 检查红包类型名称是否重复 */
            if (!$exc->is_only('type_name', $id, $val)) {
                return make_json_error($GLOBALS['_LANG']['type_name_exist']);
            } else {
                $exc->edit("type_name='$val'", $id);

                return make_json_result(stripslashes($val));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑红包金额
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_type_money') {
            check_authz_json('bonus_manage');

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            /* 检查红包类型名称是否重复 */
            if ($val <= 0) {
                return make_json_error($GLOBALS['_LANG']['type_money_error']);
            } else {
                $exc->edit("type_money='$val'", $id);

                return make_json_result(number_format($val, 2));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑订单下限
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_min_amount') {
            check_authz_json('bonus_manage');

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            if ($val < 0) {
                return make_json_error($GLOBALS['_LANG']['min_amount_empty']);
            } else {
                $exc->edit("min_amount='$val'", $id);

                return make_json_result(number_format($val, 2));
            }
        }

        /*------------------------------------------------------ */
        //-- 删除红包类型
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            check_authz_json('bonus_manage');

            $id = intval($_GET['id']);

            $exc->drop($id);

            /* 更新商品信息 */
            $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('goods') . " SET bonus_type_id = 0 WHERE bonus_type_id = '$id'");

            /* 删除用户的红包 */
            $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('user_bonus') . " WHERE bonus_type_id = '$id'");

            $url = 'bonus.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return $this->redirect($url);

        }

        /*------------------------------------------------------ */
        //-- 红包类型添加页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            admin_priv('bonus_manage');

            $this->assign('lang', $GLOBALS['_LANG']);
            $this->assign('ur_here', $GLOBALS['_LANG']['bonustype_add']);
            $this->assign('action_link', array('href' => 'bonus.php?act=list', 'text' => $GLOBALS['_LANG']['04_bonustype_list']));
            $this->assign('action', 'add');

            $this->assign('form_act', 'insert');
            $this->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            $next_month = local_strtotime_new('+1 months');
            $bonus_arr['send_start_date'] = local_date('Y-m-d');
            $bonus_arr['use_start_date'] = local_date('Y-m-d');
            $bonus_arr['send_end_date'] = local_date('Y-m-d', $next_month);
            $bonus_arr['use_end_date'] = local_date('Y-m-d', $next_month);
            $bonus_arr['send_type'] = 0;
            $this->assign('bonus_arr', $bonus_arr);

            assign_query_info();
            return $this->fetch('bonus_type_info');
        }

        /*------------------------------------------------------ */
        //-- 红包类型添加的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            /* 去掉红包类型名称前后的空格 */
            $type_name = !empty($_POST['type_name']) ? trim($_POST['type_name']) : '';

            /* 初始化变量 */
            $type_id = !empty($_POST['type_id']) ? intval($_POST['type_id']) : 0;
            $min_amount = !empty($_POST['min_amount']) ? intval($_POST['min_amount']) : 0;

            /* 检查类型是否有重复 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('bonus_type') . " WHERE type_name='$type_name'";
            if ($GLOBALS['db']->getOne($sql) > 0) {
                $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                return sys_msg($GLOBALS['_LANG']['type_name_exist'], 0, $link);
            }

            /* 获得日期信息 */
            $send_startdate = local_strtotime_new($_POST['send_start_date']);
            $send_enddate = local_strtotime_new($_POST['send_end_date']);
            $use_startdate = local_strtotime_new($_POST['use_start_date']);
            $use_enddate = local_strtotime_new($_POST['use_end_date']);

            /* 插入数据库。 */
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('bonus_type') . " (type_name, type_money,send_start_date,send_end_date,use_start_date,use_end_date,send_type,min_amount,min_goods_amount)
    VALUES ('$type_name',
            '$_POST[type_money]',
            '$send_startdate',
            '$send_enddate',
            '$use_startdate',
            '$use_enddate',
            '$_POST[send_type]',
            '$min_amount','" . floatval($_POST['min_goods_amount']) . "')";

            $GLOBALS['db']->query($sql);
            /* 记录管理员操作 */
            admin_log($_POST['type_name'], 'add', 'bonustype');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['continus_add'];
            $link[0]['href'] = 'bonus.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'bonus.php?act=list';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['type_name'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 红包类型编辑页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            admin_priv('bonus_manage');

            /* 获取红包类型数据 */
            $type_id = !empty($_GET['type_id']) ? intval($_GET['type_id']) : 0;
            $bonus_arr = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('bonus_type') . " WHERE type_id = '$type_id'");

            $bonus_arr['send_start_date'] = local_date('Y-m-d', $bonus_arr['send_start_date']);
            $bonus_arr['send_end_date'] = local_date('Y-m-d', $bonus_arr['send_end_date']);
            $bonus_arr['use_start_date'] = local_date('Y-m-d', $bonus_arr['use_start_date']);
            $bonus_arr['use_end_date'] = local_date('Y-m-d', $bonus_arr['use_end_date']);

            $this->assign('lang', $GLOBALS['_LANG']);
            $this->assign('ur_here', $GLOBALS['_LANG']['bonustype_edit']);
            $this->assign('action_link', array('href' => 'bonus.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['04_bonustype_list']));
            $this->assign('form_act', 'update');
            $this->assign('bonus_arr', $bonus_arr);

            assign_query_info();
            return $this->fetch('bonus_type_info');
        }

        /*------------------------------------------------------ */
        //-- 红包类型编辑的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 获得日期信息 */
            $send_startdate = local_strtotime_new($_POST['send_start_date']);
            $send_enddate = local_strtotime_new($_POST['send_end_date']);
            $use_startdate = local_strtotime_new($_POST['use_start_date']);
            $use_enddate = local_strtotime_new($_POST['use_end_date']);

            /* 对数据的处理 */
            $type_name = !empty($_POST['type_name']) ? trim($_POST['type_name']) : '';
            $type_id = !empty($_POST['type_id']) ? intval($_POST['type_id']) : 0;
            $min_amount = !empty($_POST['min_amount']) ? intval($_POST['min_amount']) : 0;

            $sql = "UPDATE " . $GLOBALS['ecs']->table('bonus_type') . " SET " .
                "type_name       = '$type_name', " .
                "type_money      = '$_POST[type_money]', " .
                "send_start_date = '$send_startdate', " .
                "send_end_date   = '$send_enddate', " .
                "use_start_date  = '$use_startdate', " .
                "use_end_date    = '$use_enddate', " .
                "send_type       = '$_POST[send_type]', " .
                "min_amount      = '$min_amount', " .
                "min_goods_amount = '" . floatval($_POST['min_goods_amount']) . "' " .
                "WHERE type_id   = '$type_id'";

            $GLOBALS['db']->query($sql);
            /* 记录管理员操作 */
            admin_log($_POST['type_name'], 'edit', 'bonustype');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'bonus.php?act=list&' . list_link_postfix());
            return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . $_POST['type_name'] . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 红包发送页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'send') {
            admin_priv('bonus_manage');

            /* 取得参数 */
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : '';

            assign_query_info();

            $this->assign('ur_here', $GLOBALS['_LANG']['send_bonus']);
            $this->assign('action_link', array('href' => 'bonus.php?act=list', 'text' => $GLOBALS['_LANG']['04_bonustype_list']));

            if ($_REQUEST['send_by'] == SEND_BY_USER) {
                $this->assign('id', $id);
                $this->assign('ranklist', get_rank_list());

                return $this->fetch('bonus_by_user');
            } elseif ($_REQUEST['send_by'] == SEND_BY_GOODS) {
                /* 查询此红包类型信息 */
                $bonus_type = $GLOBALS['db']->GetRow("SELECT type_id, type_name FROM " . $GLOBALS['ecs']->table('bonus_type') .
                    " WHERE type_id='$_REQUEST[id]'");

                /* 查询红包类型的商品列表 */
                $goods_list = $this->get_bonus_goods($_REQUEST['id']);

                /* 查询其他红包类型的商品 */
                $sql = "SELECT goods_id FROM " . $GLOBALS['ecs']->table('goods') .
                    " WHERE bonus_type_id > 0 AND bonus_type_id <> '$_REQUEST[id]'";
                $other_goods_list = $GLOBALS['db']->getCol($sql);
                $this->assign('other_goods', join(',', $other_goods_list));

                /* 模板赋值 */
                $this->assign('cat_list', cat_list());
                $this->assign('brand_list', get_brand_list());

                $this->assign('bonus_type', $bonus_type);
                $this->assign('goods_list', $goods_list);

                return $this->fetch('bonus_by_goods');
            } elseif ($_REQUEST['send_by'] == SEND_BY_PRINT) {
                $this->assign('type_list', get_bonus_type());

                return $this->fetch('bonus_by_print');
            }
        }

        /*------------------------------------------------------ */
        //-- 处理红包的发送页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'send_by_user') {
            $user_list = array();
            $start = empty($_REQUEST['start']) ? 0 : intval($_REQUEST['start']);
            $limit = empty($_REQUEST['limit']) ? 10 : intval($_REQUEST['limit']);
            $validated_email = empty($_REQUEST['validated_email']) ? 0 : intval($_REQUEST['validated_email']);
            $send_count = 0;

            if (isset($_REQUEST['send_rank'])) {
                /* 按会员等级来发放红包 */
                $rank_id = intval($_REQUEST['rank_id']);

                if ($rank_id > 0) {
                    $sql = "SELECT min_points, max_points, special_rank FROM " . $GLOBALS['ecs']->table('user_rank') . " WHERE rank_id = '$rank_id'";
                    $row = $GLOBALS['db']->getRow($sql);
                    if ($row['special_rank']) {
                        /* 特殊会员组处理 */
                        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') . " WHERE user_rank = '$rank_id'";
                        $send_count = $GLOBALS['db']->getOne($sql);
                        if ($validated_email) {
                            $sql = 'SELECT user_id, email, user_name FROM ' . $GLOBALS['ecs']->table('users') .
                                " WHERE user_rank = '$rank_id' AND is_validated = 1" .
                                " LIMIT $start, $limit";
                        } else {
                            $sql = 'SELECT user_id, email, user_name FROM ' . $GLOBALS['ecs']->table('users') .
                                " WHERE user_rank = '$rank_id'" .
                                " LIMIT $start, $limit";
                        }
                    } else {
                        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') .
                            " WHERE rank_points >= " . intval($row['min_points']) . " AND rank_points < " . intval($row['max_points']);
                        $send_count = $GLOBALS['db']->getOne($sql);

                        if ($validated_email) {
                            $sql = 'SELECT user_id, email, user_name FROM ' . $GLOBALS['ecs']->table('users') .
                                " WHERE rank_points >= " . intval($row['min_points']) . " AND rank_points < " . intval($row['max_points']) .
                                " AND is_validated = 1 LIMIT $start, $limit";
                        } else {
                            $sql = 'SELECT user_id, email, user_name FROM ' . $GLOBALS['ecs']->table('users') .
                                " WHERE rank_points >= " . intval($row['min_points']) . " AND rank_points < " . intval($row['max_points']) .
                                " LIMIT $start, $limit";
                        }
                    }

                    $user_list = $GLOBALS['db']->getAll($sql);
                    $count = count($user_list);
                }
            } elseif (isset($_REQUEST['send_user'])) {
                /* 按会员列表发放红包 */
                /* 如果是空数组，直接返回 */
                if (empty($_REQUEST['user'])) {
                    return sys_msg($GLOBALS['_LANG']['send_user_empty'], 1);
                }

                $user_array = (is_array($_REQUEST['user'])) ? $_REQUEST['user'] : explode(',', $_REQUEST['user']);
                $send_count = count($user_array);

                $id_array = array_slice($user_array, $start, $limit);

                /* 根据会员ID取得用户名和邮件地址 */
                $sql = "SELECT user_id, email, user_name FROM " . $GLOBALS['ecs']->table('users') .
                    " WHERE user_id " . db_create_in($id_array);
                $user_list = $GLOBALS['db']->getAll($sql);
                $count = count($user_list);
            }

            /* 发送红包 */
            $loop = 0;
            $bonus_type = $this->bonus_type_info($_REQUEST['id']);

            $tpl = get_mail_template('send_bonus');
            $today = local_date($GLOBALS['_CFG']['date_format']);

            foreach ($user_list as $key => $val) {
                /* 发送邮件通知 */
                $this->assign('user_name', $val['user_name']);
                $this->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                $this->assign('send_date', $today);
                $this->assign('sent_date', $today);
                $this->assign('count', 1);
                $this->assign('money', price_format($bonus_type['type_money']));

                $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);

                if ($this->add_to_maillist($val['user_name'], $val['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                    /* 向会员红包表录入数据 */
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('user_bonus') .
                        "(bonus_type_id, bonus_sn, user_id, used_time, order_id, emailed) " .
                        "VALUES ('$_REQUEST[id]', 0, '$val[user_id]', 0, 0, " . BONUS_MAIL_SUCCEED . ")";
                    $GLOBALS['db']->query($sql);
                } else {
                    /* 邮件发送失败，更新数据库 */
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('user_bonus') .
                        "(bonus_type_id, bonus_sn, user_id, used_time, order_id, emailed) " .
                        "VALUES ('$_REQUEST[id]', 0, '$val[user_id]', 0, 0, " . BONUS_MAIL_FAIL . ")";
                    $GLOBALS['db']->query($sql);
                }

                if ($loop >= $limit) {
                    break;
                } else {
                    $loop++;
                }
            }

            //admin_log(addslashes($GLOBALS['_LANG']['send_bonus']), 'add', 'bonustype');
            if ($send_count > ($start + $limit)) {
                /*  */
                $href = "bonus.php?act=send_by_user&start=" . ($start + $limit) . "&limit=$limit&id=$_REQUEST[id]&";

                if (isset($_REQUEST['send_rank'])) {
                    $href .= "send_rank=1&rank_id=$rank_id";
                }

                if (isset($_REQUEST['send_user'])) {
                    $href .= "send_user=1&user=" . implode(',', $user_array);
                }

                $link[] = array('text' => $GLOBALS['_LANG']['send_continue'], 'href' => $href);
            }

            $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'bonus.php?act=list');

            return sys_msg(sprintf($GLOBALS['_LANG']['sendbonus_count'], $count), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 发送邮件
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'send_mail') {
            /* 取得参数：红包id */
            $bonus_id = intval($_REQUEST['bonus_id']);
            if ($bonus_id <= 0) {
                die('invalid params');
            }

            /* 取得红包信息 */
            load_helper('order');
            $bonus = bonus_info($bonus_id);
            if (empty($bonus)) {
                return sys_msg($GLOBALS['_LANG']['bonus_not_exist']);
            }

            /* 发邮件 */
            $count = $this->send_bonus_mail($bonus['bonus_type_id'], array($bonus_id));

            $link[0]['text'] = $GLOBALS['_LANG']['back_bonus_list'];
            $link[0]['href'] = 'bonus.php?act=bonus_list&bonus_type=' . $bonus['bonus_type_id'];

            return sys_msg(sprintf($GLOBALS['_LANG']['success_send_mail'], $count), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 按印刷品发放红包
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'send_by_print') {
            @set_time_limit(0);

            /* 红下红包的类型ID和生成的数量的处理 */
            $bonus_typeid = !empty($_POST['bonus_type_id']) ? $_POST['bonus_type_id'] : 0;
            $bonus_sum = !empty($_POST['bonus_sum']) ? $_POST['bonus_sum'] : 1;

            /* 生成红包序列号 */
            $num = $GLOBALS['db']->getOne("SELECT MAX(bonus_sn) FROM " . $GLOBALS['ecs']->table('user_bonus'));
            $num = $num ? floor($num / 10000) : 100000;

            for ($i = 0, $j = 0; $i < $bonus_sum; $i++) {
                $bonus_sn = ($num + $i) . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $GLOBALS['db']->query("INSERT INTO " . $GLOBALS['ecs']->table('user_bonus') . " (bonus_type_id, bonus_sn) VALUES('$bonus_typeid', '$bonus_sn')");

                $j++;
            }

            /* 记录管理员操作 */
            admin_log($bonus_sn, 'add', 'userbonus');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_bonus_list'];
            $link[0]['href'] = 'bonus.php?act=bonus_list&bonus_type=' . $bonus_typeid;

            return sys_msg($GLOBALS['_LANG']['creat_bonus'] . $j . $GLOBALS['_LANG']['creat_bonus_num'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 导出线下发放的信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'gen_excel') {
            @set_time_limit(0);

            /* 获得此线下红包类型的ID */
            $tid = !empty($_GET['tid']) ? intval($_GET['tid']) : 0;
            $type_name = $GLOBALS['db']->getOne("SELECT type_name FROM " . $GLOBALS['ecs']->table('bonus_type') . " WHERE type_id = '$tid'");

            /* 文件名称 */
            $bonus_filename = $type_name . '_bonus_list';
            if (EC_CHARSET != 'gbk') {
                $bonus_filename = ecs_iconv('UTF8', 'GB2312', $bonus_filename);
            }

            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=$bonus_filename.xls");

            /* 文件标题 */
            if (EC_CHARSET != 'gbk') {
                echo ecs_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['bonus_excel_file']) . "\t\n";
                /* 红包序列号, 红包金额, 类型名称(红包名称), 使用结束日期 */
                echo ecs_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['bonus_sn']) . "\t";
                echo ecs_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['type_money']) . "\t";
                echo ecs_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['type_name']) . "\t";
                echo ecs_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['use_enddate']) . "\t\n";
            } else {
                echo $GLOBALS['_LANG']['bonus_excel_file'] . "\t\n";
                /* 红包序列号, 红包金额, 类型名称(红包名称), 使用结束日期 */
                echo $GLOBALS['_LANG']['bonus_sn'] . "\t";
                echo $GLOBALS['_LANG']['type_money'] . "\t";
                echo $GLOBALS['_LANG']['type_name'] . "\t";
                echo $GLOBALS['_LANG']['use_enddate'] . "\t\n";
            }

            $val = array();
            $sql = "SELECT ub.bonus_id, ub.bonus_type_id, ub.bonus_sn, bt.type_name, bt.type_money, bt.use_end_date " .
                "FROM " . $GLOBALS['ecs']->table('user_bonus') . " AS ub, " . $GLOBALS['ecs']->table('bonus_type') . " AS bt " .
                "WHERE bt.type_id = ub.bonus_type_id AND ub.bonus_type_id = '$tid' ORDER BY ub.bonus_id DESC";
            $res = $GLOBALS['db']->query($sql);

            $code_table = array();
            foreach ($res as $val) {
                echo $val['bonus_sn'] . "\t";
                echo $val['type_money'] . "\t";
                if (!isset($code_table[$val['type_name']])) {
                    if (EC_CHARSET != 'gbk') {
                        $code_table[$val['type_name']] = ecs_iconv('UTF8', 'GB2312', $val['type_name']);
                    } else {
                        $code_table[$val['type_name']] = $val['type_name'];
                    }
                }
                echo $code_table[$val['type_name']] . "\t";
                echo local_date('Y-m-d', $val['use_end_date']);
                echo "\t\n";
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'get_goods_list') {
            $filters = json_decode($_GET['JSON']);

            $arr = get_goods_list($filters);
            $opt = array();

            foreach ($arr as $key => $val) {
                $opt[] = array('value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => $val['shop_price']);
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 添加发放红包的商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add_bonus_goods') {
            check_authz_json('bonus_manage');

            $add_ids = json_decode($_GET['add_ids']);
            $args = json_decode($_GET['JSON']);
            $type_id = $args[0];

            foreach ($add_ids as $key => $val) {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET bonus_type_id='$type_id' WHERE goods_id='$val'";
                $GLOBALS['db']->query($sql, 'SILENT') or return make_json_error($GLOBALS['db']->error());
            }

            /* 重新载入 */
            $arr = $this->get_bonus_goods($type_id);
            $opt = array();

            foreach ($arr as $key => $val) {
                $opt[] = array('value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => '');
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 删除发放红包的商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'drop_bonus_goods') {
            check_authz_json('bonus_manage');

            $drop_goods = json_decode($_GET['drop_ids']);
            $drop_goods_ids = db_create_in($drop_goods);
            $arguments = json_decode($_GET['JSON']);
            $type_id = $arguments[0];

            $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('goods') . " SET bonus_type_id = 0 " .
                "WHERE bonus_type_id = '$type_id' AND goods_id " . $drop_goods_ids);

            /* 重新载入 */
            $arr = $this->get_bonus_goods($type_id);
            $opt = array();

            foreach ($arr as $key => $val) {
                $opt[] = array('value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => '');
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 搜索用户
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'search_users') {
            $keywords = json_str_iconv(trim($_GET['keywords']));

            $sql = "SELECT user_id, user_name FROM " . $GLOBALS['ecs']->table('users') .
                " WHERE user_name LIKE '%" . mysql_like_quote($keywords) . "%' OR user_id LIKE '%" . mysql_like_quote($keywords) . "%'";
            $row = $GLOBALS['db']->getAll($sql);

            return make_json_result($row);
        }

        /*------------------------------------------------------ */
        //-- 红包列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'bonus_list') {
            $this->assign('full_page', 1);
            $this->assign('ur_here', $GLOBALS['_LANG']['bonus_list']);
            $this->assign('action_link', array('href' => 'bonus.php?act=list', 'text' => $GLOBALS['_LANG']['04_bonustype_list']));

            $list = $this->get_bonus_list();

            /* 赋值是否显示红包序列号 */
            $bonus_type = $this->bonus_type_info(intval($_REQUEST['bonus_type']));
            if ($bonus_type['send_type'] == SEND_BY_PRINT) {
                $this->assign('show_bonus_sn', 1);
            } /* 赋值是否显示发邮件操作和是否发过邮件 */
            elseif ($bonus_type['send_type'] == SEND_BY_USER) {
                $this->assign('show_mail', 1);
            }

            $this->assign('bonus_list', $list['item']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            assign_query_info();
            return $this->fetch('bonus_list');
        }

        /*------------------------------------------------------ */
        //-- 红包列表翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query_bonus') {
            $list = $this->get_bonus_list();

            /* 赋值是否显示红包序列号 */
            $bonus_type = $this->bonus_type_info(intval($_REQUEST['bonus_type']));
            if ($bonus_type['send_type'] == SEND_BY_PRINT) {
                $this->assign('show_bonus_sn', 1);
            } /* 赋值是否显示发邮件操作和是否发过邮件 */
            elseif ($bonus_type['send_type'] == SEND_BY_USER) {
                $this->assign('show_mail', 1);
            }

            $this->assign('bonus_list', $list['item']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->fetch('bonus_list.htm'),
                '',
                array('filter' => $list['filter'], 'page_count' => $list['page_count'])
            );
        }

        /*------------------------------------------------------ */
        //-- 删除红包
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_bonus') {
            check_authz_json('bonus_manage');

            $id = intval($_GET['id']);

            $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('user_bonus') . " WHERE bonus_id='$id'");

            $url = 'bonus.php?act=query_bonus&' . str_replace('act=remove_bonus', '', $_SERVER['QUERY_STRING']);

            return $this->redirect($url);

        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('bonus_manage');

            /* 去掉参数：红包类型 */
            $bonus_type_id = intval($_REQUEST['bonus_type']);

            /* 取得选中的红包id */
            if (isset($_POST['checkboxes'])) {
                $bonus_id_list = $_POST['checkboxes'];

                /* 删除红包 */
                if (isset($_POST['drop'])) {
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('user_bonus') . " WHERE bonus_id " . db_create_in($bonus_id_list);
                    $GLOBALS['db']->query($sql);

                    admin_log(count($bonus_id_list), 'remove', 'userbonus');

                    clear_cache_files();

                    $link[] = array('text' => $GLOBALS['_LANG']['back_bonus_list'],
                        'href' => 'bonus.php?act=bonus_list&bonus_type=' . $bonus_type_id);
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], count($bonus_id_list)), 0, $link);
                } /* 发邮件 */
                elseif (isset($_POST['mail'])) {
                    $count = $this->send_bonus_mail($bonus_type_id, $bonus_id_list);
                    $link[] = array('text' => $GLOBALS['_LANG']['back_bonus_list'],
                        'href' => 'bonus.php?act=bonus_list&bonus_type=' . $bonus_type_id);
                    return sys_msg(sprintf($GLOBALS['_LANG']['success_send_mail'], $count), 0, $link);
                }
            } else {
                return sys_msg($GLOBALS['_LANG']['no_select_bonus'], 1);
            }
        }
    }

    /**
     * 获取红包类型列表
     * @access  public
     * @return void
     */
    private function get_type_list()
    {
        /* 获得所有红包类型的发放数量 */
        $sql = "SELECT bonus_type_id, COUNT(*) AS sent_count" .
            " FROM " . $GLOBALS['ecs']->table('user_bonus') .
            " GROUP BY bonus_type_id";
        $res = $GLOBALS['db']->query($sql);

        $sent_arr = array();
        foreach ($res as $row) {
            $sent_arr[$row['bonus_type_id']] = $row['sent_count'];
        }

        /* 获得所有红包类型的发放数量 */
        $sql = "SELECT bonus_type_id, COUNT(*) AS used_count" .
            " FROM " . $GLOBALS['ecs']->table('user_bonus') .
            " WHERE used_time > 0" .
            " GROUP BY bonus_type_id";
        $res = $GLOBALS['db']->query($sql);

        $used_arr = array();
        foreach ($res as $row) {
            $used_arr[$row['bonus_type_id']] = $row['used_count'];
        }

        $result = get_filter();
        if ($result === false) {
            /* 查询条件 */
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'type_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('bonus_type');
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('bonus_type') . " ORDER BY $filter[sort_by] $filter[sort_order]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $arr = array();
        $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

        foreach ($res as $row) {
            $row['send_by'] = $GLOBALS['_LANG']['send_by'][$row['send_type']];
            $row['send_count'] = isset($sent_arr[$row['type_id']]) ? $sent_arr[$row['type_id']] : 0;
            $row['use_count'] = isset($used_arr[$row['type_id']]) ? $used_arr[$row['type_id']] : 0;

            $arr[] = $row;
        }

        $arr = array('item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
    }

    /**
     * 查询红包类型的商品列表
     *
     * @access  public
     * @param integer $type_id
     * @return  array
     */
    private function get_bonus_goods($type_id)
    {
        $sql = "SELECT goods_id, goods_name FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE bonus_type_id = '$type_id'";
        $row = $GLOBALS['db']->getAll($sql);

        return $row;
    }

    /**
     * 获取用户红包列表
     * @access  public
     * @param   $page_param
     * @return void
     */
    private function get_bonus_list()
    {
        /* 查询条件 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'bonus_type_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['bonus_type'] = empty($_REQUEST['bonus_type']) ? 0 : intval($_REQUEST['bonus_type']);

        $where = empty($filter['bonus_type']) ? '' : " WHERE bonus_type_id='$filter[bonus_type]'";

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_bonus') . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT ub.*, u.user_name, u.email, o.order_sn, bt.type_name " .
            " FROM " . $GLOBALS['ecs']->table('user_bonus') . " AS ub " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('bonus_type') . " AS bt ON bt.type_id=ub.bonus_type_id " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " AS u ON u.user_id=ub.user_id " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " AS o ON o.order_id=ub.order_id $where " .
            " ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'] .
            " LIMIT " . $filter['start'] . ", $filter[page_size]";
        $row = $GLOBALS['db']->getAll($sql);

        foreach ($row as $key => $val) {
            $row[$key]['used_time'] = $val['used_time'] == 0 ?
                $GLOBALS['_LANG']['no_use'] : local_date($GLOBALS['_CFG']['date_format'], $val['used_time']);
            $row[$key]['emailed'] = $GLOBALS['_LANG']['mail_status'][$row[$key]['emailed']];
        }

        $arr = array('item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
    }

    /**
     * 取得红包类型信息
     * @param int $bonus_type_id 红包类型id
     * @return  array
     */
    private function bonus_type_info($bonus_type_id)
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('bonus_type') .
            " WHERE type_id = '$bonus_type_id'";

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 发送红包邮件
     * @param int $bonus_type_id 红包类型id
     * @param array $bonus_id_list 红包id数组
     * @return  int     成功发送数量
     */
    private function send_bonus_mail($bonus_type_id, $bonus_id_list)
    {
        /* 取得红包类型信息 */
        $bonus_type = $this->bonus_type_info($bonus_type_id);
        if ($bonus_type['send_type'] != SEND_BY_USER) {
            return 0;
        }

        /* 取得属于该类型的红包信息 */
        $sql = "SELECT b.bonus_id, u.user_name, u.email " .
            "FROM " . $GLOBALS['ecs']->table('user_bonus') . " AS b, " .
            $GLOBALS['ecs']->table('users') . " AS u " .
            " WHERE b.user_id = u.user_id " .
            " AND b.bonus_id " . db_create_in($bonus_id_list) .
            " AND b.order_id = 0 " .
            " AND u.email <> ''";
        $bonus_list = $GLOBALS['db']->getAll($sql);
        if (empty($bonus_list)) {
            return 0;
        }

        /* 初始化成功发送数量 */
        $send_count = 0;

        /* 发送邮件 */
        $tpl = get_mail_template('send_bonus');
        $today = local_date($GLOBALS['_CFG']['date_format']);
        foreach ($bonus_list as $bonus) {
            $this->assign('user_name', $bonus['user_name']);
            $this->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
            $this->assign('send_date', $today);
            $this->assign('sent_date', $today);
            $this->assign('count', 1);
            $this->assign('money', price_format($bonus_type['type_money']));

            $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
            if ($this->add_to_maillist($bonus['user_name'], $bonus['email'], $tpl['template_subject'], $content, $tpl['is_html'], false)) {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('user_bonus') .
                    " SET emailed = '" . BONUS_MAIL_SUCCEED . "'" .
                    " WHERE bonus_id = '$bonus[bonus_id]'";
                $GLOBALS['db']->query($sql);
                $send_count++;
            } else {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('user_bonus') .
                    " SET emailed = '" . BONUS_MAIL_FAIL . "'" .
                    " WHERE bonus_id = '$bonus[bonus_id]'";
                $GLOBALS['db']->query($sql);
            }
        }

        return $send_count;
    }

    private function add_to_maillist($username, $email, $subject, $content, $is_html)
    {
        $time = time();
        $content = addslashes($content);
        $template_id = $GLOBALS['db']->getOne("SELECT template_id FROM " . $GLOBALS['ecs']->table('mail_templates') . " WHERE template_code = 'send_bonus'");
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('email_sendlist') . " ( email, template_id, email_content, pri, last_send) VALUES ('$email', $template_id, '$content', 1, '$time')";
        $GLOBALS['db']->query($sql);
        return true;
    }
}
