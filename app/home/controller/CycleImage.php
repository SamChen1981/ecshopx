<?php

namespace app\home\controller;

/**
 * 轮播图片程序
 */
class CycleImage extends Init
{
    public function index()
    {
        header('Content-Type: application/xml; charset=' . EC_CHARSET);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Thu, 27 Mar 1975 07:38:00 GMT');
        header('Last-Modified: ' . date('r'));
        header('Pragma: no-cache');

        if (file_exists(ROOT_PATH . DATA_DIR . '/cycle_image.xml')) {
            echo file_get_contents(ROOT_PATH . DATA_DIR . '/cycle_image.xml');
        } else {
            echo '<?xml version="1.0" encoding="' . EC_CHARSET . '"?><bcaster><item item_url="images/200609/05.jpg" link="http://www.ecshop.com" /></bcaster>';
        }
    }
}
