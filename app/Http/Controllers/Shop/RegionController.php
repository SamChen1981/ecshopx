<?php

namespace app\shop\controller;

/**
 * 地区切换程序
 */
class Region extends Init
{
    public function index()
    {
        header('Content-type: text/html; charset=' . EC_CHARSET);

        $type = !empty($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
        $parent = !empty($_REQUEST['parent']) ? intval($_REQUEST['parent']) : 0;

        $arr['regions'] = get_regions($type, $parent);
        $arr['type'] = $type;
        $arr['target'] = !empty($_REQUEST['target']) ? stripslashes(trim($_REQUEST['target'])) : '';
        $arr['target'] = htmlspecialchars($arr['target']);

        echo json_encode($arr);
    }
}
