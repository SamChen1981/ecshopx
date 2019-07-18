<?php

/**
 * 程序说明
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');



$smarty->assign('ur_here', $_LANG['logistic_tracking_here']);
$smarty->assign('iframe_url', YUNQI_LOGISTIC_URL . '?ctl=exp&act=index&source='.iframe_source_encode('ecshop'));
$smarty->display('yq_iframe.htm');
