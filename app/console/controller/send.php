<?php
/**
 * 快钱联合注册接口
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
$backUrl=$ecs->url() . ADMIN_PATH . '/receive.php';
header("location:https://cloud-ecshop.xyunqi.com/payment_apply.php?mod=kuaiqian&par=$backUrl");
exit;
?>
