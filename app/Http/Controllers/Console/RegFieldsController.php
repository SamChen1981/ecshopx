<?php

namespace app\console\controller;

/**
 * 会员等级管理程序
 */
class RegFields extends Init
{
    public function index()
    {
        $exc = new Exchange($GLOBALS['ecs']->table("reg_fields"), $GLOBALS['db'], 'id', 'reg_field_name');

        /*------------------------------------------------------ */
        //-- 会员注册项列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            $fields = array();
            $fields = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('reg_fields') . " ORDER BY dis_order, id");

            $this->assign('ur_here', $GLOBALS['_LANG']['021_reg_fields']);
            $this->assign('action_link', array('text' => $GLOBALS['_LANG']['add_reg_field'], 'href' => 'reg_fields.php?act=add'));
            $this->assign('full_page', 1);

            $this->assign('reg_fields', $fields);

            assign_query_info();
            return $this->fetch('reg_fields');
        }


        /*------------------------------------------------------ */
        //-- 翻页，排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $fields = array();
            $fields = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('reg_fields') . "ORDER BY id");

            $this->assign('reg_fields', $fields);
            return make_json_result($GLOBALS['smarty']->fetch('reg_fields.htm'));
        }

        /*------------------------------------------------------ */
        //-- 添加会员注册项
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add') {
            admin_priv('reg_fields');

            $form_action = 'insert';

            $reg_field['reg_field_order'] = 100;
            $reg_field['reg_field_display'] = 1;
            $reg_field['reg_field_need'] = 1;

            $this->assign('reg_field', $reg_field);
            $this->assign('ur_here', $GLOBALS['_LANG']['add_reg_field']);
            $this->assign('action_link', array('text' => $GLOBALS['_LANG']['021_reg_fields'], 'href' => 'reg_fields.php?act=list'));
            $this->assign('form_action', $form_action);

            assign_query_info();
            return $this->fetch('reg_field_info');
        }

        /*------------------------------------------------------ */
        //-- 增加会员注册项到数据库
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('reg_fields');

            /* 检查是否存在重名的会员注册项 */
            if (!$exc->is_only('reg_field_name', trim($_POST['reg_field_name']))) {
                return sys_msg(sprintf($GLOBALS['_LANG']['field_name_exist'], trim($_POST['reg_field_name'])), 1);
            }

            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('reg_fields') . "( " .
                "reg_field_name, dis_order, display, is_need" .
                ") VALUES (" .
                "'$_POST[reg_field_name]', '$_POST[reg_field_order]', '$_POST[reg_field_display]', '$_POST[reg_field_need]')";
            $GLOBALS['db']->query($sql);

            /* 管理员日志 */
            admin_log(trim($_POST['reg_field_name']), 'add', 'reg_fields');
            clear_cache_files();

            $lnk[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'reg_fields.php?act=list');
            $lnk[] = array('text' => $GLOBALS['_LANG']['add_continue'], 'href' => 'reg_fields.php?act=add');
            return sys_msg($GLOBALS['_LANG']['add_field_success'], 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 编辑会员注册项
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit') {
            admin_priv('reg_fields');

            $form_action = 'update';

            $sql = "SELECT id AS reg_field_id, reg_field_name, dis_order AS reg_field_order, display AS reg_field_display, is_need AS reg_field_need FROM " .
                $GLOBALS['ecs']->table('reg_fields') . " WHERE id='$_REQUEST[id]'";
            $reg_field = $GLOBALS['db']->GetRow($sql);

            $this->assign('reg_field', $reg_field);
            $this->assign('ur_here', $GLOBALS['_LANG']['add_reg_field']);
            $this->assign('action_link', array('text' => $GLOBALS['_LANG']['021_reg_fields'], 'href' => 'reg_fields.php?act=list'));
            $this->assign('form_action', $form_action);

            assign_query_info();
            return $this->fetch('reg_field_info');
        }

        /*------------------------------------------------------ */
        //-- 更新会员注册项
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'update') {
            admin_priv('reg_fields');

            /* 检查是否存在重名的会员注册项 */
            if ($_POST['reg_field_name'] != $_POST['old_field_name'] && !$exc->is_only('reg_field_name', trim($_POST['reg_field_name']))) {
                return sys_msg(sprintf($GLOBALS['_LANG']['field_name_exist'], trim($_POST['reg_field_name'])), 1);
            }

            $sql = "UPDATE " . $GLOBALS['ecs']->table('reg_fields') . " SET `reg_field_name` = '$_POST[reg_field_name]', `dis_order` = '$_POST[reg_field_order]', `display` = '$_POST[reg_field_display]', `is_need` = '$_POST[reg_field_need]' WHERE `id` = '$_POST[id]'";
            $GLOBALS['db']->query($sql);

            /* 管理员日志 */
            admin_log(trim($_POST['reg_field_name']), 'edit', 'reg_fields');
            clear_cache_files();

            $lnk[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'reg_fields.php?act=list');
            return sys_msg($GLOBALS['_LANG']['update_field_success'], 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 删除会员注册项
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            check_authz_json('reg_fields');

            $field_id = intval($_GET['id']);
            $field_name = $exc->get_name($field_id);

            if ($exc->drop($field_id)) {
                /* 删除会员扩展信息表的相应信息 */
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('reg_extend_info') . " WHERE reg_field_id = '" . $field_id . "'";
                @$GLOBALS['db']->query($sql);

                admin_log(addslashes($field_name), 'remove', 'reg_fields');
                clear_cache_files();
            }

            $url = 'reg_fields.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return $this->redirect($url);

        } /*
 *  编辑会员注册项名称
 */
        elseif ($_REQUEST['act'] == 'edit_name') {
            $id = intval($_REQUEST['id']);
            $val = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));
            check_authz_json('reg_fields');
            if ($exc->is_only('reg_field_name', $val, $id)) {
                if ($exc->edit("reg_field_name = '$val'", $id)) {
                    /* 管理员日志 */
                    admin_log($val, 'edit', 'reg_fields');
                    clear_cache_files();
                    return make_json_result(stripcslashes($val));
                } else {
                    return make_json_error($GLOBALS['db']->error());
                }
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['field_name_exist'], htmlspecialchars($val)));
            }
        } /*
 *  编辑会员注册项排序权值
 */
        elseif ($_REQUEST['act'] == 'edit_order') {
            $id = intval($_REQUEST['id']);
            $val = isset($_REQUEST['val']) ? json_str_iconv(trim($_REQUEST['val'])) : '';
            check_authz_json('reg_fields');
            if (is_numeric($val)) {
                if ($exc->edit("dis_order = '$val'", $id)) {
                    /* 管理员日志 */
                    admin_log($val, 'edit', 'reg_fields');
                    clear_cache_files();
                    return make_json_result(stripcslashes($val));
                } else {
                    return make_json_error($GLOBALS['db']->error());
                }
            } else {
                return make_json_error($GLOBALS['_LANG']['order_not_num']);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改会员注册项显示状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_dis') {
            check_authz_json('reg_fields');

            $id = intval($_POST['id']);
            $is_dis = intval($_POST['val']);

            if ($exc->edit("display = '$is_dis'", $id)) {
                clear_cache_files();
                return make_json_result($is_dis);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改会员注册项必填状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_need') {
            check_authz_json('reg_fields');

            $id = intval($_POST['id']);
            $is_need = intval($_POST['val']);

            if ($exc->edit("is_need = '$is_need'", $id)) {
                clear_cache_files();
                return make_json_result($is_need);
            }
        }
    }
}
