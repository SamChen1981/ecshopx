<?php

/**
 * 订单自动取消
 */

load_lang('cron/auto_cancel');

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code'] = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc'] = 'auto_cancel_desc';

    /* 作者 */
    $modules[$i]['author'] = 'ECSHOP TEAM';

    /* 网址 */
    $modules[$i]['website'] = 'http://www.ecshop.com';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config'] = array(
        array('name' => 'auto_cancel_day', 'type' => 'select', 'value' => '12'),
    );

    return;
}
cancel_log("begin");
empty($cron['auto_cancel_day']) && $cron['auto_cancel_day'] = 12;
$cancel_time = gmtime() - $cron['auto_cancel_day'] * 3600;
load_helper('order');
$sql = "SELECT pay_id FROM " . $GLOBALS['ecs']->table('payment') . " WHERE is_cod = 1";
$pay_id_list = $GLOBALS['db']->getCol($sql);
$order_status = [OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART];
$pay_status = [PS_UNPAYED];
$shipping_status = [SS_UNSHIPPED];
$where = "order_status in (" . implode(',', $order_status) . ") AND pay_status in (" . implode(',', $pay_status) . ") AND shipping_status in (" . implode(',', $shipping_status) . ") AND pay_id not in (" . implode(',', $pay_id_list) . ") AND add_time < " . $cancel_time;
$sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE " . $where;
$count = $GLOBALS['db']->GetOne($sql);
cancel_log("select_count:" . $count);
$page_size = 100;
$total = ceil($count / $page_size);
$i = 1;
while ($i <= $total) {
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE " . $where . " limit 0," . $page_size;
    $rows = $GLOBALS['db']->getAll($sql);
    cancel_log("select_sql:" . $sql);
    cancel_log("select_rows:", $rows);
    foreach ($rows as $key => $order) {
        $order_id = $order['order_id'];
        /* 标记订单为“取消”，记录取消原因 */
        $cancel_note = $cron['auto_cancel_reason'];
        update_order($order_id, array('order_status' => OS_CANCELED, 'to_buyer' => $cancel_note));

        /* 记录log */
        order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, 'system', 'system');

        /* 如果使用库存，且下订单时减库存，则增加库存 */
        if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
            change_order_goods_storage($order_id, false, SDT_PLACE);
        }

        /* 发送邮件 */
        if ($GLOBALS['_CFG']['send_cancel_email'] == '1') {
            $tpl = get_mail_template('order_cancel');
            $GLOBALS['smarty']->assign('order', $order);
            $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
            $GLOBALS['smarty']->assign('send_date', local_date($GLOBALS['_CFG']['date_format']));
            $GLOBALS['smarty']->assign('sent_date', local_date($GLOBALS['_CFG']['date_format']));
            $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
            send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
        }

        /* 退还用户余额、积分、红包 */
        return_user_surplus_integral_bonus($order);

        $sn_list[] = $order['order_sn'];

        // 通知erp取消订单
        /*
         * TODO BY LANCE
         * $matrix = new matrix();
        $bind_info = $matrix->get_bind_info(array('ecos.ome'));
        if ($bind_info) {
            $matrix->set_dead_order($order_id);
        }*/
        cancel_log("cancel_order_id:" . $order_id);
        $i++;
    }
}

function cancel_log($msg, $data = null)
{
    error_log(date("c") . "\t" . $msg . "\t" . stripslashes(json_encode($data)) . "\t\n", 3, LOG_DIR . "/auto_cancel_" . date("Y-m-d") . ".log");
}
