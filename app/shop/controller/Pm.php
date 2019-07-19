<?php

namespace app\shop\controller;

/**
 * 短消息文件
 */
class Pm extends Init
{
    public function index()
    {
        if (empty($_SESSION['user_id']) || $GLOBALS['_CFG']['integrate_code'] == 'ecshop') {
            return $this->redirect('/');
        }

        uc_call("uc_pm_location", array($_SESSION['user_id']));
        //$ucnewpm = uc_pm_checknew($_SESSION['user_id']);
//setcookie('checkpm', '');
    }
}
