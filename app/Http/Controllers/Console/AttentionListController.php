<?php

namespace app\console\controller;

/**
 * 程序说明
 */
class AttentionList extends Init
{
    public function index()
    {
        admin_priv('attention_list');
        if ($_REQUEST['act'] == 'list') {
            $goodsdb = $this->get_attention();
            $this->assign('full_page', 1);
            $this->assign('ur_here', $GLOBALS['_LANG']['attention_list']);
            $this->assign('goodsdb', $goodsdb['goodsdb']);
            $this->assign('filter', $goodsdb['filter']);
            $this->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->assign('record_count', $goodsdb['record_count']);
            $this->assign('page_count', $goodsdb['page_count']);
            assign_query_info();
            return $this->fetch('attention_list');
        } elseif ($_REQUEST['act'] == 'query') {
            $goodsdb = $this->get_attention();
            $this->assign('goodsdb', $goodsdb['goodsdb']);
            $this->assign('filter', $goodsdb['filter']);
            $this->assign('record_count', $goodsdb['record_count']);
            $this->assign('page_count', $goodsdb['page_count']);
            return make_json_result(
                $GLOBALS['smarty']->fetch('attention_list.htm'),
                '',
                array('filter' => $goodsdb['filter'], 'page_count' => $goodsdb['page_count'])
            );
        } elseif ($_REQUEST['act'] == 'addtolist') {
            $id = intval($_REQUEST['id']);
            $pri = (intval($_REQUEST['pri']) == 1) ? 1 : 0;
            $start = empty($_GET['start']) ? 0 : (int)$_GET['start'];

            $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('goods') . " g"
                . " LEFT JOIN " . $GLOBALS['ecs']->table('collect_goods') . " c"
                . " ON g.goods_id = c.goods_id"
                . " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u"
                . " ON c.user_id = u.user_id" .
                " WHERE c.is_attention = 1 AND g.is_delete = 0 AND c.goods_id = '$id'";

            $count = $GLOBALS['db']->getOne($sql);

            if ($count > $start) {
                $sql = "SELECT u.user_name, u.email, g.goods_name, g.goods_id FROM " . $GLOBALS['ecs']->table('goods') . " g LEFT JOIN " . $GLOBALS['ecs']->table('collect_goods') . " c ON g.goods_id = c.goods_id LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON c.user_id = u.user_id" .
                    " WHERE c.is_attention = 1 AND g.is_delete = 0 AND c.goods_id = '$id' LIMIT $start,100";
                $query = $GLOBALS['db']->query($sql);
                $add = '';
                $template = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('mail_templates') . " WHERE  template_code = 'attention_list' AND type = 'template'");

                $i = 0;
                foreach ($query as $rt) {
                    $time = time();
                    $goods_url = $GLOBALS['ecs']->url() . build_uri('goods', array('gid' => $id), $rt['goods_name']);
                    $this->assign(array('user_name' => $rt['user_name'], 'goods_name' => $rt['goods_name'], 'goods_url' => $goods_url, 'shop_name' => $GLOBALS['_CFG']['shop_title'], 'send_date' => local_date($GLOBALS['_CFG']['date_format'])));
                    $content = $GLOBALS['smarty']->fetch("str:$template[template_content]");
                    $add .= $add ? ",('$rt[email]','$template[template_id]','$content','$pri','$time')" : "('$rt[email]','$template[template_id]','$content','$pri','$time')";
                    $i++;
                }
                if ($add) {
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('email_sendlist') . " (email,template_id,email_content,pri,last_send) VALUES " . $add;
                    $GLOBALS['db']->query($sql);
                }
                if ($i == 100) {
                    $start = $start + 100;
                } else {
                    $start = $start + $i;
                }
                $links[] = array('text' => sprintf($GLOBALS['_LANG']['finish_list'], $start), 'href' => "attention_list.php?act=addtolist&id=$id&pri=$pri&start=$start");
                return sys_msg($GLOBALS['_LANG']['finishing'], 0, $links);
            } else {
                $links[] = array('text' => $GLOBALS['_LANG']['attention_list'], 'href' => 'attention_list.php?act=list');
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        } elseif ($_REQUEST['act'] == 'batch_addtolist') {
            $olddate = $_REQUEST['date'];
            $date = local_strtotime(trim($_REQUEST['date']));
            $pri = (intval($_REQUEST['pri']) == 1) ? 1 : 0;
            $start = empty($_GET['start']) ? 0 : (int)$_GET['start'];

            $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('goods') . " g"
                . " LEFT JOIN " . $GLOBALS['ecs']->table('collect_goods') . " c"
                . " ON g.goods_id = c.goods_id"
                . " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u"
                . " ON c.user_id = u.user_id" .
                " WHERE c.is_attention = 1 AND g.is_delete = 0 AND g.last_update >= '$date'";

            $count = $GLOBALS['db']->getOne($sql);

            if ($count > $start) {
                $sql = "SELECT u.user_name, u.email, g.goods_name, g.goods_id FROM " . $GLOBALS['ecs']->table('goods') . " g LEFT JOIN " . $GLOBALS['ecs']->table('collect_goods') . " c ON g.goods_id = c.goods_id LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON c.user_id = u.user_id" .
                    " WHERE c.is_attention = 1 AND g.is_delete = 0 AND g.last_update >= '$date' LIMIT $start,100";
                $query = $GLOBALS['db']->query($sql);
                $add = '';

                $template = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('mail_templates') . " WHERE  template_code = 'attention_list' AND type = 'template'");

                $i = 0;
                foreach ($query as $rt) {
                    $time = time();

                    $goods_url = $GLOBALS['ecs']->url() . build_uri('goods', array('gid' => $rt['goods_id']), $rt['user_name']);

                    $this->assign(array('user_name' => $rt['user_name'], 'goods_name' => $rt['goods_name'], 'goods_url' => $goods_url));
                    $content = $GLOBALS['smarty']->fetch("str:$template[template_content]");
                    $add .= $add ? ",('$rt[email]','$template[template_id]','$content','$pri','$time')" : "('$rt[email]','$template[template_id]','$content','$pri','$time')";
                    $i++;
                }
                if ($add) {
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('email_sendlist') . " (email,template_id,email_content,pri,last_send) VALUES " . $add;
                    $GLOBALS['db']->query($sql);
                }
                if ($i == 100) {
                    $start = $start + 100;
                } else {
                    $start = $start + $i;
                }
                $links[] = array('text' => sprintf($GLOBALS['_LANG']['finish_list'], $start), 'href' => "attention_list.php?act=batch_addtolist&date=$olddate&pri=$pri&start=$start");
                return sys_msg($GLOBALS['_LANG']['finishing'], 0, $links);
            } else {
                $links[] = array('text' => $GLOBALS['_LANG']['attention_list'], 'href' => 'attention_list.php?act=list');
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
    }

    private function get_attention()
    {
        $result = get_filter();

        if ($result === false) {
            $where = 'WHERE c.is_attention = 1 AND g.is_delete = 0 ';
            if (!empty($_POST['goods_name'])) {
                $goods_name = trim($_POST['goods_name']);
                $where .= " AND g.goods_name LIKE '%$goods_name%'";
                $filter['goods_name'] = $goods_name;
            }

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'last_update' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $sql = "SELECT COUNT(DISTINCT c.goods_id) FROM " . $GLOBALS['ecs']->table('collect_goods') . " c " .
                "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " g ON c.goods_id = g.goods_id " .
                $where;

            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT DISTINCT c.goods_id, g.goods_name, g.last_update FROM " . $GLOBALS['ecs']->table('collect_goods') . " c " .
                "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " g ON c.goods_id = g.goods_id " .
                $where .
                " ORDER BY " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ",$filter[page_size]";
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $goodsdb = $GLOBALS['db']->getAll($sql);
        foreach ($goodsdb as $k => $v) {
            $goodsdb[$k]['last_update'] = local_date('Y-m-d', $v['last_update']);
        }

        $arr = array('goodsdb' => $goodsdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
        return $arr;
    }
}
