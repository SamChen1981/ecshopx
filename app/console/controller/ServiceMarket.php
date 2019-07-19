<?php

namespace app\console\controller;

/**
 * 程序说明
 */
class ServiceMarket extends Init
{
    public function index()
    {
        $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['service_market_here']);
        $GLOBALS['smarty']->assign('iframe_url', YUNQI_SERVICE_URL . 'cid=38&source=' . iframe_source_encode('ecshop'));
        $GLOBALS['smarty']->display('yq_iframe.htm');
    }
}
