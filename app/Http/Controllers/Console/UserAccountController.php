<?php

namespace app\console\controller;

/**
 * 会员帐目管理(包括预付款，余额)
 */
class UserAccount extends Init
{
    public function index()
    {


        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 会员余额记录列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限判断 */
            admin_priv('surplus_manage');

            /* 指定会员的ID为查询条件 */
            $user_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 获得支付方式列表 */
            $payment = array();
            $sql = "SELECT pay_id, pay_name FROM " . $GLOBALS['ecs']->table('payment') .
                " WHERE enabled = 1 AND pay_code != 'cod' ORDER BY pay_id";
            $res = $GLOBALS['db']->query($sql);

            foreach ($res as $row) {
                $payment[$row['pay_name']] = $row['pay_name'];
            }

            /* 模板赋值 */
            if (isset($_REQUEST['process_type'])) {
                $this->assign('process_type_' . intval($_REQUEST['process_type']), 'selected="selected"');
            }
            if (isset($_REQUEST['is_paid'])) {
                $this->assign('is_paid_' . intval($_REQUEST['is_paid']), 'selected="selected"');
            }
            $this->assign('ur_here', $GLOBALS['_LANG']['09_user_account']);
            $this->assign('id', $user_id);
            $this->assign('payment_list', $payment);
            $this->assign('action_link', array('text' => $GLOBALS['_LANG']['surplus_add'], 'href' => 'user_account.php?act=add'));

            $list = $this->account_list();
            $this->assign('list', $list['list']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);
            $this->assign('full_page', 1);

            assign_query_info();
            return $this->fetch('user_account_list');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑会员余额页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('surplus_manage'); //权限判断

            $ur_here = ($_REQUEST['act'] == 'add') ? $GLOBALS['_LANG']['surplus_add'] : $GLOBALS['_LANG']['surplus_edit'];
            $form_act = ($_REQUEST['act'] == 'add') ? 'insert' : 'update';
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            /* 获得支付方式列表, 不包括“货到付款” */
            $user_account = array();
            $payment = array();
            $sql = "SELECT pay_id, pay_name FROM " . $GLOBALS['ecs']->table('payment') .
                " WHERE enabled = 1 AND pay_code != 'cod' ORDER BY pay_id";
            $res = $GLOBALS['db']->query($sql);

            foreach ($res as $row) {
                $payment[$row['pay_name']] = $row['pay_name'];
            }

            if ($_REQUEST['act'] == 'edit') {
                /* 取得余额信息 */
                $user_account = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('user_account') . " WHERE id = '$id'");

                // 如果是负数，去掉前面的符号
                $user_account['amount'] = str_replace('-', '', $user_account['amount']);

                /* 取得会员名称 */
                $sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$user_account[user_id]'";
                $user_name = $GLOBALS['db']->getOne($sql);
            } else {
                $surplus_type = '';
                $user_name = '';
            }

            /* 模板赋值 */
            $this->assign('ur_here', $ur_here);
            $this->assign('form_act', $form_act);
            $this->assign('payment_list', $payment);
            $this->assign('action', $_REQUEST['act']);
            $this->assign('user_surplus', $user_account);
            $this->assign('user_name', $user_name);
            if ($_REQUEST['act'] == 'add') {
                $href = 'user_account.php?act=list';
            } else {
                $href = 'user_account.php?act=list&' . list_link_postfix();
            }
            $this->assign('action_link', array('href' => $href, 'text' => $GLOBALS['_LANG']['09_user_account']));

            assign_query_info();
            return $this->fetch('user_account_info');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑会员余额的处理部分
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('surplus_manage');

            /* 初始化变量 */
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $is_paid = !empty($_POST['is_paid']) ? intval($_POST['is_paid']) : 0;
            $amount = !empty($_POST['amount']) ? floatval($_POST['amount']) : 0;
            $process_type = !empty($_POST['process_type']) ? intval($_POST['process_type']) : 0;
            $user_name = !empty($_POST['user_id']) ? trim($_POST['user_id']) : '';
            $admin_note = !empty($_POST['admin_note']) ? trim($_POST['admin_note']) : '';
            $user_note = !empty($_POST['user_note']) ? trim($_POST['user_note']) : '';
            $payment = !empty($_POST['payment']) ? trim($_POST['payment']) : '';

            $user_id = $GLOBALS['db']->getOne("SELECT user_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_name = '$user_name'");

            /* 此会员是否存在 */
            if ($user_id == 0) {
                $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                return sys_msg($GLOBALS['_LANG']['username_not_exist'], 0, $link);
            }

            /* 退款，检查余额是否足够 */
            if ($process_type == 1) {
                $user_account = $this->get_user_surplus($user_id);

                /* 如果扣除的余额多于此会员拥有的余额，提示 */
                if ($amount > $user_account) {
                    $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                    return sys_msg($GLOBALS['_LANG']['surplus_amount_error'], 0, $link);
                }
            }

            if ($_REQUEST['act'] == 'insert') {
                /* 入库的操作 */
                if ($process_type == 1) {
                    $amount = (-1) * $amount;
                }
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('user_account') .
                    " VALUES ('', '$user_id', '$_SESSION[admin_name]', '$amount', '" . gmtime() . "', '" . gmtime() . "', '$admin_note', '$user_note', '$process_type', '$payment', '$is_paid')";
                $GLOBALS['db']->query($sql);
                $id = $GLOBALS['db']->insert_id();
            } else {
                /* 更新数据表 */
                $sql = "UPDATE " . $GLOBALS['ecs']->table('user_account') . " SET " .
                    "admin_note   = '$admin_note', " .
                    "user_note    = '$user_note', " .
                    "payment      = '$payment' " .
                    "WHERE id      = '$id'";
                $GLOBALS['db']->query($sql);
            }

            // 更新会员余额数量
            if ($is_paid == 1) {
                $change_desc = $amount > 0 ? $GLOBALS['_LANG']['surplus_type_0'] : $GLOBALS['_LANG']['surplus_type_1'];
                $change_type = $amount > 0 ? ACT_SAVING : ACT_DRAWING;
                log_account_change($user_id, $amount, 0, 0, 0, $change_desc, $change_type);
            }

            //如果是预付款并且未确认，向pay_log插入一条记录
            if ($process_type == 0 && $is_paid == 0) {
                load_helper('order');

                /* 取支付方式信息 */
                $payment_info = array();
                $payment_info = $GLOBALS['db']->getRow('SELECT * FROM ' . $GLOBALS['ecs']->table('payment') .
                    " WHERE pay_name = '$payment' AND enabled = '1'");
                //计算支付手续费用
                $pay_fee = pay_fee($payment_info['pay_id'], $amount, 0);
                $total_fee = $pay_fee + $amount;

                /* 插入 pay_log */
                $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('pay_log') . " (order_id, order_amount, order_type, is_paid)" .
                    " VALUES ('$id', '$total_fee', '" . PAY_SURPLUS . "', 0)";
                $GLOBALS['db']->query($sql);
            }

            /* 记录管理员操作 */
            if ($_REQUEST['act'] == 'update') {
                admin_log($user_name, 'edit', 'user_surplus');
            } else {
                admin_log($user_name, 'add', 'user_surplus');
            }

            /* 提示信息 */
            if ($_REQUEST['act'] == 'insert') {
                $href = 'user_account.php?act=list';
            } else {
                $href = 'user_account.php?act=list&' . list_link_postfix();
            }
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = $href;

            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'user_account.php?act=add';

            return sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 审核会员余额页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check') {
            /* 检查权限 */
            admin_priv('surplus_manage');

            /* 初始化 */
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            /* 如果参数不合法，返回 */
            if ($id == 0) {
                return $this->redirect('user_account.php?act=list');

            }

            /* 查询当前的预付款信息 */
            $account = array();
            $account = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('user_account') . " WHERE id = '$id'");
            $account['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $account['add_time']);

            //余额类型:预付款，退款申请，购买商品，取消订单
            if ($account['process_type'] == 0) {
                $process_type = $GLOBALS['_LANG']['surplus_type_0'];
            } elseif ($account['process_type'] == 1) {
                $process_type = $GLOBALS['_LANG']['surplus_type_1'];
            } elseif ($account['process_type'] == 2) {
                $process_type = $GLOBALS['_LANG']['surplus_type_2'];
            } else {
                $process_type = $GLOBALS['_LANG']['surplus_type_3'];
            }

            $sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$account[user_id]'";
            $user_name = $GLOBALS['db']->getOne($sql);

            /* 模板赋值 */
            $this->assign('ur_here', $GLOBALS['_LANG']['check']);
            $account['user_note'] = htmlspecialchars($account['user_note']);
            $this->assign('surplus', $account);
            $this->assign('process_type', $process_type);
            $this->assign('user_name', $user_name);
            $this->assign('id', $id);
            $this->assign('action_link', array('text' => $GLOBALS['_LANG']['09_user_account'],
                'href' => 'user_account.php?act=list&' . list_link_postfix()));

            /* 页面显示 */
            assign_query_info();
            return $this->fetch('user_account_check');
        }

        /*------------------------------------------------------ */
        //-- 更新会员余额的状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'action') {
            /* 检查权限 */
            admin_priv('surplus_manage');

            /* 初始化 */
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $is_paid = isset($_POST['is_paid']) ? intval($_POST['is_paid']) : 0;
            $admin_note = isset($_POST['admin_note']) ? trim($_POST['admin_note']) : '';

            /* 如果参数不合法，返回 */
            if ($id == 0 || empty($admin_note)) {
                return $this->redirect('user_account.php?act=list');

            }

            /* 查询当前的预付款信息 */
            $account = array();
            $account = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('user_account') . " WHERE id = '$id'");
            $amount = $account['amount'];

            //如果状态为未确认
            if ($account['is_paid'] == 0) {
                //如果是退款申请, 并且已完成,更新此条记录,扣除相应的余额
                if ($is_paid == '1' && $account['process_type'] == '1') {
                    $user_account = $this->get_user_surplus($account['user_id']);
                    $fmt_amount = str_replace('-', '', $amount);

                    //如果扣除的余额多于此会员拥有的余额，提示
                    if ($fmt_amount > $user_account) {
                        $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                        return sys_msg($GLOBALS['_LANG']['surplus_amount_error'], 0, $link);
                    }

                    $this->update_user_account($id, $amount, $admin_note, $is_paid);

                    //更新会员余额数量
                    log_account_change($account['user_id'], $amount, 0, 0, 0, $GLOBALS['_LANG']['surplus_type_1'], ACT_DRAWING);
                } elseif ($is_paid == '1' && $account['process_type'] == '0') {
                    //如果是预付款，并且已完成, 更新此条记录，增加相应的余额
                    $this->update_user_account($id, $amount, $admin_note, $is_paid);

                    //更新会员余额数量
                    log_account_change($account['user_id'], $amount, 0, 0, 0, $GLOBALS['_LANG']['surplus_type_0'], ACT_SAVING);
                } elseif ($is_paid == '0') {
                    /* 否则更新信息 */
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('user_account') . " SET " .
                        "admin_user    = '$_SESSION[admin_name]', " .
                        "admin_note    = '$admin_note', " .
                        "is_paid       = 0 WHERE id = '$id'";
                    $GLOBALS['db']->query($sql);
                }

                /* 记录管理员日志 */
                admin_log('(' . addslashes($GLOBALS['_LANG']['check']) . ')' . $admin_note, 'edit', 'user_surplus');

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'user_account.php?act=list&' . list_link_postfix();

                return sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- ajax帐户信息列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->account_list();
            $this->assign('list', $list['list']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($GLOBALS['smarty']->fetch('user_account_list.htm'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
        }
        /*------------------------------------------------------ */
        //-- ajax删除一条信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            check_authz_json('surplus_manage');
            $id = @intval($_REQUEST['id']);
            $sql = "SELECT u.user_name FROM " . $GLOBALS['ecs']->table('users') . " AS u, " .
                $GLOBALS['ecs']->table('user_account') . " AS ua " .
                " WHERE u.user_id = ua.user_id AND ua.id = '$id' ";
            $user_name = $GLOBALS['db']->getOne($sql);
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('user_account') . " WHERE id = '$id'";
            if ($GLOBALS['db']->query($sql, 'SILENT')) {
                admin_log(addslashes($user_name), 'remove', 'user_surplus');
                $url = 'user_account.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
                return $this->redirect($url);

            } else {
                return make_json_error($GLOBALS['db']->error());
            }
        }
    }

    /**
     * 查询会员余额的数量
     * @access  public
     * @param int $user_id 会员ID
     * @return  int
     */
    private function get_user_surplus($user_id)
    {
        $sql = "SELECT SUM(user_money) FROM " . $GLOBALS['ecs']->table('account_log') .
            " WHERE user_id = '$user_id'";

        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * 更新会员账目明细
     *
     * @access  public
     * @param array $id 帐目ID
     * @param array $admin_note 管理员描述
     * @param array $amount 操作的金额
     * @param array $is_paid 是否已完成
     *
     * @return  int
     */
    private function update_user_account($id, $amount, $admin_note, $is_paid)
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('user_account') . " SET " .
            "admin_user  = '$_SESSION[admin_name]', " .
            "amount      = '$amount', " .
            "paid_time   = '" . gmtime() . "', " .
            "admin_note  = '$admin_note', " .
            "is_paid     = '$is_paid' WHERE id = '$id'";
        return $GLOBALS['db']->query($sql);
    }

    /**
     *
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function account_list()
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤列表 */
            $filter['user_id'] = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }

            $filter['process_type'] = isset($_REQUEST['process_type']) ? intval($_REQUEST['process_type']) : -1;
            $filter['payment'] = empty($_REQUEST['payment']) ? '' : trim($_REQUEST['payment']);
            $filter['is_paid'] = isset($_REQUEST['is_paid']) ? intval($_REQUEST['is_paid']) : -1;
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
            $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : local_strtotime($_REQUEST['start_date']);
            $filter['end_date'] = empty($_REQUEST['end_date']) ? '' : (local_strtotime($_REQUEST['end_date']) + 86400);

            $where = " WHERE 1 ";
            if ($filter['user_id'] > 0) {
                $where .= " AND ua.user_id = '$filter[user_id]' ";
            }
            if ($filter['process_type'] != -1) {
                $where .= " AND ua.process_type = '$filter[process_type]' ";
            } else {
                $where .= " AND ua.process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN));
            }
            if ($filter['payment']) {
                $where .= " AND ua.payment = '$filter[payment]' ";
            }
            if ($filter['is_paid'] != -1) {
                $where .= " AND ua.is_paid = '$filter[is_paid]' ";
            }

            if ($filter['keywords']) {
                $where .= " AND u.user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
                $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_account') . " AS ua, " .
                    $GLOBALS['ecs']->table('users') . " AS u " . $where;
            }
            /*　时间过滤　*/
            if (!empty($filter['start_date']) && !empty($filter['end_date'])) {
                $where .= "AND paid_time >= " . $filter['start_date'] . " AND paid_time < '" . $filter['end_date'] . "'";
            }

            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_account') . " AS ua, " .
                $GLOBALS['ecs']->table('users') . " AS u " . $where;
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询数据 */
            $sql = 'SELECT ua.*, u.user_name FROM ' .
                $GLOBALS['ecs']->table('user_account') . ' AS ua LEFT JOIN ' .
                $GLOBALS['ecs']->table('users') . ' AS u ON ua.user_id = u.user_id' .
                $where . "ORDER by " . $filter['sort_by'] . " " . $filter['sort_order'] . " LIMIT " . $filter['start'] . ", " . $filter['page_size'];

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $list = $GLOBALS['db']->getAll($sql);
        foreach ($list as $key => $value) {
            $list[$key]['surplus_amount'] = price_format(abs($value['amount']), false);
            $list[$key]['add_date'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);
            $list[$key]['process_type_name'] = $GLOBALS['_LANG']['surplus_type_' . $value['process_type']];
        }
        $arr = array('list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
    }
}
