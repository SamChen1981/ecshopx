<?php

/**
 * 订单自动确认收货
 */

$cron_lang = ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/cron/auto_confirm.php';
if (file_exists($cron_lang)) {
    include_once($cron_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code'] = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc'] = 'auto_confirm_desc';

    /* 作者 */
    $modules[$i]['author'] = 'ECSHOP TEAM';

    /* 网址 */
    $modules[$i]['website'] = 'http://www.ecshop.com';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config'] = array(
        array('name' => 'auto_confirm_day', 'type' => 'select', 'value' => '7'),
    );

    return;
}
confirm_log("begin");
empty($cron['auto_confirm_day']) && $cron['auto_confirm_day'] = 7;
load_helper('transaction');
$confirmtime = gmtime() - $cron['auto_confirm_day'] * 3600 * 24;
$order_status = [OS_SPLITED];
$pay_status = [PS_UNPAYED, PS_PAYED];
$shipping_status = [SS_SHIPPED];
$where = "order_status in (" . implode(',', $order_status) . ") AND pay_status in (" . implode(',', $pay_status) . ") AND shipping_status in (" . implode(',', $shipping_status) . ") AND shipping_time < " . $confirmtime;
$sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE " . $where;
$count = $GLOBALS['db']->GetOne($sql);
confirm_log("select_count:" . $count);
$page_size = 1;
$total = ceil($count / $page_size);
$i = 1;
while ($i <= $total) {
    $sql = "SELECT order_id FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE " . $where . " limit 0," . $page_size;
    confirm_log("select_sql:" . $sql);
    $rows = $GLOBALS['db']->getAll($sql);
    confirm_log("select_rows:", $rows);
    foreach ($rows as $key => $order) {
        $order_id = isset($order['order_id']) ? intval($order['order_id']) : 0;
        affirm_received_auto($order_id, $msg);
        confirm_log("finish_order_id:" . $order_id . "|msg:" . $msg);
    }
    $i++;
}

function confirm_log($msg, $data = null)
{
    error_log(date("c") . "\t" . $msg . "\t" . stripslashes(json_encode($data)) . "\t\n", 3, LOG_DIR . "/auto_confirm_" . date("Y-m-d") . ".log");
}
