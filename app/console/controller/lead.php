<?php

/**
 * 程序说明
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 移动端全民分销开通引导页
/*------------------------------------------------------ */
if ($_REQUEST['act']== 'list') {
    /* 检查权限 */
    admin_priv('lead');
    $url_cur = $_SERVER['HTTP_REFERER'];
    $url_arr = explode('/admin', $url_cur);
    $smarty->assign('ur_here', $_LANG['lead_here']);
    $smarty->assign('url', $url_arr[0]);
    $smarty->display('lead.htm');
}
