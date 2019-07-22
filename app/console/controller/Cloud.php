<?php

namespace app\console\controller;

/**
 *  云服务接口
 */
class Cloud extends Init
{
    public function index()
    {
        require(ROOT_PATH . 'includes/shopex_json.php');

        $data['api_ver'] = '1.0';
        $data['version'] = VERSION;
        $data['patch'] = file_get_contents(ROOT_PATH . ADMIN_PATH . "/patch_num");
        $data['ecs_lang'] = $GLOBALS['_CFG']['lang'];
        $data['release'] = RELEASE;
        $data['charset'] = strtoupper(EC_CHARSET);
        $data['certificate_id'] = $GLOBALS['_CFG']['certificate_id'];
        $data['token'] = md5($GLOBALS['_CFG']['token']);
        $data['certi'] = $GLOBALS['_CFG']['certi'];
        $data['php_ver'] = PHP_VERSION;
        $data['mysql_ver'] = $GLOBALS['db']->version();
        $data['shop_url'] = urlencode($GLOBALS['ecs']->url());
        $data['admin_url'] = urlencode($GLOBALS['ecs']->url() . ADMIN_PATH);
        $data['sess_id'] = $GLOBALS['sess']->get_session_id();
        $data['stamp'] = time();
        $data['ent_id'] = $GLOBALS['_CFG']['ent_id'];
        $data['ent_ac'] = $GLOBALS['_CFG']['ent_ac'];
        $data['ent_sign'] = $GLOBALS['_CFG']['ent_sign'];
        $data['ent_email'] = $GLOBALS['_CFG']['ent_email'];

        $act = !empty($_REQUEST['act']) ? $_REQUEST['act'] : 'index';

        $must = array('version', 'ecs_lang', 'charset', 'patch', 'stamp', 'api_ver');
        if ($act == 'menu_api') {
            if (!admin_priv('all', '', false)) {
                return make_json_result('0');
            }
            $api_data = read_static_cache('menu_api');

            if ($api_data === false || (isset($api_data['api_time']) && $api_data['api_time'] < date('Ymd'))) {
                $t = new transport;
                $apiget = "ver= $data[version] &ecs_lang= $data[ecs_lang] &charset= $data[charset]&ent_id=$data[ent_id]& certificate_id=$data[certificate_id]";
                // $api_comment = $t->request('https://cloud-ecshop.xyunqi.com/menu_api.php', $apiget);
                $api_comment = $t->request('https://cloud-ecshop.xyunqi.com/menu_api.php', $apiget);
                $api_str = $api_comment["body"];
                if (!empty($api_str)) {
                    $json = new Services_JSON;
                    $api_arr = @json_decode($api_str, 1);
                    if (!empty($api_arr) && $api_arr['error'] == 0 && md5($api_arr['content']) == $api_arr['hash']) {
                        $api_arr['content'] = urldecode($api_arr['content']);
                        if ($data['charset'] != 'UTF-8') {
                            $api_arr['content'] = ecs_iconv('UTF-8', $data['charset'], $api_arr['content']);
                        }
                        $api_arr['api_time'] = date('Ymd');
                        write_static_cache('menu_api', $api_arr);
                        return make_json_result($api_arr['content']);
                    } else {
                        return make_json_result('0');
                    }
                } else {
                    return make_json_result('0');
                }
            } else {
                return make_json_result($api_data['content']);
            }
        } elseif ($act == 'load_crontab') {
            /*
             * TODO BY LANCE
             * $cert = new certificate();
            $matrix = new matrix();
            if ($cert->is_bind_sn('ecos.taocrm', 'bind_type')) {
                $matrix->push_history_order();//推送历史订单到crm
                $matrix->push_history_member();//推送历史会员到crm
            }*/
        } elseif ($act == 'cloud_remind') {
            $api_data = read_static_cache('cloud_remind');

            if ($api_data === false || (isset($api_data['api_time']) && $api_data['api_time'] < date('Ymd'))) {
                $t = new transport('-1', 5);
                $apiget = "ver=$data[version]&ecs_lang=$data[ecs_lang]&charset=$data[charset]&certificate_id=$data[certificate_id]&ent_id=$data[ent_id]";
                // $api_comment = $t->request('https://cloud-ecshop.xyunqi.com/cloud_remind.php', $apiget);
                $api_comment = $t->request('https://cloud-ecshop.xyunqi.com/cloud_remind.php', $apiget);
                $api_str = $api_comment["body"];
                $json = new Services_JSON;
                $api_arr = @json_decode($api_str, 1);
                if (!empty($api_str)) {
                    if (!empty($api_arr) && $api_arr['error'] == 0 && md5($api_arr['content']) == $api_arr['hash']) {
                        $api_arr['content'] = urldecode($api_arr['content']);
                        $message = explode('|', $api_arr['content']);
                        $api_arr['content'] = '<li  class="cloud_close">' . $message['0'] . '<img onclick="cloud_close(' . $message['1'] . ')" src="images/no.svg" width="20"></li>';
                        if ($data['charset'] != 'UTF-8') {
                            $api_arr['content'] = ecs_iconv('UTF-8', $data['charset'], $api_arr['content']);
                        }
                        $api_arr['api_time'] = date('Ymd');
                        write_static_cache('cloud_remind', $api_arr);
                        return make_json_result($api_arr['content']);
                    } else {
                        return make_json_result('0');
                    }
                } else {
                    return make_json_result('0');
                }
            } else {
                return make_json_result($api_data['content']);
            }
        } elseif ($act == 'close_remind') {
            $remind_id = $_REQUEST['remind_id'];
            $t = new transport('-1', 5);
            $apiget = "ver= $data[version] &ecs_lang= $data[ecs_lang] &charset= $data[charset] &certificate_id=$data[certificate_id]&ent_id=$data[ent_id]&remind_id=$remind_id";
            // $api_comment = $t->request('https://cloud-ecshop.xyunqi.com/cloud_remind.php', $apiget);
            $api_comment = $t->request('https://cloud-ecshop.xyunqi.com/cloud_remind.php', $apiget);

            $api_str = $api_comment["body"];
            $json = new Services_JSON;
            $api_arr = array();
            $api_arr = @json_decode($api_str, 1);
            if (!empty($api_str)) {
                if (!empty($api_arr) && $api_arr['error'] == 0 && md5($api_arr['content']) == $api_arr['hash']) {
                    $api_arr['content'] = urldecode($api_arr['content']);
                    if ($data['charset'] != 'UTF-8') {
                        $api_arr['content'] = ecs_iconv('UTF-8', $data['charset'], $api_arr['content']);
                    }
                    if (admin_priv('all', '', false)) {
                        $apiget .= "&act=close_remind&ent_ac=$data[ent_ac]";
                        // $result=$t->request('https://cloud-ecshop.xyunqi.com/cloud_remind.php', $apiget);
                        $result = $t->request('https://cloud-ecshop.xyunqi.com/cloud_remind.php', $apiget);
                        $api_str = $result["body"];
                        //var_dump($api_str);
                        $api_arr = array();
                        $api_arr = @json_decode($api_str, 1);
                        $api_arr['content'] = urldecode($api_arr['content']);
                        if ($data['charset'] != 'UTF-8') {
                            $api_arr['content'] = ecs_iconv('UTF-8', $data['charset'], $api_arr['content']);
                        }
                        if ($api_arr['error'] == 1) {
                            $message = explode('|', $api_arr['content']);
                            $api_arr['content'] = '<li  class="cloud_close">' . $message['0'] . '&nbsp;&nbsp;&nbsp;&nbsp;' . $message['2'] . '</li>';
                            return make_json_result($api_arr['content']);
                        } else {
                            clear_all_files();
                            return make_json_result('0');
                        }
                    } else {
                        $message = explode('|', $api_arr['content']);

                        $api_arr['content'] = '<li  class="cloud_close">' . $message['0'] . '&nbsp;&nbsp;&nbsp;&nbsp;' . $GLOBALS['_LANG']['cloud_no_priv'] . '<img onclick="cloud_close( ' . $message['1'] . ')" src="images/no.svg" width="20"></li>';

                        return make_json_result($api_arr['content']);
                    }
                } else {
                    return make_json_result('0');
                }
            }
        } else {
            admin_priv('all');
            if (empty($_GET['act'])) {
                $act = 'index';
            } else {
                $query = '';
                $act = trim($_GET['act']);
                foreach ($_GET as $k => $v) {
                    if (array_key_exists($k, $data)) {
                        $query .= '&' . $k . '=' . $data[$k];
                    }
                }
            }
            if (!empty($_GET['link'])) {
                $url = parse_url($_GET['link']);
                if (!empty($url['host'])) {
                    return $this->redirect($url['scheme'] . "://" . $url['host'] . $url['path'] . "?" . $url['query'] . $query);
                }
            }

            foreach ($must as $v) {
                $query .= '&' . $v . '=' . $data[$v];
            }

            return $this->redirect('https://cloud-ecshop.xyunqi.com/api.php?act=" . $act . $query . "');
        }
    }
}
