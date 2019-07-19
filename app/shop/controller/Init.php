<?php

namespace app\shop\controller;

use app\common\libraries\Error;
use app\common\libraries\Mysql;
use app\common\libraries\Session;
use app\common\libraries\Shop;
use app\common\libraries\Template;
use think\Controller;

/**
 * 前台公用文件
 * Class Init
 * @package app\shop\controller
 */
class Init extends Controller
{
    protected function initialize()
    {
        define('ROOT_PATH', base_path());

        /* https 检测https */
        if (request()->isSsl()) {
            define('FORCE_SSL_LOGIN', true);
            define('FORCE_SSL_ADMIN', true);
        }

        $php_self = parse_name(request()->controller());
        if ('/' == substr($php_self, -1)) {
            $php_self .= 'index.php';
        } else {
            $php_self .= '.php';
        }
        define('PHP_SELF', $php_self);

        load_helper('time');
        load_helper('base');
        load_helper('common');
        load_helper('main');
        load_helper('insert');
        load_helper('goods');
        load_helper('article');

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

        /* 创建 SHOP 对象 */
        $GLOBALS['ecs'] = new Shop(config('database.database'), config('database.db_prefix'));
        define('DATA_DIR', $GLOBALS['ecs']->data_dir());
        define('IMAGE_DIR', $GLOBALS['ecs']->image_dir());

        /* 初始化数据库类 */
        $GLOBALS['db'] = new Mysql(config('database.database'), config('database.database'), config('database.database'), config('database.database'));
        $GLOBALS['db']->set_disable_cache_tables(array($GLOBALS['ecs']->table('sessions'), $GLOBALS['ecs']->table('sessions_data'), $GLOBALS['ecs']->table('cart')));

        /* 创建错误处理对象 */
        $GLOBALS['err'] = new Error('message.dwt');

        /* 载入系统参数 */
        $GLOBALS['_CFG'] = load_config();
        /* 载入语言文件 */
        require(ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/common.php');

        $document_uri = $_SERVER['DOCUMENT_URI'];
        $document_uris = ['/captcha.php', '/yunqi_check.php'];
        if ($GLOBALS['_CFG']['shop_closed'] == 1 && !in_array($document_uri, $document_uris)) {
            /* 商店关闭了，输出关闭的消息 */
            header('Content-type: text/html; charset=' . EC_CHARSET);

            die('<div style="margin: 150px; text-align: center; font-size: 14px"><p>' . $GLOBALS['_LANG']['shop_closed'] . '</p><p>' . $GLOBALS['_CFG']['close_comment'] . '</p></div>');
        }

        if (is_spider()) {
            /* 如果是蜘蛛的访问，那么默认为访客方式，并且不记录到日志中 */
            if (!defined('INIT_NO_USERS')) {
                define('INIT_NO_USERS', true);
                /* 整合UC后，如果是蜘蛛访问，初始化UC需要的常量 */
                if ($GLOBALS['_CFG']['integrate_code'] == 'ucenter') {
                    $GLOBALS['user'] = init_users();
                }
            }
            $_SESSION = array();
            $_SESSION['user_id'] = 0;
            $_SESSION['user_name'] = '';
            $_SESSION['email'] = '';
            $_SESSION['user_rank'] = 0;
            $_SESSION['discount'] = 1.00;
        }

        if (!defined('INIT_NO_USERS')) {
            /* 初始化session */
            $GLOBALS['sess'] = new Session($db, $GLOBALS['ecs']->table('sessions'), $GLOBALS['ecs']->table('sessions_data'));

            define('SESS_ID', $GLOBALS['sess']->get_session_id());
        }
        if (isset($_SERVER['PHP_SELF'])) {
            $_SERVER['PHP_SELF'] = htmlspecialchars($_SERVER['PHP_SELF']);
        }
        if (!defined('INIT_NO_SMARTY')) {
            header('Cache-control: private');
            header('Content-type: text/html; charset=' . EC_CHARSET);

            /* 创建 Smarty 对象。*/
            $GLOBALS['smarty'] = new Template();

            $GLOBALS['smarty']->cache_lifetime = $GLOBALS['_CFG']['cache_time'];
            $GLOBALS['smarty']->template_dir = public_path('themes/' . $GLOBALS['_CFG']['template']);
            $GLOBALS['smarty']->cache_dir = runtime_path('temp/caches');
            $GLOBALS['smarty']->compile_dir = runtime_path('temp/compiled');

            if (config('app.app_debug')) {
                $GLOBALS['smarty']->direct_output = true;
                $GLOBALS['smarty']->force_compile = true;
            } else {
                $GLOBALS['smarty']->direct_output = false;
                $GLOBALS['smarty']->force_compile = false;
            }

            $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);
            $GLOBALS['smarty']->assign('ecs_charset', EC_CHARSET);
            $GLOBALS['smarty']->assign('template_dir', 'themes/' . $GLOBALS['_CFG']['template']);
            if (!empty($GLOBALS['_CFG']['stylename'])) {
                $GLOBALS['smarty']->assign('ecs_css_path', 'themes/' . $GLOBALS['_CFG']['template'] . '/style_' . $GLOBALS['_CFG']['stylename'] . '.css');
            } else {
                $GLOBALS['smarty']->assign('ecs_css_path', 'themes/' . $GLOBALS['_CFG']['template'] . '/style.css');
            }
        }

        if (!defined('INIT_NO_USERS')) {
            /* 会员信息 */
            $GLOBALS['user'] = init_users();

            if (!isset($_SESSION['user_id'])) {
                /* 获取投放站点的名称 */
                $site_name = isset($_GET['from']) ? htmlspecialchars($_GET['from']) : addslashes($GLOBALS['_LANG']['self_site']);
                $from_ad = !empty($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

                $_SESSION['from_ad'] = $from_ad; // 用户点击的广告ID
                $_SESSION['referer'] = stripslashes($site_name); // 用户来源

                unset($site_name);

                if (!defined('INGORE_VISIT_STATS')) {
                    visit_stats();
                }
            }

            if (empty($_SESSION['user_id'])) {
                if ($GLOBALS['user']->get_cookie()) {
                    /* 如果会员已经登录并且还没有获得会员的帐户余额、积分以及优惠券 */
                    if ($_SESSION['user_id'] > 0) {
                        update_user_info();
                    }
                } else {
                    $_SESSION['user_id'] = 0;
                    $_SESSION['user_name'] = '';
                    $_SESSION['email'] = '';
                    $_SESSION['user_rank'] = 0;
                    $_SESSION['discount'] = 1.00;
                    if (!isset($_SESSION['login_fail'])) {
                        $_SESSION['login_fail'] = 0;
                    }
                }
            }

            /* 设置推荐会员 */
            if (isset($_GET['u'])) {
                set_affiliate();
            }

            /* session 不存在，检查cookie */
            if (!empty($_COOKIE['ECS']['user_id']) && !empty($_COOKIE['ECS']['password'])) {
                // 找到了cookie, 验证cookie信息
                $sql = 'SELECT user_id, user_name, password ' .
                    ' FROM ' . $GLOBALS['ecs']->table('users') .
                    " WHERE user_id = '" . intval($_COOKIE['ECS']['user_id']) . "' AND password = '" . $_COOKIE['ECS']['password'] . "'";

                $row = $GLOBALS['db']->GetRow($sql);

                if (!$row) {
                    // 没有找到这个记录
                    $time = time() - 3600;
                    setcookie("ECS[user_id]", '', $time, '/', null, null, true);
                    setcookie("ECS[password]", '', $time, '/', null, null, true);
                } else {
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['user_name'] = $row['user_name'];
                    update_user_info();
                }
            }

            if (isset($smarty)) {
                $GLOBALS['smarty']->assign('ecs_session', $_SESSION);
            }
        }

        /* 判断是否支持 Gzip 模式 */
        if (!defined('INIT_NO_SMARTY') && gzip_enabled()) {
            ob_start('ob_gzhandler');
        } else {
            ob_start();
        }
    }
}
