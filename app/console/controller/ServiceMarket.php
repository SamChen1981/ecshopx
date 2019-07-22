<?php

namespace app\console\controller;

/**
 * 程序说明
 */
class ServiceMarket extends Init
{
    public function index()
    {
        $this->assign('ur_here', $GLOBALS['_LANG']['service_market_here']);
        $this->assign('iframe_url', YUNQI_SERVICE_URL . 'cid=38&source=' . iframe_source_encode('ecshop'));
        return $this->display('yq_iframe.view.php');
    }
}
