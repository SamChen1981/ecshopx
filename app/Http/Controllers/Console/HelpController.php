<?php

namespace app\console\controller;

/**
 * 帮助信息接口
 */
class Help extends Init
{
    public function index()
    {
        $get_keyword = trim($_GET['al']); // 获取关键字
        header("location:https://help-ecshop.xyunqi.com/do.php?k=" . $get_keyword . "&v=" . $GLOBALS['_CFG']['ecs_version'] . "&l=" . $GLOBALS['_CFG']['lang'] . "&c=" . EC_CHARSET);
    }
}
