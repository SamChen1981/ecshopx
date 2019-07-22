<?php

namespace app\console\controller;

/**
 * 程序说明
 */
class LogisticTracking extends Init
{
    public function index()
    {
        $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['logistic_tracking_here']);
        $GLOBALS['smarty']->assign('iframe_url', YUNQI_LOGISTIC_URL . '?ctl=exp&act=index&source=' . iframe_source_encode('ecshop'));
        return $GLOBALS['smarty']->display('yq_iframe.view.php');
    }
}
