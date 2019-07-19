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

        error_reporting(E_ALL);

        if (__FILE__ == '') {
            die('Fatal error code: 0');
        }

        /* 初始化设置 */
        @ini_set('memory_limit', '64M');
        @ini_set('session.cache_expire', 600);
        @ini_set('session.use_trans_sid', 0);
        @ini_set('session.use_cookies', 1);
        @ini_set('session.auto_start', 0);
        @ini_set('display_errors', 0);

        if (DIRECTORY_SEPARATOR == '\\') {
            @ini_set('include_path', '.;' . ROOT_PATH);
        } else {
            @ini_set('include_path', '.:' . ROOT_PATH);
        }

        if (file_exists('../data/config.php')) {
            include('../data/config.php');
        } else {
            include('../includes/config.php');
        }

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

        /* 对路径进行安全处理 */
        if (strpos(PHP_SELF, '.php/') !== false) {
            ecs_header("Location:" . substr(PHP_SELF, 0, strpos(PHP_SELF, '.php/') + 4) . "\n");
            exit();
        }

        /* 创建 ECSHOP 对象 */
        $ecs = new ECS($db_name, $prefix);
        define('DATA_DIR', $ecs->data_dir());
        define('IMAGE_DIR', $ecs->image_dir());

        /* 初始化数据库类 */
        $db = new Mysql($db_host, $db_user, $db_pass, $db_name);

        /* 创建错误处理对象 */
        $err = new Error('message.htm');

        /* 初始化session */
        $sess = new Session($db, $ecs->table('sessions'), $ecs->table('sessions_data'), 'ECSCP_ID');

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
        $_CFG = load_config();

        // TODO : 登录部分准备拿出去做，到时候把以下操作一起挪过去
        if ($_REQUEST['act'] == 'captcha') {
            $img = new Captcha('../data/captcha/', 104, 36);
            @ob_end_clean(); //清除之前出现的多余输入
            $img->generate_image();

            exit;
        }

        require(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/admin/common.php');
        require(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/admin/log_action.php');

        if (file_exists(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/admin/' . basename(PHP_SELF))) {
            include(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/admin/' . basename(PHP_SELF));
        }

        if (!file_exists('../temp/caches')) {
            @mkdir('../temp/caches', 0777);
            @chmod('../temp/caches', 0777);
        }

        if (!file_exists('../temp/compiled/admin')) {
            @mkdir('../temp/compiled/admin', 0777);
            @chmod('../temp/compiled/admin', 0777);
        }

        clearstatcache();

        /* 如果有新版本，升级 */
        if (!isset($_CFG['ecs_version'])) {
            $_CFG['ecs_version'] = 'v4.0.0';
        }

        if (preg_replace('/(?:\.|\s+)[a-z]*$/i', '', $_CFG['ecs_version']) != preg_replace('/(?:\.|\s+)[a-z]*$/i', '', VERSION)
            && file_exists('../upgrade/index.php')) {
            // echo "<pre>";var_dump($_CFG['ecs_version'],VERSION,preg_replace('/(?:\.|\s+)[a-z]*$/i', '', $_CFG['ecs_version']),preg_replace('/(?:\.|\s+)[a-z]*$/i', '', VERSION));exit;
            // 转到升级文件
            ecs_header("Location: ../upgrade/index.php\n");

            exit;
        }

        /* 创建 Smarty 对象。*/
        $smarty = new Template();

        $smarty->template_dir = ROOT_PATH . ADMIN_PATH . '/templates';
        $smarty->compile_dir = ROOT_PATH . 'temp/compiled/admin';

        $smarty->assign('lang', $_LANG);
        $smarty->assign('help_open', $_CFG['help_open']);

        if (isset($_CFG['enable_order_check'])) {  // 为了从旧版本顺利升级到2.5.0
            $smarty->assign('enable_order_check', $_CFG['enable_order_check']);
        } else {
            $smarty->assign('enable_order_check', 0);
        }

        /* 验证通行证信息 */
        if (isset($_GET['ent_id']) && isset($_GET['ent_ac']) && isset($_GET['ent_sign']) && isset($_GET['ent_email'])) {
            $ent_id = trim($_GET['ent_id']);
            $ent_ac = trim($_GET['ent_ac']);
            $ent_sign = trim($_GET['ent_sign']);
            $ent_email = trim($_GET['ent_email']);
            $certificate_id = trim($_CFG['certificate_id']);
            $domain_url = $ecs->url();
            $token = $_GET['token'];
            if ($token == md5(md5($_CFG['token']) . $domain_url . ADMIN_PATH)) {
                $t = new transport('-1', 5);
                $apiget = "act=ent_sign&ent_id= $ent_id & certificate_id=$certificate_id";

                // $t->request('https://cloud-ecshop.xyunqi.com/api.php', $apiget);
                $t->request('https://cloud-ecshop.xyunqi.com/api.php', $apiget);
                $db->query('UPDATE ' . $ecs->table('shop_config') . ' SET value = "' . $ent_id . '" WHERE code = "ent_id"');
                $db->query('UPDATE ' . $ecs->table('shop_config') . ' SET value = "' . $ent_ac . '" WHERE code = "ent_ac"');
                $db->query('UPDATE ' . $ecs->table('shop_config') . ' SET value = "' . $ent_sign . '" WHERE code = "ent_sign"');
                $db->query('UPDATE ' . $ecs->table('shop_config') . ' SET value = "' . $ent_email . '" WHERE code = "ent_email"');
                clear_cache_files();
                ecs_header("Location: ./index.php\n");
            }
        }

        /* 验证管理员身份 */
        if ((!isset($_SESSION['admin_id']) || intval($_SESSION['admin_id']) <= 0) &&
            $_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order' && $_REQUEST['act'] != 'yq_login' && $_REQUEST['act'] != 'is_yunqi_admin' && $_REQUEST['act'] != 'get_certificate') {
            /* session 不存在，检查cookie */
            if (!empty($_COOKIE['ECSCP']['admin_id']) && !empty($_COOKIE['ECSCP']['admin_pass'])) {
                // 找到了cookie, 验证cookie信息
                $sql = 'SELECT user_id, user_name, password, add_time, action_list, last_login ' .
                    ' FROM ' . $ecs->table('admin_user') .
                    " WHERE user_id = '" . intval($_COOKIE['ECSCP']['admin_id']) . "'";
                $row = $db->GetRow($sql);

                if (!$row) {
                    // 没有找到这个记录
                    setcookie($_COOKIE['ECSCP']['admin_id'], '', 1, null, null, null, true);
                    setcookie($_COOKIE['ECSCP']['admin_pass'], '', 1, null, null, null, true);

                    if (!empty($_REQUEST['is_ajax'])) {
                        make_json_error($_LANG['priv_error']);
                    } else {
                        ecs_header("Location: privilege.php?act=login\n");
                    }

                    exit;
                } else {
                    // 检查密码是否正确
                    if (md5($row['password'] . $_CFG['hash_code'] . $row['add_time']) == $_COOKIE['ECSCP']['admin_pass']) {
                        !isset($row['last_time']) && $row['last_time'] = '';
                        set_admin_session($row['user_id'], $row['user_name'], $row['action_list'], $row['last_time']);

                        // 更新最后登录时间和IP
                        $db->query('UPDATE ' . $ecs->table('admin_user') .
                            " SET last_login = '" . gmtime() . "', last_ip = '" . real_ip() . "'" .
                            " WHERE user_id = '" . $_SESSION['admin_id'] . "'");
                    } else {
                        setcookie($_COOKIE['ECSCP']['admin_id'], '', 1, null, null, null, true);
                        setcookie($_COOKIE['ECSCP']['admin_pass'], '', 1, null, null, null, true);

                        if (!empty($_REQUEST['is_ajax'])) {
                            make_json_error($_LANG['priv_error']);
                        } else {
                            ecs_header("Location: privilege.php?act=login\n");
                        }

                        exit;
                    }
                }
            } else {
                if (!empty($_REQUEST['is_ajax'])) {
                    make_json_error($_LANG['priv_error']);
                } else {
                    ecs_header("Location: privilege.php?act=login\n");
                }

                exit;
            }
        }

        $smarty->assign('token', $_CFG['token']);

        if ($_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order') {
            $admin_path = preg_replace('/:\d+/', '', $ecs->url()) . ADMIN_PATH;
            if (!empty($_SERVER['HTTP_REFERER']) &&
                strpos(preg_replace('/:\d+/', '', $_SERVER['HTTP_REFERER']), $admin_path) === false) {
                if (!empty($_REQUEST['is_ajax'])) {
                    make_json_error($_LANG['priv_error']);
                } else {
                    ecs_header("Location: privilege.php?act=login\n");
                }

                exit;
            }
        }
    }
}
