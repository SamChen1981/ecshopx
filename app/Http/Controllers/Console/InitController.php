<?php

namespace app\console\controller;

use app\common\libraries\Captcha;
use app\common\libraries\Error;
use app\common\libraries\Session;
use app\common\libraries\Template;
use think\Controller;

/**
 * 管理中心公用文件
 */
class Init extends Controller
{
    protected function initialize()
    {
        require_once(str_replace('/admin/includes', '/includes', str_replace('\\', '/', dirname(__FILE__))) . '/safety.php');

        /* https 检测https */
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            define('FORCE_SSL_LOGIN', true);
            define('FORCE_SSL_ADMIN', true);
        } else {
            if (isset($_SERVER['HTTP_ORIGIN']) && substr($_SERVER['HTTP_ORIGIN'], 0, 5) == 'https') {
                $_SERVER['HTTPS'] = 'on';
                define('FORCE_SSL_LOGIN', true);
                define('FORCE_SSL_ADMIN', true);
            }
        }

        /* https 登陆失败 */

        define('ECS_ADMIN', true);

        /* 取得当前ecshop所在的根目录 */
        if (!defined('ADMIN_PATH')) {
            define('ADMIN_PATH', 'admin');
        }
        define('ROOT_PATH', str_replace(ADMIN_PATH . '/includes/init.php', '', str_replace('\\', '/', __FILE__)));

        if (isset($_SERVER['PHP_SELF'])) {
            define('PHP_SELF', $_SERVER['PHP_SELF']);
        } else {
            define('PHP_SELF', $_SERVER['SCRIPT_NAME']);
        }

        load_helper('time');
        load_helper('base');
        load_helper('common');
        load_helper('main', 'console');

        /* 对用户传入的变量进行转义操作。*/
        if (!get_magic_quotes_gpc()) {
            if (!empty($_GET)) {
                $_GET = addslashes_deep($_GET);
            }
            if (!empty($_POST)) {
                $_POST = addslashes_deep($_POST);
            }

            $_COOKIE = addslashes_deep($_COOKIE);
            $_REQUEST = addslashes_deep($_REQUEST);
        }

        /* 创建 ECSHOP 对象 */
        $GLOBALS['ecs'] = new ECS($db_name, $prefix);
        define('DATA_DIR', $GLOBALS['ecs']->data_dir());
        define('IMAGE_DIR', $GLOBALS['ecs']->image_dir());

        /* 初始化数据库类 */
        $GLOBALS['db'] = new Mysql();

        /* 创建错误处理对象 */
        $GLOBALS['err'] = new Error('message.htm');

        /* 初始化session */
        $GLOBALS['sess'] = new Session($GLOBALS['db'], $GLOBALS['ecs']->table('sessions'), $GLOBALS['ecs']->table('sessions_data'), 'ECSCP_ID');

        /* 初始化 action */
        if (!isset($_REQUEST['act'])) {
            $_REQUEST['act'] = '';
        } elseif (($_REQUEST['act'] == 'login' || $_REQUEST['act'] == 'logout' || $_REQUEST['act'] == 'signin') &&
            strpos(PHP_SELF, '/privilege.php') === false) {
            $_REQUEST['act'] = '';
        } elseif (($_REQUEST['act'] == 'forget_pwd' || $_REQUEST['act'] == 'reset_pwd' || $_REQUEST['act'] == 'get_pwd') &&
            strpos(PHP_SELF, '/get_password.php') === false) {
            $_REQUEST['act'] = '';
        }

        /* 载入系统参数 */
        $GLOBALS['_CFG'] = load_config();

        // TODO : 登录部分准备拿出去做，到时候把以下操作一起挪过去
        if ($_REQUEST['act'] == 'captcha') {
            $img = new Captcha('../data/captcha/', 104, 36);
            @ob_end_clean(); //清除之前出现的多余输入
            $img->generate_image();
        }

        load_lang('admin/common');
        load_lang('admin/log_action');

        if (file_exists(ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/admin/' . basename(PHP_SELF))) {
            include(ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/admin/' . basename(PHP_SELF));
        }

        $this->assign('lang', $GLOBALS['_LANG']);

        /* 验证管理员身份 */
        if ((!isset($_SESSION['admin_id']) || intval($_SESSION['admin_id']) <= 0) &&
            $_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order' && $_REQUEST['act'] != 'yq_login' && $_REQUEST['act'] != 'is_yunqi_admin' && $_REQUEST['act'] != 'get_certificate') {
            /* session 不存在，检查cookie */
            if (!empty($_COOKIE['ECSCP']['admin_id']) && !empty($_COOKIE['ECSCP']['admin_pass'])) {
                // 找到了cookie, 验证cookie信息
                $sql = 'SELECT user_id, user_name, password, add_time, action_list, last_login ' .
                    ' FROM ' . $GLOBALS['ecs']->table('admin_user') .
                    " WHERE user_id = '" . intval($_COOKIE['ECSCP']['admin_id']) . "'";
                $row = $GLOBALS['db']->GetRow($sql);

                if (!$row) {
                    // 没有找到这个记录
                    setcookie($_COOKIE['ECSCP']['admin_id'], '', 1, null, null, null, true);
                    setcookie($_COOKIE['ECSCP']['admin_pass'], '', 1, null, null, null, true);

                    if (!empty($_REQUEST['is_ajax'])) {
                        return make_json_error($GLOBALS['_LANG']['priv_error']);
                    } else {
                        return $this->redirect('privilege.php?act=login');
                    }


                } else {
                    // 检查密码是否正确
                    if (md5($row['password'] . $GLOBALS['_CFG']['hash_code'] . $row['add_time']) == $_COOKIE['ECSCP']['admin_pass']) {
                        !isset($row['last_time']) && $row['last_time'] = '';
                        set_admin_session($row['user_id'], $row['user_name'], $row['action_list'], $row['last_time']);

                        // 更新最后登录时间和IP
                        $GLOBALS['db']->query('UPDATE ' . $GLOBALS['ecs']->table('admin_user') .
                            " SET last_login = '" . gmtime() . "', last_ip = '" . real_ip() . "'" .
                            " WHERE user_id = '" . $_SESSION['admin_id'] . "'");
                    } else {
                        setcookie($_COOKIE['ECSCP']['admin_id'], '', 1, null, null, null, true);
                        setcookie($_COOKIE['ECSCP']['admin_pass'], '', 1, null, null, null, true);

                        if (!empty($_REQUEST['is_ajax'])) {
                            return make_json_error($GLOBALS['_LANG']['priv_error']);
                        } else {
                            return $this->redirect('privilege.php?act=login');
                        }


                    }
                }
            } else {
                if (!empty($_REQUEST['is_ajax'])) {
                    return make_json_error($GLOBALS['_LANG']['priv_error']);
                } else {
                    return $this->redirect('privilege.php?act=login');
                }


            }
        }

        $this->assign('token', $GLOBALS['_CFG']['token']);

        if ($_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order') {
            $admin_path = preg_replace('/:\d+/', '', $GLOBALS['ecs']->url()) . ADMIN_PATH;
            if (!empty($_SERVER['HTTP_REFERER']) &&
                strpos(preg_replace('/:\d+/', '', $_SERVER['HTTP_REFERER']), $admin_path) === false) {
                if (!empty($_REQUEST['is_ajax'])) {
                    return make_json_error($GLOBALS['_LANG']['priv_error']);
                } else {
                    return $this->redirect('privilege.php?act=login');
                }


            }
        }
    }
}
