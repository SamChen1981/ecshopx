<?php

namespace app\console\controller;

/**
 * 程序说明
 */
class Affiliate extends Init
{
    public function index()
    {
        admin_priv('affiliate');
        $config = $this->get_affiliate();

        /*------------------------------------------------------ */
        //-- 分成管理页
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            assign_query_info();
            if (empty($_REQUEST['is_ajax'])) {
                $this->assign('full_page', 1);
            }

            $this->assign('ur_here', $GLOBALS['_LANG']['affiliate']);
            $this->assign('config', $config);
            return $this->fetch('affiliate');
        } elseif ($_REQUEST['act'] == 'query') {
            $this->assign('ur_here', $GLOBALS['_LANG']['affiliate']);
            $this->assign('config', $config);
            return make_json_result($GLOBALS['smarty']->fetch('affiliate.htm'), '', null);
        }
        /*------------------------------------------------------ */
        //-- 增加下线分配方案
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            if (count($config['item']) < 5) {
                //下线不能超过5层
                $_POST['level_point'] = (float)$_POST['level_point'];
                $_POST['level_money'] = (float)$_POST['level_money'];
                $maxpoint = $maxmoney = 100;
                foreach ($config['item'] as $key => $val) {
                    $maxpoint -= $val['level_point'];
                    $maxmoney -= $val['level_money'];
                }
                $_POST['level_point'] > $maxpoint && $_POST['level_point'] = $maxpoint;
                $_POST['level_money'] > $maxmoney && $_POST['level_money'] = $maxmoney;
                if (!empty($_POST['level_point']) && strpos($_POST['level_point'], '%') === false) {
                    $_POST['level_point'] .= '%';
                }
                if (!empty($_POST['level_money']) && strpos($_POST['level_money'], '%') === false) {
                    $_POST['level_money'] .= '%';
                }
                $items = array('level_point' => $_POST['level_point'], 'level_money' => $_POST['level_money']);
                $links[] = array('text' => $GLOBALS['_LANG']['affiliate'], 'href' => 'affiliate.php?act=list');
                $config['item'][] = $items;
                $config['on'] = 1;
                $config['config']['separate_by'] = 0;

                $this->put_affiliate($config);
            } else {
                return make_json_error($GLOBALS['_LANG']['level_error']);
            }

            return $this->redirect('affiliate.php?act=query');

        }
        /*------------------------------------------------------ */
        //-- 修改配置
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'updata') {
            $separate_by = (intval($_POST['separate_by']) == 1) ? 1 : 0;

            $_POST['expire'] = (float)$_POST['expire'];
            $_POST['level_point_all'] = (float)$_POST['level_point_all'];
            $_POST['level_money_all'] = (float)$_POST['level_money_all'];
            $_POST['level_money_all'] > 100 && $_POST['level_money_all'] = 100;
            $_POST['level_point_all'] > 100 && $_POST['level_point_all'] = 100;

            if (!empty($_POST['level_point_all']) && strpos($_POST['level_point_all'], '%') === false) {
                $_POST['level_point_all'] .= '%';
            }
            if (!empty($_POST['level_money_all']) && strpos($_POST['level_money_all'], '%') === false) {
                $_POST['level_money_all'] .= '%';
            }
            $_POST['level_register_all'] = intval($_POST['level_register_all']);
            $_POST['level_register_up'] = intval($_POST['level_register_up']);
            $temp = array();
            $temp['config'] = array('expire' => $_POST['expire'],        //COOKIE过期数字
                'expire_unit' => $_POST['expire_unit'],   //单位：小时、天、周
                'separate_by' => $separate_by,            //分成模式：0、注册 1、订单
                'level_point_all' => $_POST['level_point_all'],    //积分分成比
                'level_money_all' => $_POST['level_money_all'],    //金钱分成比
                'level_register_all' => $_POST['level_register_all'], //推荐注册奖励积分
                'level_register_up' => $_POST['level_register_up']   //推荐注册奖励积分上限
            );
            $temp['item'] = $config['item'];
            $temp['on'] = 1;
            $this->put_affiliate($temp);
            $links[] = array('text' => $GLOBALS['_LANG']['affiliate'], 'href' => 'affiliate.php?act=list');
            return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
        }
        /*------------------------------------------------------ */
        //-- 推荐开关
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'on') {
            $on = (intval($_POST['on']) == 1) ? 1 : 0;

            $config['on'] = $on;
            $this->put_affiliate($config);
            $links[] = array('text' => $GLOBALS['_LANG']['affiliate'], 'href' => 'affiliate.php?act=list');
            return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
        }
        /*------------------------------------------------------ */
        //-- Ajax修改设置
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_point') {

            /* 取得参数 */
            $key = trim($_POST['id']) - 1;
            $val = (float)trim($_POST['val']);
            $maxpoint = 100;
            foreach ($config['item'] as $k => $v) {
                if ($k != $key) {
                    $maxpoint -= $v['level_point'];
                }
            }
            $val > $maxpoint && $val = $maxpoint;
            if (!empty($val) && strpos($val, '%') === false) {
                $val .= '%';
            }
            $config['item'][$key]['level_point'] = $val;
            $config['on'] = 1;
            $this->put_affiliate($config);
            return make_json_result(stripcslashes($val));
        }
        /*------------------------------------------------------ */
        //-- Ajax修改设置
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_money') {
            $key = trim($_POST['id']) - 1;
            $val = (float)trim($_POST['val']);
            $maxmoney = 100;
            foreach ($config['item'] as $k => $v) {
                if ($k != $key) {
                    $maxmoney -= $v['level_money'];
                }
            }
            $val > $maxmoney && $val = $maxmoney;
            if (!empty($val) && strpos($val, '%') === false) {
                $val .= '%';
            }
            $config['item'][$key]['level_money'] = $val;
            $config['on'] = 1;
            $this->put_affiliate($config);
            return make_json_result(stripcslashes($val));
        }
        /*------------------------------------------------------ */
        //-- 删除下线分成
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del') {
            $key = trim($_GET['id']) - 1;
            unset($config['item'][$key]);
            $temp = array();
            foreach ($config['item'] as $key => $val) {
                $temp[] = $val;
            }
            $config['item'] = $temp;
            $config['on'] = 1;
            $config['config']['separate_by'] = 0;
            $this->put_affiliate($config);
            return $this->redirect('affiliate.php?act=list');

        }
    }

    private function get_affiliate()
    {
        $config = unserialize($GLOBALS['_CFG']['affiliate']);
        empty($config) && $config = array();

        return $config;
    }

    private function put_affiliate($config)
    {
        $temp = serialize($config);
        $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_config') .
            "SET  value = '$temp'" .
            "WHERE code = 'affiliate'";
        $GLOBALS['db']->query($sql);
        clear_all_files();
    }
}
