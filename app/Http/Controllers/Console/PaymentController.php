<?php

namespace app\console\controller;

/**
 * 支付方式管理程序
 */
class Payment extends Init
{
    public function index()
    {
        $exc = new Exchange($GLOBALS['ecs']->table('payment'), $GLOBALS['db'], 'pay_code', 'pay_name');

        /*------------------------------------------------------ */
        //-- 支付方式列表 ?act=list
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 查询数据库中启用的支付方式 */
            $pay_list = array();
            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('payment') . " WHERE enabled = '1' ORDER BY pay_order";
            $res = $GLOBALS['db']->query($sql);
            foreach ($res as $row) {
                $pay_list[$row['pay_code']] = $row;
            }

            /* 取得插件文件中的支付方式 */
            $modules = read_modules('../includes/modules/payment');
            $yunqi_payment = array();
            $modules_count = count($modules);
            for ($i = 0; $i < $modules_count; $i++) {
                $code = $modules[$i]['code'];
                $modules[$i]['pay_code'] = $modules[$i]['code'];
                /* 如果数据库中有，取数据库中的名称和描述 */
                if (isset($pay_list[$code])) {
                    $modules[$i]['name'] = $pay_list[$code]['pay_name'];
                    $modules[$i]['pay_fee'] = $pay_list[$code]['pay_fee'];
                    $modules[$i]['is_cod'] = $pay_list[$code]['is_cod'];
                    $modules[$i]['desc'] = $pay_list[$code]['pay_desc'];
                    $modules[$i]['pay_order'] = $pay_list[$code]['pay_order'];
                    $modules[$i]['install'] = '1';
                } else {
                    $modules[$i]['name'] = $GLOBALS['_LANG'][$modules[$i]['code']];
                    if (!isset($modules[$i]['pay_fee'])) {
                        $modules[$i]['pay_fee'] = 0;
                    }
                    $modules[$i]['desc'] = $GLOBALS['_LANG'][$modules[$i]['desc']];
                    $modules[$i]['install'] = '0';
                }
                if ($modules[$i]['pay_code'] == 'tenpayc2c') {
                    $tenpayc2c = $modules[$i];
                }
                if ($modules[$i]['pay_code'] == 'yunqi') {
                    $yunqi_payment = $modules[$i];
                    unset($modules[$i]);
                }
            }


            load_helper('compositor');
            $yunqi_payment and array_unshift($modules, $yunqi_payment);

            assign_query_info();
            $this->assign('certi', $certificate);
            $this->assign('ur_here', $GLOBALS['_LANG']['02_payment_list']);
            $this->assign('modules', $modules);
            $this->assign('tenpayc2c', $tenpayc2c);
            $this->assign('account_url', TEEGON_PASSPORT_URL);
            return $this->fetch('payment_list');
        }

        /*------------------------------------------------------ */
        //-- 获取云起收银账号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_yunqi') {
            //获取云起收银账号
            $cert = new certificate();
            $yunqi_account = $cert->get_yunqi_account();
            if (!$yunqi_account || !$yunqi_account['status']) {
                $yqaccount_result = $cert->yqaccount_appget();
                if ($yqaccount_result['status'] == 'success') {
                    $cert->set_yunqi_account(array('appkey' => $yqaccount_result['data']['appkey'], 'appsecret' => $yqaccount_result['data']['appsecret'], 'status' => true));
                    echo json_encode(array('status' => true));

                } else {
                    echo json_encode(array('status' => false));

                }
            } else {
                echo json_encode(array('status' => true));

            }
            //获取云起收银账号end
        }
        /*------------------------------------------------------ */
        //-- 安装支付方式 ?act=install&code=".$code."
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'install') {
            admin_priv('payment');

            /* 取相应插件信息 */
            $set_modules = true;
            include_once(ROOT_PATH . 'includes/modules/payment/' . $_REQUEST['code'] . '.php');

            $data = $modules[0];
            /* 对支付费用判断。如果data['pay_fee']为false无支付费用，为空则说明以配送有关，其它可以修改 */
            if (isset($data['pay_fee'])) {
                $data['pay_fee'] = trim($data['pay_fee']);
            } else {
                $data['pay_fee'] = 0;
            }

            $pay['pay_code'] = $data['code'];
            $pay['pay_name'] = $GLOBALS['_LANG'][$data['code']];
            $pay['pay_desc'] = $GLOBALS['_LANG'][$data['desc']];
            $pay['is_cod'] = $data['is_cod'];
            $pay['pay_fee'] = $data['pay_fee'];
            $pay['is_online'] = $data['is_online'];
            $pay['pay_config'] = array();

            foreach ($data['config'] as $key => $value) {
                $config_desc = (isset($GLOBALS['_LANG'][$value['name'] . '_desc'])) ? $GLOBALS['_LANG'][$value['name'] . '_desc'] : '';
                $pay['pay_config'][$key] = $value +
                    array('label' => $GLOBALS['_LANG'][$value['name']], 'value' => $value['value'], 'desc' => $config_desc);

                if ($pay['pay_config'][$key]['type'] == 'select' ||
                    $pay['pay_config'][$key]['type'] == 'radiobox') {
                    $pay['pay_config'][$key]['range'] = $GLOBALS['_LANG'][$pay['pay_config'][$key]['name'] . '_range'];
                }
            }

            assign_query_info();

            $this->assign('action_link', array('text' => $GLOBALS['_LANG']['02_payment_list'], 'href' => 'payment.php?act=list'));
            $this->assign('pay', $pay);
            return $this->fetch('payment_edit');
        } elseif ($_REQUEST['act'] == 'get_config') {
            check_authz_json('payment');

            $code = $_REQUEST['code'];

            /* 取相应插件信息 */
            $set_modules = true;
            include_once(ROOT_PATH . 'includes/modules/payment/' . $code . '.php');
            $data = $modules[0]['config'];
            $config = '<table>';
            $range = '';
            foreach ($data as $key => $value) {
                $config .= "<tr><td width=80><span class='label'>";
                $config .= $GLOBALS['_LANG'][$data[$key]['name']];
                $config .= "</span></td>";
                if ($data[$key]['type'] == 'text') {
                    if ($data[$key]['name'] == 'alipay_account') {
                        $config .= "<td><input name='cfg_value[]' type='text' value='" . $data[$key]['value'] . "' /><a href=\"https://www.alipay.com/himalayas/practicality.htm\" target=\"_blank\">" . $GLOBALS['_LANG']['alipay_look'] . "</a></td>";
                    } elseif ($data[$key]['name'] == 'tenpay_account') {
                        $config .= "<td><input name='cfg_value[]' type='text' value='" . $data[$key]['value'] . "' />" . $GLOBALS['_LANG']['penpay_register'] . "</td>";
                    } else {
                        $config .= "<td><input name='cfg_value[]' type='text' value='" . $data[$key]['value'] . "' /></td>";
                    }
                } elseif ($data[$key]['type'] == 'select') {
                    $range = $GLOBALS['_LANG'][$data[$key]['name'] . '_range'];
                    $config .= "<td><select name='cfg_value[]'>";
                    foreach ($range as $index => $val) {
                        $config .= "<option value='$index'>" . $range[$index] . "</option>";
                    }
                    $config .= "</select></td>";
                }
                $config .= "</tr>";
                //$config .= '<br />';
                $config .= "<input name='cfg_name[]' type='hidden' value='" . $data[$key]['name'] . "' />";
                $config .= "<input name='cfg_type[]' type='hidden' value='" . $data[$key]['type'] . "' />";
                $config .= "<input name='cfg_lang[]' type='hidden' value='" . $data[$key]['lang'] . "' />";
            }
            $config .= '</table>';

            return make_json_result($config);
        }

        /*------------------------------------------------------ */
        //-- 编辑支付方式 ?act=edit&code={$code}
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            admin_priv('payment');

            /* 查询该支付方式内容 */
            if (isset($_REQUEST['code'])) {
                $_REQUEST['code'] = trim($_REQUEST['code']);
            } else {
                die('invalid parameter');
            }

            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('payment') . " WHERE pay_code = '$_REQUEST[code]' AND enabled = '1'";
            $pay = $GLOBALS['db']->getRow($sql);
            if (empty($pay)) {
                $links[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'payment.php?act=list');
                return sys_msg($GLOBALS['_LANG']['payment_not_available'], 0, $links);
            }

            /* 取相应插件信息 */
            $set_modules = true;
            include_once(ROOT_PATH . 'includes/modules/payment/' . $_REQUEST['code'] . '.php');
            $data = $modules[0];

            /* 取得配置信息 */
            if (is_string($pay['pay_config'])) {
                $store = unserialize($pay['pay_config']);
                /* 取出已经设置属性的code */
                $code_list = array();
                if ($store) {
                    foreach ($store as $key => $value) {
                        $code_list[$value['name']] = $value['value'];
                    }
                }

                $pay['pay_config'] = array();

                /* 循环插件中所有属性 */
                foreach ($data['config'] as $key => $value) {
                    $pay['pay_config'][$key]['desc'] = (isset($GLOBALS['_LANG'][$value['name'] . '_desc'])) ? $GLOBALS['_LANG'][$value['name'] . '_desc'] : '';
                    $pay['pay_config'][$key]['label'] = $GLOBALS['_LANG'][$value['name']];
                    $pay['pay_config'][$key]['name'] = $value['name'];
                    $pay['pay_config'][$key]['type'] = $value['type'];

                    if (isset($code_list[$value['name']])) {
                        $pay['pay_config'][$key]['value'] = $code_list[$value['name']];
                    } else {
                        $pay['pay_config'][$key]['value'] = $value['value'];
                    }

                    if ($pay['pay_config'][$key]['type'] == 'select' ||
                        $pay['pay_config'][$key]['type'] == 'radiobox') {
                        $pay['pay_config'][$key]['range'] = $GLOBALS['_LANG'][$pay['pay_config'][$key]['name'] . '_range'];
                    }
                }
            }

            /* 如果以前没设置支付费用，编辑时补上 */
            if (!isset($pay['pay_fee'])) {
                if (isset($data['pay_fee'])) {
                    $pay['pay_fee'] = $data['pay_fee'];
                } else {
                    $pay['pay_fee'] = 0;
                }
            }

            assign_query_info();

            $this->assign('action_link', array('text' => $GLOBALS['_LANG']['02_payment_list'], 'href' => 'payment.php?act=list'));
            $this->assign('ur_here', $GLOBALS['_LANG']['edit'] . $GLOBALS['_LANG']['payment']);
            $this->assign('pay', $pay);
            return $this->fetch('payment_edit');
        }

        /*------------------------------------------------------ */
        //-- 提交支付方式 post
        /*------------------------------------------------------ */
        elseif (isset($_POST['Submit'])) {
            admin_priv('payment');
            /* 检查输入 */
            if (empty($_POST['pay_name'])) {
                return sys_msg($GLOBALS['_LANG']['payment_name'] . $GLOBALS['_LANG']['empty']);
            }

            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('payment') .
                " WHERE pay_name = '$_POST[pay_name]' AND pay_code <> '$_POST[pay_code]'";
            if ($GLOBALS['db']->GetOne($sql) > 0) {
                return sys_msg($GLOBALS['_LANG']['payment_name'] . $GLOBALS['_LANG']['repeat'], 1);
            }

            /* 取得配置信息 */
            $pay_config = array();
            if (isset($_POST['cfg_value']) && is_array($_POST['cfg_value'])) {
                for ($i = 0; $i < count($_POST['cfg_value']); $i++) {
                    $pay_config[] = array('name' => trim($_POST['cfg_name'][$i]),
                        'type' => trim($_POST['cfg_type'][$i]),
                        'value' => trim($_POST['cfg_value'][$i])
                    );
                }
            }
            // 如果是银联且签名方式选择的是证书方式 证书文件处理
            if ($_POST['pay_code'] == 'upop' && $pay_config[0]['value'] == '0') {
                $upop_cert_path = '';
                if ($_FILES['upop_cert']['size'] > 0) {
                    $pathinfo = pathinfo($_FILES['upop_cert']['name']);
                    if ($pathinfo['extension'] != 'pfx') {
                        return sys_msg($GLOBALS['_LANG']['cert_invalid_file'], 1);
                    }
                    $destination = 'cert/' . $_FILES['upop_cert']['name'];
                    if (move_upload_file($_FILES['upop_cert']['tmp_name'], ROOT_PATH . $destination)) {
                        $upop_cert_path = $destination;
                    } else {
                        return sys_msg($GLOBALS['_LANG']['fail_upload'], 1);
                    }
                }
                foreach ($pay_config as $key => &$value) {
                    if ($value['name'] == 'upop_cert') {
                        if ($upop_cert_path) {
                            $value['value'] = $upop_cert_path;
                        } else {
                            if (empty($value['value'])) {
                                return sys_msg($GLOBALS['_LANG']['lack_cert_file'], 1);
                            }
                        }
                    }
                }
            }
            // 如果是银联在线 私钥公钥文件处理
            if ($_POST['pay_code'] == 'chinapay') {
                $pfx_path = $cer_path = '';

                if ($_FILES['chinapay_pfx']['size'] > 0) {
                    $pathinfo = pathinfo($_FILES['chinapay_pfx']['name']);
                    if ($pathinfo['extension'] != 'pfx') {
                        return sys_msg($GLOBALS['_LANG']['cert_invalid_file'], 1);
                    }
                    $destination = 'cert/' . $_FILES['chinapay_pfx']['name'];
                    if (move_upload_file($_FILES['chinapay_pfx']['tmp_name'], ROOT_PATH . $destination)) {
                        $pfx_path = $destination;
                    } else {
                        return sys_msg($GLOBALS['_LANG']['fail_upload'], 1);
                    }
                }
                if ($_FILES['chinapay_cer']['size'] > 0) {
                    $pathinfo = pathinfo($_FILES['chinapay_cer']['name']);
                    if ($pathinfo['extension'] != 'cer') {
                        return sys_msg($GLOBALS['_LANG']['cert_invalid_file'], 1);
                    }
                    $destination = 'cert/' . $_FILES['chinapay_cer']['name'];
                    if (move_upload_file($_FILES['chinapay_cer']['tmp_name'], ROOT_PATH . $destination)) {
                        $cer_path = $destination;
                    } else {
                        return sys_msg($GLOBALS['_LANG']['fail_upload'], 1);
                    }
                }
                foreach ($pay_config as $key => $value) {
                    if ($value['name'] == 'chinapay_pfx') {
                        if ($pfx_path) {
                            $pay_config[$key]['value'] = $pfx_path;
                        } else {
                            if (empty($value['value'])) {
                                return sys_msg($GLOBALS['_LANG']['lack_cert_file'], 1);
                            }
                        }
                    } elseif ($value['name'] == 'chinapay_cer') {
                        if ($cer_path) {
                            $pay_config[$key]['value'] = $cer_path;
                        } else {
                            if (empty($value['value'])) {
                                return sys_msg($GLOBALS['_LANG']['lack_cert_file'], 1);
                            }
                        }
                    } elseif ($value['name'] = 'chinapay_pfx_pwd') {
                        if ($pay_config[$key]['value']) {
                            $pfx_pwd = $value['value'];
                        }
                    }
                }
                if (!$pfx_pwd) {
                    return sys_msg($GLOBALS['_LANG']['pfx_pwd_null'], 1);
                }

                // 重新编写 security.properties 配置文件
                $security = array(
                    'sign.file=' . ROOT_PATH . $pfx_path . PHP_EOL,
                    'sign.file.password=' . $pfx_pwd . PHP_EOL,
                    'sign.cert.type=PKCS12' . PHP_EOL,
                    'sign.invalid.fields=Signature,CertId' . PHP_EOL,
                    'verify.file=' . ROOT_PATH . $cer_path . PHP_EOL,
                    'signature.field=Signature' . PHP_EOL,
                    'log4j.name=cpLog',
                );
                $filename = ROOT_PATH . 'cert/security.properties';
                if (file_exists($filename)) {
                    unlink($filename);
                }
                $fh = fopen($filename, "a");
                foreach ($security as $infos) {
                    fwrite($fh, $infos);
                }
                fclose($fh);

                $path_properties = ROOT_PATH . 'cert/path.properties';
                if (!file_exists($path_properties)) {
                    $fhpp = fopen($path_properties, "a");
                    fwrite($fhpp, 'query_url=https://payment.chinapay.com/CTITS/service/rest/forward/syn/000000000060/0/0/0/0/0' . PHP_EOL);
                    fwrite($fhpp, 'pay_url=https://payment.chinapay.com/CTITS/service/rest/page/nref/000000000017/0/0/0/0/0' . PHP_EOL);
                    fwrite($fhpp, 'refund_url=https://payment.chinapay.com/CTITS/service/rest/forward/syn/000000000060/0/0/0/0/0');
                    fclose($fhpp);
                }
            }
            /*  兼容老的站点和移动端 */
            if ($_POST['pay_code'] == 'yunqi') {
                $cert = new certificate();
                foreach ($pay_config as $key => $value) {
                    if ($value['name'] == 'appkey') {
                        $appkey = $value['value'];
                    }
                    if ($value['name'] == 'appsecret') {
                        $appsecret = $value['value'];
                    }
                }
                if ($appkey && $appsecret) {
                    $status = true;
                } else {
                    $status = false;
                }
                $cert->set_yunqi_account(array('appkey' => $appkey, 'appsecret' => $appsecret, 'status' => $status));
            }
            $pay_config = serialize($pay_config);
            /* 取得和验证支付手续费 */
            $pay_fee = empty($_POST['pay_fee']) ? 0 : $_POST['pay_fee'];

            /* 检查是编辑还是安装 */
            $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'payment.php?act=list');
            if ($_POST['pay_id']) {

                /* 编辑 */
                $sql = "UPDATE " . $GLOBALS['ecs']->table('payment') .
                    "SET pay_name = '$_POST[pay_name]'," .
                    "    pay_desc = '$_POST[pay_desc]'," .
                    "    pay_config = '$pay_config', " .
                    "    pay_fee    =  '$pay_fee' " .
                    "WHERE pay_code = '$_POST[pay_code]' LIMIT 1";
                $GLOBALS['db']->query($sql);

                /* 记录日志 */
                admin_log($_POST['pay_name'], 'edit', 'payment');

                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $link);
            } else {
                /* 安装，检查该支付方式是否曾经安装过 */
                $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('payment') . " WHERE pay_code = '$_REQUEST[pay_code]'";
                if ($GLOBALS['db']->GetOne($sql) > 0) {
                    /* 该支付方式已经安装过, 将该支付方式的状态设置为 enable */
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('payment') .
                        "SET pay_name = '$_POST[pay_name]'," .
                        "    pay_desc = '$_POST[pay_desc]'," .
                        "    pay_config = '$pay_config'," .
                        "    pay_fee    =  '$pay_fee', " .
                        "    enabled = '1' " .
                        "WHERE pay_code = '$_POST[pay_code]' LIMIT 1";
                    $GLOBALS['db']->query($sql);
                } else {

                    /* 该支付方式没有安装过, 将该支付方式的信息添加到数据库 */
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('payment') . " (pay_code, pay_name, pay_desc, pay_config, is_cod, pay_fee, enabled, is_online)" .
                        "VALUES ('$_POST[pay_code]', '$_POST[pay_name]', '$_POST[pay_desc]', '$pay_config', '$_POST[is_cod]', '$pay_fee', 1, '$_POST[is_online]')";
                    $GLOBALS['db']->query($sql);
                }

                /* 记录日志 */
                admin_log($_POST['pay_name'], 'install', 'payment');

                return sys_msg($GLOBALS['_LANG']['install_ok'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 卸载支付方式 ?act=uninstall&code={$code}
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'uninstall') {
            admin_priv('payment');

            /* 把 enabled 设为 0 */
            $sql = "UPDATE " . $GLOBALS['ecs']->table('payment') .
                "SET enabled = '0' " .
                "WHERE pay_code = '$_REQUEST[code]' LIMIT 1";
            $GLOBALS['db']->query($sql);

            if ($_REQUEST['code'] == 'yunqi') {
                $dSql = "DELETE FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code='yunqi_account'";
                $GLOBALS['db']->query($dSql);
            }

            /* 记录日志 */
            admin_log($_REQUEST['code'], 'uninstall', 'payment');

            $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'payment.php?act=list');
            return sys_msg($GLOBALS['_LANG']['uninstall_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 修改支付方式名称
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_name') {
            /* 检查权限 */
            check_authz_json('payment');

            /* 取得参数 */
            $code = json_str_iconv(trim($_POST['id']));
            $name = json_str_iconv(trim($_POST['val']));

            /* 检查名称是否为空 */
            if (empty($name)) {
                return make_json_error($GLOBALS['_LANG']['name_is_null']);
            }

            /* 检查名称是否重复 */
            if (!$exc->is_only('pay_name', $name, $code)) {
                return make_json_error($GLOBALS['_LANG']['name_exists']);
            }

            /* 更新支付方式名称 */
            $exc->edit("pay_name = '$name'", $code);
            return make_json_result(stripcslashes($name));
        }

        /*------------------------------------------------------ */
        //-- 修改支付方式描述
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_desc') {
            /* 检查权限 */
            check_authz_json('payment');

            /* 取得参数 */
            $code = json_str_iconv(trim($_POST['id']));
            $desc = json_str_iconv(trim($_POST['val']));

            /* 更新描述 */
            $exc->edit("pay_desc = '$desc'", $code);
            return make_json_result(stripcslashes($desc));
        }

        /*------------------------------------------------------ */
        //-- 修改支付方式排序
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_order') {
            /* 检查权限 */
            check_authz_json('payment');

            /* 取得参数 */
            $code = json_str_iconv(trim($_POST['id']));
            $order = intval($_POST['val']);

            /* 更新排序 */
            $exc->edit("pay_order = '$order'", $code);
            return make_json_result(stripcslashes($order));
        }

        /*------------------------------------------------------ */
        //-- 修改支付方式费用
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_pay_fee') {
            /* 检查权限 */
            check_authz_json('payment');

            /* 取得参数 */
            $code = json_str_iconv(trim($_POST['id']));
            $pay_fee = json_str_iconv(trim($_POST['val']));
            if (empty($pay_fee)) {
                $pay_fee = 0;
            } else {
                $pay_fee = make_semiangle($pay_fee); //全角转半角
                if (strpos($pay_fee, '%') === false) {
                    $pay_fee = floatval($pay_fee);
                } else {
                    $pay_fee = floatval($pay_fee) . '%';
                }
            }

            /* 更新支付费用 */
            $exc->edit("pay_fee = '$pay_fee'", $code);
            return make_json_result(stripcslashes($pay_fee));
        }
    }
}
