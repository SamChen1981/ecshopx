<?php

namespace app\console\controller;

/**
 * 程序说明
 */
class GoodsAuto extends Init
{
    public function index()
    {
        admin_priv('goods_auto');
        $this->assign('thisfile', 'goods_auto.php');
        if ($_REQUEST['act'] == 'list') {
            $goodsdb = $this->get_auto_goods();
            $crons_enable = $GLOBALS['db']->getOne("SELECT enable FROM " . $GLOBALS['ecs']->table('crons') . " WHERE cron_code='auto_manage'");
            $this->assign('crons_enable', $crons_enable);
            $this->assign('full_page', 1);
            $this->assign('ur_here', $GLOBALS['_LANG']['goods_auto']);
            $this->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->assign('goodsdb', $goodsdb['goodsdb']);
            $this->assign('filter', $goodsdb['filter']);
            $this->assign('record_count', $goodsdb['record_count']);
            $this->assign('page_count', $goodsdb['page_count']);
            assign_query_info();
            return $this->fetch('goods_auto');
        } elseif ($_REQUEST['act'] == 'query') {
            $goodsdb = $this->get_auto_goods();
            $this->assign('goodsdb', $goodsdb['goodsdb']);
            $this->assign('filter', $goodsdb['filter']);
            $this->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->assign('record_count', $goodsdb['record_count']);
            $this->assign('page_count', $goodsdb['page_count']);

            $sort_flag = sort_flag($goodsdb['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($GLOBALS['smarty']->fetch('goods_auto.htm'), '', array('filter' => $goodsdb['filter'], 'page_count' => $goodsdb['page_count']));
        } elseif ($_REQUEST['act'] == 'del') {
            $goods_id = (int)$_REQUEST['goods_id'];
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('auto_manage') . " WHERE item_id = '$goods_id' AND type = 'goods'";
            $GLOBALS['db']->query($sql);
            $links[] = array('text' => $GLOBALS['_LANG']['goods_auto'], 'href' => 'goods_auto.php?act=list');
            return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
        } elseif ($_REQUEST['act'] == 'edit_starttime') {
            check_authz_json('goods_auto');

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_POST['val']))) {
                return make_json_error('');
            }

            $id = intval($_POST['id']);
            $time = local_strtotime(trim($_POST['val']));
            if ($id <= 0 || $_POST['val'] == '0000-00-00' || $time <= 0) {
                return make_json_error('');
            }

            $GLOBALS['db']->autoReplace($GLOBALS['ecs']->table('auto_manage'), array('item_id' => $id, 'type' => 'goods',
                'starttime' => $time), array('starttime' => (string)$time));

            clear_cache_files();
            return make_json_result(stripslashes($_POST['val']), '', array('act' => 'goods_auto', 'id' => $id));
        } elseif ($_REQUEST['act'] == 'edit_endtime') {
            check_authz_json('goods_auto');

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_POST['val']))) {
                return make_json_error('');
            }

            $id = intval($_POST['id']);
            $time = local_strtotime(trim($_POST['val']));
            if ($id <= 0 || $_POST['val'] == '0000-00-00' || $time <= 0) {
                return make_json_error('');
            }

            $GLOBALS['db']->autoReplace($GLOBALS['ecs']->table('auto_manage'), array('item_id' => $id, 'type' => 'goods',
                'endtime' => $time), array('endtime' => (string)$time));

            clear_cache_files();
            return make_json_result(stripslashes($_POST['val']), '', array('act' => 'goods_auto', 'id' => $id));
        } //批量上架
        elseif ($_REQUEST['act'] == 'batch_start') {
            admin_priv('goods_auto');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_select_goods'], 1);
            }

            if ($_POST['date'] == '0000-00-00') {
                $_POST['date'] = 0;
            } else {
                $_POST['date'] = local_strtotime(trim($_POST['date']));
            }

            foreach ($_POST['checkboxes'] as $id) {
                $GLOBALS['db']->autoReplace($GLOBALS['ecs']->table('auto_manage'), array('item_id' => $id, 'type' => 'goods',
                    'starttime' => $_POST['date']), array('starttime' => (string)$_POST['date']));
            }

            $lnk[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_auto.php?act=list');
            return sys_msg($GLOBALS['_LANG']['batch_start_succeed'], 0, $lnk);
        } //批量下架
        elseif ($_REQUEST['act'] == 'batch_end') {
            admin_priv('goods_auto');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_select_goods'], 1);
            }

            if ($_POST['date'] == '0000-00-00') {
                $_POST['date'] = 0;
            } else {
                $_POST['date'] = local_strtotime(trim($_POST['date']));
            }

            foreach ($_POST['checkboxes'] as $id) {
                $GLOBALS['db']->autoReplace($GLOBALS['ecs']->table('auto_manage'), array('item_id' => $id, 'type' => 'goods',
                    'endtime' => $_POST['date']), array('endtime' => (string)$_POST['date']));
            }

            $lnk[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'goods_auto.php?act=list');
            return sys_msg($GLOBALS['_LANG']['batch_end_succeed'], 0, $lnk);
        }
    }

    private function get_auto_goods()
    {
        $where = ' WHERE g.is_delete <> 1 ';
        if (!empty($_POST['goods_name'])) {
            $goods_name = trim($_POST['goods_name']);
            $where .= " AND g.goods_name LIKE '%$goods_name%'";
            $filter['goods_name'] = $goods_name;
        }

        $result = get_filter();

        if ($result === false) {
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'last_update' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " g" . $where;
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT g.*,a.starttime,a.endtime FROM " . $GLOBALS['ecs']->table('goods') . " g LEFT JOIN " . $GLOBALS['ecs']->table('auto_manage') . " a ON g.goods_id = a.item_id AND a.type='goods'" . $where .
                " ORDER by goods_id, " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ",$filter[page_size]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $query = $GLOBALS['db']->query($sql);

        $goodsdb = array();

        foreach ($query as $rt) {
            if (!empty($rt['starttime'])) {
                $rt['starttime'] = local_date('Y-m-d', $rt['starttime']);
            }
            if (!empty($rt['endtime'])) {
                $rt['endtime'] = local_date('Y-m-d', $rt['endtime']);
            }
            $goodsdb[] = $rt;
        }

        $arr = array('goodsdb' => $goodsdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
    }
}
