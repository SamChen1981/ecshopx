<?php

namespace app\console\controller;

/**
 *  管理中心管理员留言程序
 */
class Message extends Init
{
    public function index()
    {


        /* act操作项的初始化 */
        $_REQUEST['act'] = trim($_REQUEST['act']);
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        }

        /*------------------------------------------------------ */
        //-- 留言列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->assign('full_page', 1);
            $this->assign('ur_here', $GLOBALS['_LANG']['msg_list']);
            $this->assign('action_link', array('text' => $GLOBALS['_LANG']['send_msg'], 'href' => 'message.php?act=send'));

            $list = $this->get_message_list();

            $this->assign('message_list', $list['item']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            assign_query_info();
            return $this->fetch('message_list');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->get_message_list();

            $this->assign('message_list', $list['item']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->fetch('message_list.htm'),
                '',
                array('filter' => $list['filter'], 'page_count' => $list['page_count'])
            );
        }

        /*------------------------------------------------------ */
        //-- 留言发送页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'send') {
            /* 获取管理员列表 */
            $admin_list = $GLOBALS['db']->getAll('SELECT user_id, user_name FROM ' . $GLOBALS['ecs']->table('admin_user'));

            $this->assign('ur_here', $GLOBALS['_LANG']['send_msg']);
            $this->assign('action_link', array('href' => 'message.php?act=list', 'text' => $GLOBALS['_LANG']['msg_list']));
            $this->assign('action', 'add');
            $this->assign('form_act', 'insert');
            $this->assign('admin_list', $admin_list);

            assign_query_info();
            return $this->fetch('message_info');
        }

        /*------------------------------------------------------ */
        //-- 处理留言的发送
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            $rec_arr = $_POST['receiver_id'];

            /* 向所有管理员发送留言 */
            if ($rec_arr[0] == 0) {
                /* 获取管理员信息 */
                $result = $GLOBALS['db']->query('SELECT user_id FROM ' . $GLOBALS['ecs']->table('admin_user') . 'WHERE user_id !=' . $_SESSION['admin_id']);
                foreach ($result as $rows) {
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('admin_message') . " (sender_id, receiver_id, sent_time, " .
                        "read_time, readed, deleted, title, message) " .
                        "VALUES ('" . $_SESSION['admin_id'] . "', '" . $rows['user_id'] . "', '" . gmtime() . "', " .
                        "0, '0', '0', '$_POST[title]', '$_POST[message]')";
                    $GLOBALS['db']->query($sql);
                }

                /*添加链接*/
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'message.php?act=list';

                $link[1]['text'] = $GLOBALS['_LANG']['continue_send_msg'];
                $link[1]['href'] = 'message.php?act=send';

                return sys_msg($GLOBALS['_LANG']['send_msg'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);

                /* 记录管理员操作 */
                admin_log(admin_log($GLOBALS['_LANG']['send_msg']), 'add', 'admin_message');
            } else {
                /* 如果是发送给指定的管理员 */
                foreach ($rec_arr as $key => $id) {
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('admin_message') . " (sender_id, receiver_id, " .
                        "sent_time, read_time, readed, deleted, title, message) " .
                        "VALUES ('" . $_SESSION['admin_id'] . "', '$id', '" . gmtime() . "', " .
                        "'0', '0', '0', '$_POST[title]', '$_POST[message]')";
                    $GLOBALS['db']->query($sql);
                }
                admin_log(addslashes($GLOBALS['_LANG']['send_msg']), 'add', 'admin_message');

                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'message.php?act=list';
                $link[1]['text'] = $GLOBALS['_LANG']['continue_send_msg'];
                $link[1]['href'] = 'message.php?act=send';

                return sys_msg($GLOBALS['_LANG']['send_msg'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
            }
        }
        /*------------------------------------------------------ */
        //-- 留言编辑页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            $id = intval($_REQUEST['id']);

            /* 获取管理员列表 */
            $admin_list = $GLOBALS['db']->getAll('SELECT user_id, user_name FROM ' . $GLOBALS['ecs']->table('admin_user'));

            /* 获得留言数据*/
            $sql = 'SELECT message_id, receiver_id, title, message' .
                'FROM ' . $GLOBALS['ecs']->table('admin_message') . " WHERE message_id='$id'";
            $msg_arr = $GLOBALS['db']->getRow($sql);

            $this->assign('ur_here', $GLOBALS['_LANG']['edit_msg']);
            $this->assign('action_link', array('href' => 'message.php?act=list', 'text' => $GLOBALS['_LANG']['msg_list']));
            $this->assign('form_act', 'update');
            $this->assign('admin_list', $admin_list);
            $this->assign('msg_arr', $msg_arr);

            assign_query_info();
            return $this->fetch('message_info');
        } elseif ($_REQUEST['act'] == 'update') {
            /* 获得留言数据*/
            $msg_arr = array();
            $msg_arr = $GLOBALS['db']->getRow('SELECT * FROM ' . $GLOBALS['ecs']->table('admin_message') . " WHERE message_id='$_POST[id]'");

            $sql = "UPDATE " . $GLOBALS['ecs']->table('admin_message') . " SET " .
                "title = '$_POST[title]'," .
                "message = '$_POST[message]'" .
                "WHERE sender_id = '$msg_arr[sender_id]' AND sent_time='$msg_arr[send_time]'";
            $GLOBALS['db']->query($sql);

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'message.php?act=list';

            return sys_msg($GLOBALS['_LANG']['edit_msg'] . ' ' . $GLOBALS['_LANG']['action_succeed'], 0, $link);

            /* 记录管理员操作 */
            admin_log(addslashes($GLOBALS['_LANG']['edit_msg']), 'edit', 'admin_message');
        }

        /*------------------------------------------------------ */
        //-- 留言查看页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'view') {
            $msg_id = intval($_REQUEST['id']);

            /* 获得管理员留言数据 */
            $msg_arr = array();
            $sql = "SELECT a.*, b.user_name " .
                "FROM " . $GLOBALS['ecs']->table('admin_message') . " AS a " .
                "LEFT JOIN " . $GLOBALS['ecs']->table('admin_user') . " AS b ON b.user_id = a.sender_id " .
                "WHERE a.message_id = '$msg_id'";
            $msg_arr = $GLOBALS['db']->getRow($sql);
            $msg_arr['title'] = nl2br(htmlspecialchars($msg_arr['title']));
            $msg_arr['message'] = nl2br(htmlspecialchars($msg_arr['message']));

            /* 如果还未阅读 */
            if ($msg_arr['readed'] == 0) {
                $msg_arr['read_time'] = gmtime(); //阅读日期为当前日期

                //更新阅读日期和阅读状态
                $sql = "UPDATE " . $GLOBALS['ecs']->table('admin_message') . " SET " .
                    "read_time = '" . $msg_arr['read_time'] . "', " .
                    "readed = '1' " .
                    "WHERE message_id = '$msg_id'";
                $GLOBALS['db']->query($sql);
            }

            //模板赋值，显示
            $this->assign('ur_here', $GLOBALS['_LANG']['view_msg']);
            $this->assign('action_link', array('href' => 'message.php?act=list', 'text' => $GLOBALS['_LANG']['msg_list']));
            $this->assign('admin_user', $_SESSION['admin_name']);
            $this->assign('msg_arr', $msg_arr);

            assign_query_info();
            return $this->fetch('message_view');
        }

        /*------------------------------------------------------ */
        //--留言回复页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'reply') {
            $msg_id = intval($_REQUEST['id']);

            /* 获得留言数据 */
            $msg_val = array();
            $sql = "SELECT a.*, b.user_name " .
                "FROM " . $GLOBALS['ecs']->table('admin_message') . " AS a " .
                "LEFT JOIN " . $GLOBALS['ecs']->table('admin_user') . " AS b ON b.user_id = a.sender_id " .
                "WHERE a.message_id = '$msg_id'";
            $msg_val = $GLOBALS['db']->getRow($sql);

            $this->assign('ur_here', $GLOBALS['_LANG']['reply_msg']);
            $this->assign('action_link', array('href' => 'message.php?act=list', 'text' => $GLOBALS['_LANG']['msg_list']));

            $this->assign('action', 'reply');
            $this->assign('form_act', 're_msg');
            $this->assign('msg_val', $msg_val);

            assign_query_info();
            return $this->fetch('message_info');
        }

        /*------------------------------------------------------ */
        //--留言回复的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 're_msg') {
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('admin_message') . " (sender_id, receiver_id, sent_time, " .
                "read_time, readed, deleted, title, message) " .
                "VALUES ('" . $_SESSION['admin_id'] . "', '$_POST[receiver_id]', '" . gmtime() . "', " .
                "0, '0', '0', '$_POST[title]', '$_POST[message]')";
            $GLOBALS['db']->query($sql);

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'message.php?act=list';

            return sys_msg($GLOBALS['_LANG']['send_msg'] . ' ' . $GLOBALS['_LANG']['action_succeed'], 0, $link);

            /* 记录管理员操作 */
            admin_log(addslashes($GLOBALS['_LANG']['send_msg']), 'add', 'admin_message');
        }

        /*------------------------------------------------------ */
        //-- 批量删除留言记录
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_msg') {
            if (isset($_POST['checkboxes'])) {
                $count = 0;
                foreach ($_POST['checkboxes'] as $key => $id) {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('admin_message') . " SET " .
                        "deleted = '1'" .
                        "WHERE message_id = '$id' AND (sender_id='$_SESSION[admin_id]' OR receiver_id='$_SESSION[admin_id]')";
                    $GLOBALS['db']->query($sql);

                    $count++;
                }

                admin_log('', 'remove', 'admin_message');
                $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'message.php?act=list');
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $count), 0, $link);
            } else {
                return sys_msg($GLOBALS['_LANG']['no_select_msg'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除留言
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $id = intval($_GET['id']);

            $sql = "UPDATE " . $GLOBALS['ecs']->table('admin_message') . " SET deleted=1 " .
                " WHERE message_id=$id AND (sender_id='$_SESSION[admin_id]' OR receiver_id='$_SESSION[admin_id]')";
            $GLOBALS['db']->query($sql);

            $url = 'message.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return $this->redirect($url);

        }
    }

    /**
     *  获取管理员留言列表
     *
     * @return void
     */
    private function get_message_list()
    {
        /* 查询条件 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'sent_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['msg_type'] = empty($_REQUEST['msg_type']) ? 0 : intval($_REQUEST['msg_type']);

        /* 查询条件 */
        switch ($filter['msg_type']) {
            case 1:
                $where = " a.receiver_id='" . $_SESSION['admin_id'] . "'";
                break;
            case 2:
                $where = " a.sender_id='" . $_SESSION['admin_id'] . "' AND a.deleted='0'";
                break;
            case 3:
                $where = " a.readed='0' AND a.receiver_id='" . $_SESSION['admin_id'] . "' AND a.deleted='0'";
                break;
            case 4:
                $where = " a.readed='1' AND a.receiver_id='" . $_SESSION['admin_id'] . "' AND a.deleted='0'";
                break;
            default:
                $where = " (a.receiver_id='" . $_SESSION['admin_id'] . "' OR a.sender_id='" . $_SESSION['admin_id'] . "') AND a.deleted='0'";
        }

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('admin_message') . " AS a WHERE 1 AND " . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT a.message_id,a.sender_id,a.receiver_id,a.sent_time,a.read_time,a.deleted,a.title,a.message,b.user_name" .
            " FROM " . $GLOBALS['ecs']->table('admin_message') . " AS a," . $GLOBALS['ecs']->table('admin_user') . " AS b " .
            " WHERE a.sender_id=b.user_id AND $where " .
            " ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'] .
            " LIMIT " . $filter['start'] . ", $filter[page_size]";
        $row = $GLOBALS['db']->getAll($sql);

        foreach ($row as $key => $val) {
            $row[$key]['sent_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['sent_time']);
            $row[$key]['read_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['read_time']);
        }

        $arr = array('item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
    }
}
