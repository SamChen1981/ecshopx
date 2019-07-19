<?php

namespace app\console\controller;

/**
 * 程序说明
 */
class WxaSetting extends Init
{
    public function index()
    {
        $uri = $GLOBALS['ecs']->url();
        $allow_suffix = array('gif', 'jpg', 'png', 'jpeg', 'bmp');

        /*------------------------------------------------------ */
        //-- 移动端应用配置
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('wxa_setting');
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['wxa_setting']);
            $auth_sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('shop_config') . ' WHERE code = "authorize"';
            $auth = $GLOBALS['db']->getRow($auth_sql);
            $params = unserialize($auth['value']);
            if ($params['authorize_code'] != 'NDE') {
                $url = $params['authorize_code'] == 'NCH' ? 'https://account.shopex.cn/order/confirm/goods_2460-946 ' : 'https://account.shopex.cn/order/confirm/goods_2540-1050 ';
                $GLOBALS['smarty']->assign('url', $url);
                $GLOBALS['smarty']->display('accredit.html');

            }
            $cert = new certificate;
            $isOpenWap = $cert->is_open_sn('fy');
            if ($isOpenWap == false && $_SESSION['yunqi_login'] && $_SESSION['TOKEN']) {
                $result = $cert->getsnlistoauth($_SESSION['TOKEN'], array());
                if ($result['status'] == 'success') {
                    $cert->save_snlist($result['data']);
                    $isOpenWap = $cert->is_open_sn('fy');
                }
            }
            $tab = !$isOpenWap ? 'open' : 'enter';
            $charset = EC_CHARSET == 'utf-8' ? "utf8" : 'gbk';
            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('config') . " WHERE 1";
            $group_items = $GLOBALS['db']->getAll($sql);
            $grouplist = $this->get_params();
            foreach ($grouplist as $key => $value) {
                foreach ($value['items'] as $k => $v) {
                    foreach ($group_items as $item) {
                        if ($item['code'] == $v['code']) {
                            $config = json_decode($item['config'], 1);
                            foreach ($v['vars'] as $var_k => $var_v) {
                                $grouplist[$key]['items'][$k]['vars'][$var_k]['value'] = $config[$var_v['code']];
                            }
                        }
                    }
                }
            }

            assign_query_info();

            $GLOBALS['smarty']->assign('group_list', $grouplist);
            $GLOBALS['smarty']->display('wxa_config.html');
        } elseif ($_REQUEST['act'] == 'post') {
            /* 检查权限 */
            admin_priv('mobile_setting');
            $links[] = array('text' => $GLOBALS['_LANG']['wxa_setting'], 'href' => 'wxa_setting.php?act=list');

            foreach ($_POST['value'] as $key => $value) {
                $_POST['value'][$key] = trim($value);
            }
            if (!empty($_FILES['value']['name'])) {
                foreach ($_FILES['value']['name'] as $k => $v) {
                    if ($v) {
                        $cert = $_FILES['value']['tmp_name']['cert'];
                        $PSize = filesize($cert);
                        $cert_steam = (fread(fopen($cert, "r"), $PSize));
                        $cert_steam = addslashes($cert_steam);
                        $_POST['value']['cert'] = $_FILES['value']['name']['cert'];
                    } else {
                        sys_msg('证书不能为空', 1, $links);
                    }
                }
            }
            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('config') . " WHERE `code` = '" . $_POST['code'] . "'";
            $res = $GLOBALS['db']->getRow($sql);
            $items = $this->get_items($_POST['code']);

            $type = $items['type'];
            $name = $items['name'];
            $code = $items['code'];
            $description = $items['description'];
            $config = json_encode($_POST['value']);
            $status = $_POST['value']['status'];
            $time = date('Y-m-d H:i:s', time());

            if ($res) {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('config') . " SET `updated_at` = '$time',`status` = '$status' ,`config` = '$config' WHERE `code` = '$code'";
            } else {
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('config') . " (`name`,`type`,`description`,`code`,`config`,`created_at`,`updated_at`,`status`) VALUES ('$name','$type','$description','$code','$config','$time','$time','$status')";
            }
            $GLOBALS['db']->query($sql);
            if ($type == 'payment') {
                save_payment($code, $name, $description, $config, $status, PAY_TYPE_XCX);
            }
            if ($cert_steam) {
                //处理文件
                $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('config') . " WHERE `code` = '" . $_POST['code'] . "'";
                $setting = $GLOBALS['db']->getRow($sql);
                if ($setting['id']) {
                    $id = $setting['id'];
                    $cert_tmp = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('cert') . " WHERE `config_id` = '$id'");
                    if ($cert_tmp) {
                        $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('cert') . " SET `file` = '$cert_steam' WHERE `config_id` = '$id'");
                    } else {
                        $GLOBALS['db']->query("INSERT INTO " . $GLOBALS['ecs']->table('cert') . " (`config_id`,`file`) VALUES ($id,'$cert_steam')");
                    }
                }
            }
            sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $links);
        }
    }

    private function get_items($code)
    {
        $params = $this->get_params();
        foreach ($params as $value) {
            foreach ($value['items'] as $val) {
                if ($val['code'] == $code) {
                    return $val;
                }
            }
        }
    }

    private function get_params()
    {
        $grouplist = array(
            0 => array(
                'name' => '小程序登陆配置',
                'code' => 'oauthwxa',
                'items' => array(
                    0 => array(
                        'title' => '小程序登陆配置',
                        'submit' => '?act=post',
                        'url' => 'https://pay.weixin.qq.com',
                        'type' => 'oauth',
                        'name' => '小程序登陆配置',
                        'description' => '小程序登陆配置',
                        'code' => 'wechat.wxa',
                        'vars' => array(
                            0 => array(
                                'type' => 'radio',
                                'name' => '是否开启',
                                'code' => 'status',
                                'value' => '',
                            ),
                            1 => array(
                                'type' => 'text',
                                'name' => 'APP_ID',
                                'code' => 'app_id',
                                'value' => '',
                            ),
                            2 => array(
                                'type' => 'text',
                                'name' => 'APP_Secret',
                                'code' => 'app_secret',
                                'value' => '',
                            ),
                            // 3 => array(
                            //     'type' => 'text',
                            //     'name' => 'Cert',
                            //     'code' => 'cert',
                            //     'value' => '',
                            // ),
                        ),
                    ),
                ),
            ),
            1 => array(
                'name' => '小程序支付',
                'code' => 'paymentwxa',
                'items' => array(
                    0 => array(
                        'title' => '小程序支付',
                        'submit' => '?act=post',
                        'url' => 'https://pay.weixin.qq.com',
                        'type' => 'payment',
                        'name' => '小程序支付',
                        'description' => '小程序支付',
                        'code' => 'wxpay.wxa',
                        'vars' => array(
                            0 => array(
                                'type' => 'radio',
                                'name' => '是否开启',
                                'code' => 'status',
                                'value' => '',
                            ),
                            1 => array(
                                'type' => 'text',
                                'name' => 'APP_ID',
                                'code' => 'app_id',
                                'value' => '',
                            ),
                            2 => array(
                                'type' => 'text',
                                'name' => 'APP_Secret',
                                'code' => 'app_secret',
                                'value' => '',
                            ),
                            3 => array(
                                'type' => 'text',
                                'name' => 'MCH_ID',
                                'code' => 'mch_id',
                                'value' => '',
                            ),
                            4 => array(
                                'type' => 'text',
                                'name' => 'MCH_Key',
                                'code' => 'mch_key',
                                'value' => '',
                            ),
                            // 5 => array(
                            //     'type' => 'text',
                            //     'name' => 'Cert',
                            //     'code' => 'cert',
                            //     'value' => '',
                            // ),
                        ),
                    ),
                ),
            ),
        );
        return $grouplist;
    }
}
