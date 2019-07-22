<?php

namespace app\console\controller;

/**
 * 角色管理信息以及权限管理程序
 */
class Role extends Init
{
    public function index()
    {


        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'login';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /* 初始化 $exc 对象 */
        $exc = new Exchange($GLOBALS['ecs']->table("role"), $GLOBALS['db'], 'role_id', 'role_name');

        /*------------------------------------------------------ */
        //-- 退出登录
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'logout') {
            /* 清除cookie */
            setcookie('ECSCP[admin_id]', '', 1, null, null, null, true);
            setcookie('ECSCP[admin_pass]', '', 1, null, null, null, true);

            $GLOBALS['sess']->destroy_session();

            $_REQUEST['act'] = 'login';
        }

        /*------------------------------------------------------ */
        //-- 登陆界面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'login') {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");

            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_ADMIN) && gd_version() > 0) {
                $GLOBALS['smarty']->assign('gd_version', gd_version());
                $GLOBALS['smarty']->assign('random', mt_rand());
            }

            return $GLOBALS['smarty']->display('login.htm');
        }


        /*------------------------------------------------------ */
        //-- 角色列表页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['admin_role']);
            $GLOBALS['smarty']->assign('action_link', array('href' => 'role.php?act=add', 'text' => $GLOBALS['_LANG']['admin_add_role']));
            $GLOBALS['smarty']->assign('full_page', 1);
            $GLOBALS['smarty']->assign('admin_list', $this->get_role_list());

            /* 显示页面 */
            assign_query_info();
            return $GLOBALS['smarty']->display('role_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $GLOBALS['smarty']->assign('admin_list', $this->get_role_list());

            return make_json_result($GLOBALS['smarty']->fetch('role_list.htm'));
        }

        /*------------------------------------------------------ */
        //-- 添加角色页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('admin_manage');
            load_lang('admin/priv_action');

            $priv_str = '';

            /* 获取权限的分组数据 */
            $sql_query = "SELECT action_id, parent_id, action_code, relevance FROM " . $GLOBALS['ecs']->table('admin_action') .
                " WHERE parent_id = 0";
            $res = $GLOBALS['db']->query($sql_query);
            foreach ($res as $rows) {
                $priv_arr[$rows['action_id']] = $rows;
            }


            /* 按权限组查询底级的权限名称 */
            $sql = "SELECT action_id, parent_id, action_code, relevance FROM " . $GLOBALS['ecs']->table('admin_action') .
                " WHERE parent_id " . db_create_in(array_keys($priv_arr));
            $result = $GLOBALS['db']->query($sql);
            foreach ($result as $priv) {
                $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
            }

            // 将同一组的权限使用 "," 连接起来，供JS全选
            foreach ($priv_arr as $action_id => $action_group) {
                $priv_arr[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));

                foreach ($action_group['priv'] as $key => $val) {
                    $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                }
            }

            /* 模板赋值 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['admin_add_role']);
            $GLOBALS['smarty']->assign('action_link', array('href' => 'role.php?act=list', 'text' => $GLOBALS['_LANG']['admin_list_role']));
            $GLOBALS['smarty']->assign('form_act', 'insert');
            $GLOBALS['smarty']->assign('action', 'add');
            $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);
            $GLOBALS['smarty']->assign('priv_arr', $priv_arr);

            /* 显示页面 */
            assign_query_info();
            return $GLOBALS['smarty']->display('role_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 添加角色的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('admin_manage');
            $act_list = @join(",", $_POST['action_code']);
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('role') . " (role_name, action_list, role_describe) " .
                "VALUES ('" . trim($_POST['user_name']) . "','$act_list','" . trim($_POST['role_describe']) . "')";

            $GLOBALS['db']->query($sql);
            /* 转入权限分配列表 */
            $new_id = $GLOBALS['db']->Insert_ID();

            /*添加链接*/

            $link[0]['text'] = $GLOBALS['_LANG']['admin_list_role'];
            $link[0]['href'] = 'role.php?act=list';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);

            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'add', 'role');
        }

        /*------------------------------------------------------ */
        //-- 编辑角色信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            load_lang('admin/priv_action');
            $_REQUEST['id'] = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            /* 获得该管理员的权限 */
            $priv_str = $GLOBALS['db']->getOne("SELECT action_list FROM " . $GLOBALS['ecs']->table('role') . " WHERE role_id = '$_GET[id]'");

            /* 查看是否有权限编辑其他管理员的信息 */
            if ($_SESSION['admin_id'] != $_REQUEST['id']) {
                admin_priv('admin_manage');
            }

            /* 获取角色信息 */
            $sql = "SELECT role_id, role_name, role_describe FROM " . $GLOBALS['ecs']->table('role') .
                " WHERE role_id = '" . $_REQUEST['id'] . "'";
            $user_info = $GLOBALS['db']->getRow($sql);

            /* 获取权限的分组数据 */
            $sql_query = "SELECT action_id, parent_id, action_code,relevance FROM " . $GLOBALS['ecs']->table('admin_action') .
                " WHERE parent_id = 0";
            $res = $GLOBALS['db']->query($sql_query);
            foreach ($res as $rows) {
                $priv_arr[$rows['action_id']] = $rows;
            }

            /* 按权限组查询底级的权限名称 */
            $sql = "SELECT action_id, parent_id, action_code,relevance FROM " . $GLOBALS['ecs']->table('admin_action') .
                " WHERE parent_id " . db_create_in(array_keys($priv_arr));
            $result = $GLOBALS['db']->query($sql);
            foreach ($result as $priv) {
                $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
            }

            // 将同一组的权限使用 "," 连接起来，供JS全选
            foreach ($priv_arr as $action_id => $action_group) {
                $priv_arr[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));

                foreach ($action_group['priv'] as $key => $val) {
                    $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                }
            }


            /* 模板赋值 */

            $GLOBALS['smarty']->assign('user', $user_info);
            $GLOBALS['smarty']->assign('form_act', 'update');
            $GLOBALS['smarty']->assign('action', 'edit');
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['admin_edit_role']);
            $GLOBALS['smarty']->assign('action_link', array('href' => 'role.php?act=list', 'text' => $GLOBALS['_LANG']['admin_list_role']));
            $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);
            $GLOBALS['smarty']->assign('priv_arr', $priv_arr);
            $GLOBALS['smarty']->assign('user_id', $_GET['id']);

            assign_query_info();
            return $GLOBALS['smarty']->display('role_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 更新角色信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 更新管理员的权限 */
            $act_list = @join(",", $_POST['action_code']);
            $sql = "UPDATE " . $GLOBALS['ecs']->table('role') . " SET action_list = '$act_list', role_name = '" . $_POST['user_name'] . "', role_describe = '" . $_POST['role_describe'] . " ' " .
                "WHERE role_id = '$_POST[id]'";
            $GLOBALS['db']->query($sql);
            $user_sql = "UPDATE " . $GLOBALS['ecs']->table('admin_user') . " SET action_list = '$act_list' " .
                "WHERE role_id = '$_POST[id]'";
            $GLOBALS['db']->query($user_sql);
            /* 提示信息 */
            $link[] = array('text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'role.php?act=list');
            return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除一个角色
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            check_authz_json('admin_drop');

            $id = intval($_GET['id']);
            $num_sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE role_id = '$_GET[id]'";
            $remove_num = $GLOBALS['db']->getOne($num_sql);
            if ($remove_num > 0) {
                return make_json_error($GLOBALS['_LANG']['remove_cannot_user']);
            } else {
                $exc->drop($id);
                $url = 'role.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
            }

            return $this->redirect($url);

        }
    }

    /* 获取角色列表 */
    private function get_role_list()
    {
        $list = array();
        $sql = 'SELECT role_id, role_name, action_list, role_describe ' .
            'FROM ' . $GLOBALS['ecs']->table('role') . ' ORDER BY role_id DESC';
        $list = $GLOBALS['db']->getAll($sql);

        return $list;
    }
}
