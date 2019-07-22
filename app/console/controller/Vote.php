<?php

namespace app\console\controller;

/**
 *  调查管理程序
 */
class Vote extends Init
{
    public function index()
    {


        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $exc = new Exchange($GLOBALS['ecs']->table("vote"), $GLOBALS['db'], 'vote_id', 'vote_name');
        $exc_opn = new Exchange($GLOBALS['ecs']->table("vote_option"), $GLOBALS['db'], 'option_id', 'option_name');

        /*------------------------------------------------------ */
        //-- 投票列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['list_vote']);
            $GLOBALS['smarty']->assign('action_link', array('text' => $GLOBALS['_LANG']['add_vote'], 'href' => 'vote.php?act=add'));
            $GLOBALS['smarty']->assign('full_page', 1);

            $vote_list = $this->get_votelist();

            $GLOBALS['smarty']->assign('list', $vote_list['list']);
            $GLOBALS['smarty']->assign('filter', $vote_list['filter']);
            $GLOBALS['smarty']->assign('record_count', $vote_list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $vote_list['page_count']);

            /* 显示页面 */
            assign_query_info();
            return $GLOBALS['smarty']->display('vote_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $vote_list = $this->get_votelist();

            $GLOBALS['smarty']->assign('list', $vote_list['list']);
            $GLOBALS['smarty']->assign('filter', $vote_list['filter']);
            $GLOBALS['smarty']->assign('record_count', $vote_list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $vote_list['page_count']);

            return make_json_result(
                $GLOBALS['smarty']->fetch('vote_list.htm'),
                '',
                array('filter' => $vote_list['filter'], 'page_count' => $vote_list['page_count'])
            );
        }

        /*------------------------------------------------------ */
        //-- 添加新的投票页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 权限检查 */
            admin_priv('vote_priv');

            /* 日期初始化 */
            $vote = array('start_time' => local_date('Y-m-d'), 'end_time' => local_date('Y-m-d', local_strtotime('+2 weeks')));

            /* 模板赋值 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['add_vote']);
            $GLOBALS['smarty']->assign('action_link', array('href' => 'vote.php?act=list', 'text' => $GLOBALS['_LANG']['list_vote']));

            $GLOBALS['smarty']->assign('action', 'add');
            $GLOBALS['smarty']->assign('form_act', 'insert');
            $GLOBALS['smarty']->assign('vote_arr', $vote);
            $GLOBALS['smarty']->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            /* 显示页面 */
            assign_query_info();
            return $GLOBALS['smarty']->display('vote_info.htm');
        } elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('vote_priv');

            /* 获得广告的开始时期与结束日期 */
            $start_time = local_strtotime($_POST['start_time']);
            $end_time = local_strtotime($_POST['end_time']);

            /* 查看广告名称是否有重复 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('vote') . " WHERE vote_name='$_POST[vote_name]'";
            if ($GLOBALS['db']->getOne($sql) == 0) {
                /* 插入数据 */
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('vote') . " (vote_name, start_time, end_time, can_multi, vote_count)
        VALUES ('$_POST[vote_name]', '$start_time', '$end_time', '$_POST[can_multi]', '0')";
                $GLOBALS['db']->query($sql);

                $new_id = $GLOBALS['db']->Insert_ID();

                /* 记录管理员操作 */
                admin_log($_POST['vote_name'], 'add', 'vote');

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['continue_add_option'];
                $link[0]['href'] = 'vote.php?act=option&id=' . $new_id;

                $link[1]['text'] = $GLOBALS['_LANG']['continue_add_vote'];
                $link[1]['href'] = 'vote.php?act=add';

                $link[2]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[2]['href'] = 'vote.php?act=list';

                return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['vote_name'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            } else {
                $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                return sys_msg($GLOBALS['_LANG']['vote_name_exist'], 0, $link);
            }
        }
        /*------------------------------------------------------ */
        //-- 在线调查编辑页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            admin_priv('vote_priv');

            /* 获取数据 */
            $vote_arr = $GLOBALS['db']->GetRow("SELECT * FROM " . $GLOBALS['ecs']->table('vote') . " WHERE vote_id='$_REQUEST[id]'");
            $vote_arr['start_time'] = local_date('Y-m-d', $vote_arr['start_time']);
            $vote_arr['end_time'] = local_date('Y-m-d', $vote_arr['end_time']);

            /* 模板赋值 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['edit_vote']);
            $GLOBALS['smarty']->assign('action_link', array('href' => 'vote.php?act=list', 'text' => $GLOBALS['_LANG']['list_vote']));
            $GLOBALS['smarty']->assign('form_act', 'update');
            $GLOBALS['smarty']->assign('vote_arr', $vote_arr);

            assign_query_info();
            return $GLOBALS['smarty']->display('vote_info.htm');
        } elseif ($_REQUEST['act'] == 'update') {
            /* 获得广告的开始时期与结束日期 */
            $start_time = local_strtotime($_POST['start_time']);
            $end_time = local_strtotime($_POST['end_time']);

            /* 更新信息 */
            $sql = "UPDATE " . $GLOBALS['ecs']->table('vote') . " SET " .
                "vote_name     = '$_POST[vote_name]', " .
                "start_time    = '$start_time', " .
                "end_time      = '$end_time', " .
                "can_multi     = '$_POST[can_multi]' " .
                "WHERE vote_id = '$_REQUEST[id]'";
            $GLOBALS['db']->query($sql);

            /* 清除缓存 */
            clear_cache_files();

            /* 记录管理员操作 */
            admin_log($_POST['vote_name'], 'edit', 'vote');

            /* 提示信息 */
            $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'vote.php?act=list');
            return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . $_POST['vote_name'] . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }
        /*------------------------------------------------------ */
        //-- 调查选项列表页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'option') {
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 模板赋值 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['list_vote_option']);
            $GLOBALS['smarty']->assign('action_link', array('href' => 'vote.php?act=list', 'text' => $GLOBALS['_LANG']['list_vote']));
            $GLOBALS['smarty']->assign('full_page', 1);

            $GLOBALS['smarty']->assign('id', $id);
            $GLOBALS['smarty']->assign('option_arr', $this->get_optionlist($id));

            /* 显示页面 */
            assign_query_info();
            return $GLOBALS['smarty']->display('vote_option.htm');
        }

        /*------------------------------------------------------ */
        //-- 调查选项查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query_option') {
            $id = intval($_GET['vid']);

            $GLOBALS['smarty']->assign('id', $id);
            $GLOBALS['smarty']->assign('option_arr', $this->get_optionlist($id));

            return make_json_result($GLOBALS['smarty']->fetch('vote_option.htm'));
        }

        /*------------------------------------------------------ */
        //-- 添加新调查选项
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'new_option') {
            check_authz_json('vote_priv');

            $option_name = json_str_iconv(trim($_POST['option_name']));
            $vote_id = intval($_POST['id']);

            if (!empty($option_name)) {
                /* 查看调查标题是否有重复 */
                $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('vote_option') .
                    " WHERE option_name = '$option_name' AND vote_id = '$vote_id'";
                if ($GLOBALS['db']->getOne($sql) != 0) {
                    return make_json_error($GLOBALS['_LANG']['vote_option_exist']);
                } else {
                    $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('vote_option') . ' (vote_id, option_name, option_count) ' .
                        "VALUES ('$vote_id', '$option_name', 0)";
                    $GLOBALS['db']->query($sql);

                    clear_cache_files();
                    admin_log($option_name, 'add', 'vote');

                    $url = 'vote.php?act=query_option&vid=' . $vote_id . '&' . str_replace('act=new_option', '', $_SERVER['QUERY_STRING']);

                    return $this->redirect($url);

                }
            } else {
                return make_json_error($GLOBALS['_LANG']['js_languages']['option_name_empty']);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑调查主题
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_vote_name') {
            check_authz_json('vote_priv');

            $id = intval($_POST['id']);
            $vote_name = json_str_iconv(trim($_POST['val']));

            /* 检查名称是否重复 */
            if ($exc->num("vote_name", $vote_name, $id) != 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['vote_name_exist'], $vote_name));
            } else {
                if ($exc->edit("vote_name = '$vote_name'", $id)) {
                    admin_log($vote_name, 'edit', 'vote');
                    return make_json_result(stripslashes($vote_name));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑调查选项
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_option_name') {
            check_authz_json('vote_priv');

            $id = intval($_POST['id']);
            $option_name = json_str_iconv(trim($_POST['val']));

            /* 检查名称是否重复 */
            $vote_id = $GLOBALS['db']->getOne('SELECT vote_id FROM ' . $GLOBALS['ecs']->table('vote_option') . " WHERE option_id='$id'");

            $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('vote_option') .
                " WHERE option_name = '$option_name' AND vote_id = '$vote_id' AND option_id <> $id";
            if ($GLOBALS['db']->getOne($sql) != 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['vote_option_exist'], $option_name));
            } else {
                if ($exc_opn->edit("option_name = '$option_name'", $id)) {
                    admin_log($option_name, 'edit', 'vote');
                    return make_json_result(stripslashes($option_name));
                }
            }
        }


        /*------------------------------------------------------ */
        //-- 编辑调查选项排序值
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_option_order') {
            check_authz_json('vote_priv');

            $id = intval($_POST['id']);
            $option_order = json_str_iconv(trim($_POST['val']));

            if ($exc_opn->edit("option_order = '$option_order'", $id)) {
                admin_log($GLOBALS['_LANG']['edit_option_order'], 'edit', 'vote');
                return make_json_result(stripslashes($option_order));
            }
        }


        /*------------------------------------------------------ */
        //-- 删除在线调查主题
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            check_authz_json('vote_priv');

            $id = intval($_GET['id']);

            if ($exc->drop($id)) {
                /* 同时删除调查选项 */
                $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('vote_option') . " WHERE vote_id = '$id'");
                clear_cache_files();
                admin_log('', 'remove', 'ads_position');
            }

            $url = 'vote.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return $this->redirect($url);

        }

        /*------------------------------------------------------ */
        //-- 删除在线调查选项
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_option') {
            check_authz_json('vote_priv');

            $id = intval($_GET['id']);
            $vote_id = $GLOBALS['db']->getOne('SELECT vote_id FROM ' . $GLOBALS['ecs']->table('vote_option') . " WHERE option_id='$id'");

            if ($exc_opn->drop($id)) {
                clear_cache_files();
                admin_log('', 'remove', 'vote');
            }

            $url = 'vote.php?act=query_option&vid=' . $vote_id . '&' . str_replace('act=remove_option', '', $_SERVER['QUERY_STRING']);

            return $this->redirect($url);

        }
    }

    /* 获取在线调查数据列表 */
    private function get_votelist()
    {
        $filter = array();

        /* 记录总数以及页数 */
        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('vote');
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 查询数据 */
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('vote') . ' ORDER BY vote_id DESC';
        $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

        $list = array();
        foreach ($res as $rows) {
            $rows['begin_date'] = local_date('Y-m-d', $rows['start_time']);
            $rows['end_date'] = local_date('Y-m-d', $rows['end_time']);
            $list[] = $rows;
        }

        return array('list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    }

    /* 获取调查选项列表 */
    private function get_optionlist($id)
    {
        $list = array();
        $sql = 'SELECT option_id, vote_id, option_name, option_count, option_order' .
            ' FROM ' . $GLOBALS['ecs']->table('vote_option') .
            " WHERE vote_id = '$id' ORDER BY option_order ASC, option_id DESC";
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $rows) {
            $list[] = $rows;
        }

        return $list;
    }
}
