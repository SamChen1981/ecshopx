<?php

namespace app\console\controller;

/**
 * 程序说明
 */
class SearchLog extends Init
{
    public function index()
    {
        $_REQUEST['act'] = trim($_REQUEST['act']);

        admin_priv('search_log');
        if ($_REQUEST['act'] == 'list') {
            $logdb = $this->get_search_log();
            $this->assign('ur_here', $GLOBALS['_LANG']['search_log']);
            $this->assign('full_page', 1);
            $this->assign('logdb', $logdb['logdb']);
            $this->assign('filter', $logdb['filter']);
            $this->assign('record_count', $logdb['record_count']);
            $this->assign('page_count', $logdb['page_count']);
            $this->assign('start_date', local_date('Y-m-d'));
            $this->assign('end_date', local_date('Y-m-d'));
            assign_query_info();
            return $this->fetch('search_log_list');
        } elseif ($_REQUEST['act'] == 'query') {
            $logdb = $this->get_search_log();
            $this->assign('full_page', 0);
            $this->assign('logdb', $logdb['logdb']);
            $this->assign('filter', $logdb['filter']);
            $this->assign('record_count', $logdb['record_count']);
            $this->assign('page_count', $logdb['page_count']);
            $this->assign('start_date', local_date('Y-m-d'));
            $this->assign('end_date', local_date('Y-m-d'));
            return make_json_result(
                $GLOBALS['smarty']->fetch('search_log_list.htm'),
                '',
                array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count'])
            );
        }
    }

    private function get_search_log()
    {
        $where = '';
        if (isset($_REQUEST['start_dateYear']) && isset($_REQUEST['end_dateYear'])) {
            $start_date = $_POST['start_dateYear'] . '-' . $_POST['start_dateMonth'] . '-' . $_POST['start_dateDay'];
            $end_date = $_POST['end_dateYear'] . '-' . $_POST['end_dateMonth'] . '-' . $_POST['end_dateDay'];
            $where .= " AND date <= '$end_date' AND date >= '$start_date'";
            $filter['start_dateYear'] = $_REQUEST['start_dateYear'];
            $filter['start_dateMonth'] = $_REQUEST['start_dateMonth'];
            $filter['start_dateDay'] = $_REQUEST['start_dateDay'];

            $filter['end_dateYear'] = $_REQUEST['end_dateYear'];
            $filter['end_dateMonth'] = $_REQUEST['end_dateMonth'];
            $filter['end_dateDay'] = $_REQUEST['end_dateDay'];
        }

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('keywords') . " WHERE  searchengine='ecshop' $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $logdb = array();
        $filter = page_and_size($filter);
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('keywords') .
            " WHERE  searchengine='ecshop' $where" .
            " ORDER BY date DESC, count DESC" .
            "  LIMIT $filter[start],$filter[page_size]";
        $query = $GLOBALS['db']->query($sql);

        foreach ($query as $rt) {
            $logdb[] = $rt;
        }
        $arr = array('logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
    }
}
