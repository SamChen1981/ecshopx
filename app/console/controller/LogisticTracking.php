<?php

namespace app\console\controller;

/**
 * 程序说明
 */





$smarty->assign('ur_here', $_LANG['logistic_tracking_here']);
$smarty->assign('iframe_url', YUNQI_LOGISTIC_URL . '?ctl=exp&act=index&source=' . iframe_source_encode('ecshop'));
$smarty->display('yq_iframe.htm');
