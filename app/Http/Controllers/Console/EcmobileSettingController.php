<?php

namespace app\console\controller;

use app\common\libraries\Certificate;

/**
 * 程序说明
 */
class EcmobileSetting extends Init
{
    public function index()
    {


        /*------------------------------------------------------ */
        //-- 移动端应用配置
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('mobile_setting');
            $this->assign('ur_here', $GLOBALS['_LANG']['lead_here']);
            $cert = new Certificate();
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
                                $grouplist[$key]['items'][$k]['vars'][$var_k]['value'] = $var_v['code'] == 'cert' ? urldecode($config[$var_v['code']]) : $config[$var_v['code']];
                            }
                        }
                    }
                }
            }

            $this->assign('ur_here', $GLOBALS['_LANG']['mobile_setting']);
            $this->assign('group_list', $grouplist);
            return $this->fetch('mobile_config.html');
        } elseif ($_REQUEST['act'] == 'post') {
            /* 检查权限 */
            admin_priv('mobile_setting');
            $links[] = array('text' => $GLOBALS['_LANG']['mobile_setting'], 'href' => 'ecmobile_setting.php?act=list');

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
                        $_POST['value']['cert'] = urlencode($_FILES['value']['name']['cert']);
                    } else {
                        return sys_msg('证书不能为空', 1, $links);
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
                $sql = "UPDATE " . $GLOBALS['ecs']->table('config') . " SET `updated_at` = '$time',`status` = '$status' ,`config` = '" . json_encode($_POST['value']) . "' WHERE `code` = '$code'";
            } else {
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('config') . " (`name`,`type`,`description`,`code`,`config`,`created_at`,`updated_at`,`status`) VALUES ('$name','$type','$description','$code','$config','$time','$time','$status')";
            }
            $GLOBALS['db']->query($sql);
            if ($type == 'payment') {
                save_payment($code, $name, $description, $config, $status, PAY_TYPE_APP);
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

            return sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $links);
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
            1 => array(
                'name' => '支付配置',
                'code' => 'payment',
                'items' => array(
                    0 => array(
                        'title' => 'Alipay - 支付宝APP手机支付（无线快捷支付）',
                        'submit' => '?act=post',
                        'type' => 'payment',
                        'name' => '支付宝APP手机支付',
                        'description' => '支付宝手机支付',
                        'url' => 'https://open.alipay.com',
                        'code' => 'alipay.app',
                        'vars' => array(
                            0 => array(
                                'type' => 'radio',
                                'name' => '是否开启',
                                'code' => 'status',
                                'value' => '',
                            ),
                            1 => array(
                                'type' => 'textarea',
                                'name' => '合作者身份ID',
                                'code' => 'partner_id',
                                'value' => '',
                            ),
                            2 => array(
                                'type' => 'text',
                                'name' => 'Seller ID',
                                'code' => 'seller_id',
                                'value' => '',
                            ),
                            3 => array(
                                'type' => 'text',
                                'name' => 'Public Key',
                                'code' => 'public_key',
                                'value' => '',
                            ),
                            4 => array(
                                'type' => 'text',
                                'name' => 'Private Key',
                                'code' => 'private_key',
                                'value' => '',
                            ),
                        )
                    ),
                    1 => array(
                        'title' => 'Wechat - 微信APP支付',
                        'submit' => '?act=post',
                        'url' => 'https://pay.weixin.qq.com',
                        'type' => 'payment',
                        'name' => '微信APP支付',
                        'description' => '微信手机支付',
                        'code' => 'wxpay.app',
                        'vars' => array(
                            0 => array(
                                'type' => 'radio',
                                'name' => '是否开启',
                                'code' => 'status',
                                'value' => '',
                            ),
                            1 => array(
                                'type' => 'text',
                                'name' => 'APP ID',
                                'code' => 'app_id',
                                'value' => '',
                            ),
                            2 => array(
                                'type' => 'text',
                                'name' => 'APP Secret',
                                'code' => 'app_secret',
                                'value' => '',
                            ),
                            3 => array(
                                'type' => 'text',
                                'name' => 'MCH ID',
                                'code' => 'mch_id',
                                'value' => '',
                            ),
                            4 => array(
                                'type' => 'text',
                                'name' => 'MCH Key',
                                'code' => 'mch_key',
                                'value' => '',
                            ),
                        )
                    ),
                    2 => array(
                        'title' => 'Unionpay - 银联手机支付',
                        'submit' => '?act=post',
                        'url' => 'https://open.unionpay.com/ajweb/index',
                        'type' => 'payment',
                        'name' => '银联手机支付',
                        'description' => '银联手机支付',
                        'code' => 'unionpay.app',
                        'vars' => array(
                            0 => array(
                                'type' => 'radio',
                                'name' => '是否开启',
                                'code' => 'status',
                                'value' => '',
                            ),
                            1 => array(
                                'type' => 'text',
                                'name' => 'MER ID',
                                'code' => 'mer_id',
                                'value' => '',
                            ),
                            2 => array(
                                'type' => 'file',
                                'name' => 'Cert',
                                'code' => 'cert',
                                'value' => '',
                            ),
                            3 => array(
                                'type' => 'text',
                                'name' => 'Cert Password',
                                'code' => 'cert_pwd',
                                'value' => '',
                            ),
                        )
                    ),
                ),
            ),
            2 => array(
                'name' => '社交配置',
                'code' => 'sociality',
                'items' => array(
                    0 => array(
                        'title' => 'Wechat - 微信开放平台',
                        'submit' => '?act=post',
                        'url' => 'https://open.weixin.qq.com/',
                        'type' => 'oauth',
                        'name' => '微信登录',
                        'description' => '微信第三方登陆',
                        'code' => 'wechat.app',
                        'vars' => array(
                            0 => array(
                                'type' => 'radio',
                                'name' => '是否开启',
                                'code' => 'status',
                                'value' => '',
                            ),
                            1 => array(
                                'type' => 'text',
                                'name' => 'APP ID',
                                'code' => 'app_id',
                                'value' => '',
                            ),
                            2 => array(
                                'type' => 'text',
                                'name' => 'APP Secret',
                                'code' => 'app_secret',
                                'value' => '',
                            ),
                        )
                    ),
//                1 => array(
//                    'title' => 'Wechat - 微信开放平台Web',
//                    'submit' => '?act=post',
//                    'url' => 'https://open.weixin.qq.com/',
//                    'code' => 'sociality_wechat_web',
//                    'vars' => array(
//                        0 => array(
//                            'type' => 'radio',
//                            'name' => '是否开启',
//                            'code' => 'status',
//                            'value' => '',
//                        ),
//                        1 => array(
//                            'type' => 'text',
//                            'name' => 'APP ID',
//                            'code' => 'APP ID',
//                            'value' => '',
//                        ),
//                        2 => array(
//                            'type' => 'text',
//                            'name' => 'APP Secret',
//                            'code' => 'APP Secret',
//                            'value' => '',
//                        ),
//                    )
//                ),
                    2 => array(
                        'title' => 'Weibo - 微博开放平台',
                        'submit' => '?act=post',
                        'url' => 'http://open.weibo.com/',
                        'type' => 'oauth',
                        'name' => '微博登录',
                        'description' => '微博第三方登陆',
                        'code' => 'weibo.app',
                        'vars' => array(
                            0 => array(
                                'type' => 'radio',
                                'name' => '是否开启',
                                'code' => 'status',
                                'value' => '',
                            ),
                            1 => array(
                                'type' => 'text',
                                'name' => 'APP ID',
                                'code' => 'app_id',
                                'value' => '',
                            ),
                            2 => array(
                                'type' => 'text',
                                'name' => 'APP Secret',
                                'code' => 'app_secret',
                                'value' => '',
                            ),
                        )
                    ),
                    3 => array(
                        'title' => 'QQ - 腾讯开放平台',
                        'submit' => '?act=post',
                        'url' => 'https://open.qq.com/',
                        'type' => 'oauth',
                        'name' => 'QQ登录',
                        'description' => 'QQ第三方登陆',
                        'code' => 'qq.app',
                        'vars' => array(
                            0 => array(
                                'type' => 'radio',
                                'name' => '是否开启',
                                'code' => 'status',
                                'value' => '',
                            ),
                            1 => array(
                                'type' => 'text',
                                'name' => 'APP ID',
                                'code' => 'app_id',
                                'value' => '',
                            ),
                            2 => array(
                                'type' => 'text',
                                'name' => 'APP Secret',
                                'code' => 'app_secret',
                                'value' => '',
                            ),
                        )
                    ),
                ),
            ),
            3 => array(
                'name' => '云推送',
                'code' => 'leancloud',
                'items' => array(
                    0 => array(
                        'title' => 'LeanCloud - 推送服务',
                        'submit' => '?act=post',
                        'url' => 'https://leancloud.cn',
                        'type' => 'cloud',
                        'name' => '云推送',
                        'description' => '云推送',
                        'code' => 'leancloud',
                        'vars' => array(
                            0 => array(
                                'type' => 'radio',
                                'name' => '是否开启',
                                'code' => 'status',
                                'value' => '',
                            ),
                            1 => array(
                                'type' => 'textarea',
                                'name' => 'APP ID',
                                'code' => 'app_id',
                                'value' => '',
                            ),
                            2 => array(
                                'type' => 'textarea',
                                'name' => 'APP Key',
                                'code' => 'app_key',
                                'value' => '',
                            ),
                            3 => array(
                                'type' => 'text',
                                'name' => 'Master Key',
                                'code' => 'master_key',
                                'value' => '',
                            ),
                            4 => array(
                                'type' => 'text',
                                'name' => '安卓包名称',
                                'code' => 'package_name',
                                'value' => '',
                            ),
                        )
                    ),
                ),
            ),
        );
        return $grouplist;
    }
}
