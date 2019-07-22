<?php

namespace app\console\controller;

/**
 * 程序说明
 */
class MagazineList extends Init
{
    public function index()
    {
        admin_priv('magazine_list');
        if ($_REQUEST['act'] == 'list') {
            $this->assign('ur_here', $GLOBALS['_LANG']['magazine_list']);
            $this->assign('action_link', array('text' => $GLOBALS['_LANG']['add_new'], 'href' => 'magazine_list.php?act=add'));
            $this->assign('full_page', 1);

            $magazinedb = $this->get_magazine();

            $this->assign('magazinedb', $magazinedb['magazinedb']);
            $this->assign('filter', $magazinedb['filter']);
            $this->assign('record_count', $magazinedb['record_count']);
            $this->assign('page_count', $magazinedb['page_count']);

            $special_ranks = get_rank_list();
            $send_rank[SEND_LIST . '_0'] = $GLOBALS['_LANG']['email_user'];
            $send_rank[SEND_USER . '_0'] = $GLOBALS['_LANG']['user_list'];
            foreach ($special_ranks as $rank_key => $rank_value) {
                $send_rank[SEND_RANK . '_' . $rank_key] = $rank_value;
            }
            $this->assign('send_rank', $send_rank);

            assign_query_info();
            return $this->display('magazine_list.view.php');
        } elseif ($_REQUEST['act'] == 'query') {
            $magazinedb = $this->get_magazine();
            $this->assign('magazinedb', $magazinedb['magazinedb']);
            $this->assign('filter', $magazinedb['filter']);
            $this->assign('record_count', $magazinedb['record_count']);
            $this->assign('page_count', $magazinedb['page_count']);

            $sort_flag = sort_flag($magazinedb['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($GLOBALS['smarty']->fetch('magazine_list.htm'), '', array('filter' => $magazinedb['filter'], 'page_count' => $magazinedb['page_count']));
        } elseif ($_REQUEST['act'] == 'add') {
            if (empty($_POST['step'])) {
                include_once(ROOT_PATH . 'includes/fckeditor/fckeditor.php'); // 包含 html editor 类文件
                $this->assign('action_link', array('text' => $GLOBALS['_LANG']['go_list'], 'href' => 'magazine_list.php?act=list'));
                $this->assign(array('ur_here' => $GLOBALS['_LANG']['magazine_list'], 'act' => 'add'));
                create_html_editor('magazine_content');
                assign_query_info();
                return $this->display('magazine_list_add.view.php');
            } elseif ($_POST['step'] == 2) {
                $magazine_name = trim($_POST['magazine_name']);
                $magazine_content = trim($_POST['magazine_content']);
                $magazine_content = str_replace('src=\"', 'src=\"' . defined('FORCE_SSL_ADMIN') ? "https://" : "http://" . $_SERVER['HTTP_HOST'], $magazine_content);
                $time = gmtime();
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('mail_templates') . " (template_code, is_html,template_subject, template_content, last_modify, type) VALUES('" . md5($magazine_name . $time) . "',1, '$magazine_name', '$magazine_content', '$time', 'magazine')";
                $GLOBALS['db']->query($sql);
                $links[] = array('text' => $GLOBALS['_LANG']['magazine_list'], 'href' => 'magazine_list.php?act=list');
                $links[] = array('text' => $GLOBALS['_LANG']['add_new'], 'href' => 'magazine_list.php?act=add');
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        } elseif ($_REQUEST['act'] == 'edit') {
            include_once(ROOT_PATH . 'includes/fckeditor/fckeditor.php'); // 包含 html editor 类文件
            $id = intval($_REQUEST['id']);
            if (empty($_POST['step'])) {
                $rt = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('mail_templates') . " WHERE type = 'magazine' AND template_id = '$id'");
                $this->assign(array('id' => $id, 'act' => 'edit', 'magazine_name' => $rt['template_subject'], 'magazine_content' => $rt['template_content']));
                $this->assign(array('ur_here' => $GLOBALS['_LANG']['magazine_list'], 'act' => 'edit'));
                $this->assign('action_link', array('text' => $GLOBALS['_LANG']['go_list'], 'href' => 'magazine_list.php?act=list'));
                create_html_editor('magazine_content', $rt['template_content']);
                assign_query_info();
                return $this->display('magazine_list_add.view.php');
            } elseif ($_POST['step'] == 2) {
                $magazine_name = trim($_POST['magazine_name']);
                $magazine_content = trim($_POST['magazine_content']);
                $magazine_content = str_replace('src=\"', 'src=\"' . defined('FORCE_SSL_ADMIN') ? "https://" : "http://" . $_SERVER['HTTP_HOST'], $magazine_content);
                $time = gmtime();
                $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('mail_templates') . " SET is_html = 1, template_subject = '$magazine_name', template_content = '$magazine_content', last_modify = '$time' WHERE type = 'magazine' AND template_id = '$id'");
                $links[] = array('text' => $GLOBALS['_LANG']['magazine_list'], 'href' => 'magazine_list.php?act=list');
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        } elseif ($_REQUEST['act'] == 'del') {
            $id = intval($_REQUEST['id']);
            $GLOBALS['db']->query("DELETE  FROM " . $GLOBALS['ecs']->table('mail_templates') . " WHERE type = 'magazine' AND template_id = '$id' LIMIT 1");
            $links[] = array('text' => $GLOBALS['_LANG']['magazine_list'], 'href' => 'magazine_list.php?act=list');
            return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
        } elseif ($_REQUEST['act'] == 'addtolist') {
            $id = intval($_REQUEST['id']);
            $pri = !empty($_REQUEST['pri']) ? 1 : 0;
            $start = empty($_GET['start']) ? 0 : (int)$_GET['start'];
            $send_rank = $_REQUEST['send_rank'];
            $rank_array = explode('_', $send_rank);
            $template_id = $GLOBALS['db']->getOne("SELECT template_id FROM " . $GLOBALS['ecs']->table('mail_templates') . " WHERE type = 'magazine' AND template_id = '$id'");
            if (!empty($template_id)) {
                if (SEND_LIST == $rank_array['0']) {
                    $count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('email_list') . "WHERE stat = 1");
                    if ($count > $start) {
                        $sql = "SELECT email FROM " . $GLOBALS['ecs']->table('email_list') . "WHERE stat = 1 LIMIT $start,100";
                        $query = $GLOBALS['db']->query($sql);
                        $add = '';

                        $i = 0;
                        foreach ($query as $rt) {
                            $time = time();
                            $add .= $add ? ",('$rt[email]','$id','$pri','$time')" : "('$rt[email]','$id','$pri','$time')";
                            $i++;
                        }
                        if ($add) {
                            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('email_sendlist') . " (email,template_id,pri,last_send) VALUES " . $add;
                            $GLOBALS['db']->query($sql);
                        }
                        if ($i == 100) {
                            $start = $start + 100;
                        } else {
                            $start = $start + $i;
                        }
                        $links[] = array('text' => sprintf($GLOBALS['_LANG']['finish_list'], $start), 'href' => "magazine_list.php?act=addtolist&id=$id&pri=$pri&start=$start&send_rank=$send_rank");
                        return sys_msg($GLOBALS['_LANG']['finishing'], 0, $links);
                    } else {
                        $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('mail_templates') . " SET last_send = " . time() . " WHERE type = 'magazine' AND template_id = '$id'");
                        $links[] = array('text' => $GLOBALS['_LANG']['magazine_list'], 'href' => 'magazine_list.php?act=list');
                        return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
                    }
                } else {
                    $sql = "SELECT special_rank FROM " . $GLOBALS['ecs']->table('user_rank') . " WHERE rank_id = '" . $rank_array['1'] . "'";
                    $row = $GLOBALS['db']->getRow($sql);
                    if (SEND_USER == $rank_array['0']) {
                        $count_sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') . 'WHERE is_validated = 1';
                        $email_sql = 'SELECT email FROM ' . $GLOBALS['ecs']->table('users') . "WHERE is_validated = 1 LIMIT $start,100";
                    } elseif ($row['special_rank']) {
                        $count_sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') . 'WHERE is_validated = 1 AND user_rank = ' . $rank_array['1'];
                        $email_sql = 'SELECT email FROM ' . $GLOBALS['ecs']->table('users') . 'WHERE is_validated = 1 AND user_rank = ' . $rank_array['1'] . " LIMIT $start,100";
                    } else {
                        $count_sql = 'SELECT COUNT(*) ' .
                            'FROM ' . $GLOBALS['ecs']->table('users') . ' AS u LEFT JOIN ' . $GLOBALS['ecs']->table('user_rank') . ' AS ur ' .
                            "  ON ur.special_rank = '0' AND ur.min_points <= u.rank_points AND ur.max_points > u.rank_points" .
                            " WHERE ur.rank_id = '" . $rank_array['1'] . "' AND u.is_validated = 1";
                        $email_sql = 'SELECT u.email ' .
                            'FROM ' . $GLOBALS['ecs']->table('users') . ' AS u LEFT JOIN ' . $GLOBALS['ecs']->table('user_rank') . ' AS ur ' .
                            "  ON ur.special_rank = '0' AND ur.min_points <= u.rank_points AND ur.max_points > u.rank_points" .
                            " WHERE ur.rank_id = '" . $rank_array['1'] . "' AND u.is_validated = 1 LIMIT $start,100";
                    }

                    $count = $GLOBALS['db']->getOne($count_sql);
                    if ($count > $start) {
                        $query = $GLOBALS['db']->query($email_sql);
                        $add = '';

                        $i = 0;
                        foreach ($query as $rt) {
                            $time = time();
                            $add .= $add ? ",('$rt[email]','$id','$pri','$time')" : "('$rt[email]','$id','$pri','$time')";
                            $i++;
                        }
                        if ($add) {
                            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('email_sendlist') . " (email,template_id,pri,last_send) VALUES " . $add;
                            $GLOBALS['db']->query($sql);
                        }
                        if ($i == 100) {
                            $start = $start + 100;
                        } else {
                            $start = $start + $i;
                        }
                        $links[] = array('text' => sprintf($GLOBALS['_LANG']['finish_list'], $start), 'href' => "magazine_list.php?act=addtolist&id=$id&pri=$pri&start=$start&send_rank=$send_rank");
                        return sys_msg($GLOBALS['_LANG']['finishing'], 0, $links);
                    } else {
                        $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('mail_templates') . " SET last_send = " . time() . " WHERE type = 'magazine' AND template_id = '$id'");
                        $links[] = array('text' => $GLOBALS['_LANG']['magazine_list'], 'href' => 'magazine_list.php?act=list');
                        return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
                    }
                }
            } else {
                $links[] = array('text' => $GLOBALS['_LANG']['magazine_list'], 'href' => 'magazine_list.php?act=list');
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
    }

    private function get_magazine()
    {
        $result = get_filter();

        if ($result === false) {
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'template_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('mail_templates') . " WHERE type = 'magazine'";
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT * " .
                " FROM " . $GLOBALS['ecs']->table('mail_templates') .
                " WHERE type = 'magazine'" .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $magazinedb = $GLOBALS['db']->getAll($sql);

        foreach ($magazinedb as $k => $v) {
            $magazinedb[$k]['last_modify'] = local_date('Y-m-d', $v['last_modify']);
            $magazinedb[$k]['last_send'] = local_date('Y-m-d', $v['last_send']);
        }

        $arr = array('magazinedb' => $magazinedb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
    }
}
