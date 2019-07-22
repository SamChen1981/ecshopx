<?php

namespace app\console\controller;

/**
 * 综合流量统计
 */
class FlowStats extends Init
{
    public function index()
    {
        load_lang('admin/statistic');
        $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);

        global $timezone;

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'view';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        if ($_REQUEST['act'] == 'view') {
            if ($GLOBALS['_CFG']['visit_stats'] == 'off') {
                sys_msg($GLOBALS['_LANG']['stats_off']);
            }
            admin_priv('client_flow_stats');
            $is_multi = empty($_POST['is_multi']) ? false : true;

            /* 时间参数 */
            if (isset($_POST['start_date']) && !empty($_POST['end_date'])) {
                $start_date = local_strtotime($_POST['start_date']);
                $end_date = local_strtotime($_POST['end_date']);
            } else {
                $today = local_strtotime(local_date('Y-m-d'));
                $start_date = $today - 86400 * 7;
                $end_date = $today;
            }

            $start_date_arr = array();
            $end_date_arr = array();
            if (!empty($_POST['year_month'])) {
                $tmp = $_POST['year_month'];

                for ($i = 0; $i < count($tmp); $i++) {
                    if (!empty($tmp[$i])) {
                        $tmp_time = local_strtotime($tmp[$i] . '-1');
                        $start_date_arr[] = $tmp_time;
                        $end_date_arr[] = local_strtotime($tmp[$i] . '-' . date('t', $tmp_time));
                    }
                }
            } else {
                $tmp_time = local_strtotime(local_date('Y-m-d'));
                $start_date_arr[] = local_strtotime(local_date('Y-m') . '-1');
                $end_date_arr[] = local_strtotime(local_date('Y-m') . '-31');
            }

            /* ------------------------------------- */
            /* --综合流量
            /* ------------------------------------- */
            $max = 0;

            if (!$is_multi) {
                $general_xml = "<graph caption='{$GLOBALS['_LANG']['general_stats']}' shownames='1' showvalues='1' decimalPrecision='0' yaxisminvalue='0' yaxismaxvalue='%d' animation='1' outCnvBaseFontSize='12' baseFontSize='12' xaxisname='{$GLOBALS['_LANG']['date']}' yaxisname='{$GLOBALS['_LANG']['access_count']}' >";

                $sql = "SELECT FLOOR((access_time - $start_date) / (24 * 3600)) AS sn, access_time, COUNT(*) AS access_count" .
                    " FROM " . $GLOBALS['ecs']->table('stats') .
                    " WHERE access_time >= '$start_date' AND access_time <= " . ($end_date + 86400) .
                    " GROUP BY sn";
                $res = $GLOBALS['db']->query($sql);

                $key = 0;

                foreach ($res as $val) {
                    $val['access_date'] = gmdate('m-d', $val['access_time'] + intval($timezone) * 3600);
                    $general_xml .= "<set name='$val[access_date]' value='$val[access_count]' color='" . chart_color($key) . "' />";
                    if ($val['access_count'] > $max) {
                        $max = $val['access_count'];
                    }
                    $key++;
                }

                $general_xml .= '</graph>';
                $general_xml = sprintf($general_xml, $max);
            } else {
                $general_xml = "<graph caption='{$GLOBALS['_LANG']['general_stats']}' lineThickness='1' showValues='0' formatNumberScale='0' anchorRadius='2'   divLineAlpha='20' divLineColor='CC3300' divLineIsDashed='1' showAlternateHGridColor='1' alternateHGridAlpha='5' alternateHGridColor='CC3300' shadowAlpha='40' labelStep='2' numvdivlines='5' chartRightMargin='35' bgColor='FFFFFF,CC3300' bgAngle='270' bgAlpha='10,10' outCnvBaseFontSize='12' baseFontSize='12' >";
                foreach ($start_date_arr as $k => $val) {
                    $seriesName = local_date('Y-m', $start_date_arr[$k]);
                    $general_xml .= "<dataset seriesName='$seriesName' color='" . chart_color($k) . "' anchorBorderColor='" . chart_color($k) . "' anchorBgColor='" . chart_color($k) . "'>";
                    $sql = "SELECT FLOOR((access_time - $start_date_arr[$k]) / (24 * 3600)) AS sn, access_time, COUNT(*) AS access_count" .
                        " FROM " . $GLOBALS['ecs']->table('stats') .
                        " WHERE access_time >= '$start_date_arr[$k]' AND access_time <= " . ($end_date_arr[$k] + 86400) .
                        " GROUP BY sn";
                    $res = $GLOBALS['db']->query($sql);

                    $lastDay = 0;

                    foreach ($res as $val) {
                        $day = gmdate('d', $val['access_time'] + $timezone * 3600);

                        if ($lastDay == 0) {
                            $time_span = (($day - 1) - $lastDay);
                            $lastDay++;
                            for (; $lastDay < $day; $lastDay++) {
                                $general_xml .= "<set value='0' />";
                            }
                        }
                        $general_xml .= "<set value='$val[access_count]' />";
                        $lastDay = $day;
                    }

                    $general_xml .= "</dataset>";
                }

                $general_xml .= "<categories>";

                for ($i = 1; $i <= 31; $i++) {
                    $general_xml .= "<category label='$i' />";
                }
                $general_xml .= "</categories>";
                $general_xml .= "</graph>";
            }
            /* ------------------------------------- */
            /* --地域分布
            /* ------------------------------------- */
            $area_xml = '';

            if (!$is_multi) {
                $area_xml .= "<graph caption='" . $GLOBALS['_LANG']['area_stats'] . "' shownames='1' showvalues='1' decimalPrecision='2' outCnvBaseFontSize='13' baseFontSize='13' pieYScale='45'  pieBorderAlpha='40' pieFillAlpha='70' pieSliceDepth='15' pieRadius='100' bgAngle='460'>";

                $sql = "SELECT COUNT(*) AS access_count, area FROM " . $GLOBALS['ecs']->table('stats') .
                    " WHERE access_time >= '$start_date' AND access_time < " . ($end_date + 86400) .
                    " GROUP BY area ORDER BY access_count DESC LIMIT 20";
                $res = $GLOBALS['db']->query($sql);

                $key = 0;
                foreach ($res as $val) {
                    $area = empty($val['area']) ? 'unknow' : $val['area'];

                    $area_xml .= "<set name='$area' value='$val[access_count]' color='" . chart_color($key) . "' />";
                    $key++;
                }
                $area_xml .= '</graph>';
            } else {
                $where = '';
                foreach ($start_date_arr as $k => $val) {
                    if ($where != '') {
                        $where .= ' or ';
                    }
                    $where .= "(access_time >= '$start_date_arr[$k]' AND access_time <= " . ($end_date_arr[$k] + 86400) . ")";
                }
                $sql = "SELECT access_time, area FROM " . $GLOBALS['ecs']->table('stats') .
                    " WHERE $where";
                $res = $GLOBALS['db']->query($sql);
                $area_arr = array();
                $category = array();
                foreach ($res as $val) {
                    $date = local_date('Y-m', $val['access_time']);
                    $area_arr[$val['area']] = null;
                    if (isset($category[$date][$val['area']])) {
                        $category[$date][$val['area']]++;
                    } else {
                        $category[$date][$val['area']] = 1;
                    }
                }
                $area_xml = "<chart palette='2' caption='{$GLOBALS['_LANG']['area_stats']}' shownames='1' showvalues='0' numberPrefix='' useRoundEdges='1' legendBorderAlpha='0' outCnvBaseFontSize='13' baseFontSize='13'>";
                $area_xml .= "<categories>";
                foreach ($area_arr as $k => $v) {
                    $area_xml .= "<category label='$k'/>";
                }
                $area_xml .= "</categories>";
                $key = 0;
                foreach ($start_date_arr as $val) {
                    $key++;
                    $date = local_date('Y-m', $val);
                    $area_xml .= "<dataset seriesName='$date' color='" . chart_color($key) . "' showValues='0'>";

                    foreach ($area_arr as $k => $v) {
                        if (isset($category[$date][$k])) {
                            $area_xml .= "<set value='" . $category[$date][$k] . "'/>";
                        } else {
                            $area_xml .= "<set value='0'/>";
                        }
                    }
                    $area_xml .= "</dataset>";
                }
                $area_xml .= "</chart>";
            }

            /* ------------------------------------- */
            /* --来源网站
            /* ------------------------------------- */
            if (!$is_multi) {
                $from_xml = "<graph caption='{$GLOBALS['_LANG']['from_stats']}' shownames='1' showvalues='1' decimalPrecision='2' outCnvBaseFontSize='12' baseFontSize='12' pieYScale='45' pieBorderAlpha='40' pieFillAlpha='70' pieSliceDepth='15' pieRadius='100' bgAngle='460'>";

                $sql = "SELECT COUNT(*) AS access_count, referer_domain FROM " . $GLOBALS['ecs']->table('stats') .
                    " WHERE access_time >= '$start_date' AND access_time <= " . ($end_date + 86400) .
                    " GROUP BY referer_domain ORDER BY access_count DESC LIMIT 20";
                $res = $GLOBALS['db']->query($sql);

                $key = 0;

                foreach ($res as $val) {
                    $from = empty($val['referer_domain']) ? $GLOBALS['_LANG']['input_url'] : $val['referer_domain'];

                    if ((strpos($from, '"') !== false) || (strpos($from, "'") !== false)) {
                        // 会有非域名数据出现，直接过滤掉
                        continue;
                    }
                    $from_xml .= "<set name='" . str_replace(array('http://', 'https://'), array('', ''), $from) . "' value='$val[access_count]' color='" . chart_color($key) . "' />";

                    $key++;
                }

                $from_xml .= '</graph>';
            } else {
                $where = '';
                foreach ($start_date_arr as $k => $val) {
                    if ($where != '') {
                        $where .= ' or ';
                    }
                    $where .= "(access_time >= '$start_date_arr[$k]' AND access_time <= " . ($end_date_arr[$k] + 86400) . ")";
                }

                $sql = "SELECT access_time, referer_domain FROM " . $GLOBALS['ecs']->table('stats') .
                    " WHERE $where";

                $res = $GLOBALS['db']->query($sql);
                $domain_arr = array();
                foreach ($res as $val) {
                    $date = local_date('Y-m', $val['access_time']);
                    $domain_arr[$val['referer_domain']] = null;
                    if (isset($category[$date][$val['referer_domain']])) {
                        $category[$date][$val['referer_domain']]++;
                    } else {
                        $category[$date][$val['referer_domain']] = 1;
                    }
                }
                $from_xml = "<chart palette='2' caption='{$GLOBALS['_LANG']['from_stats']}' shownames='1' showvalues='0' numberPrefix='' useRoundEdges='1' legendBorderAlpha='0' outCnvBaseFontSize='13' baseFontSize='13'>";
                $from_xml .= "<categories>";
                foreach ($domain_arr as $k => $v) {
                    $from = $k == '' ? $GLOBALS['_LANG']['input_url'] : $k;
                    $from_xml .= "<category label='$from'/>";
                }
                $from_xml .= "</categories>";
                $key = 0;
                foreach ($start_date_arr as $val) {
                    $key++;
                    $date = local_date('Y-m', $val);
                    $from_xml .= "<dataset seriesName='$date' color='" . chart_color($key) . "' showValues='0'>";

                    foreach ($domain_arr as $k => $v) {
                        if (isset($category[$date][$k])) {
                            $from_xml .= "<set value='" . $category[$date][$k] . "'/>";
                        } else {
                            $from_xml .= "<set value='0'/>";
                        }
                    }
                    $from_xml .= "</dataset>";
                }
                $from_xml .= "</chart>";
            }

            /* 模板赋值 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['flow_stats']);
            $GLOBALS['smarty']->assign('general_data', $general_xml);
            $GLOBALS['smarty']->assign('area_data', $area_xml);
            $GLOBALS['smarty']->assign('is_multi', $is_multi);
            $GLOBALS['smarty']->assign('from_data', $from_xml);
            /* 显示日期 */

            $GLOBALS['smarty']->assign('start_date', local_date('Y-m-d', $start_date));
            $GLOBALS['smarty']->assign('end_date', local_date('Y-m-d', $end_date));

            for ($i = 0; $i < 5; $i++) {
                if (isset($start_date_arr[$i])) {
                    $start_date_arr[$i] = local_date('Y-m', $start_date_arr[$i]);
                } else {
                    $start_date_arr[$i] = null;
                }
            }
            $GLOBALS['smarty']->assign('start_date_arr', $start_date_arr);

            if (!$is_multi) {
                $filename = gmdate($GLOBALS['_CFG']['date_format'], intval($start_date) + intval($timezone) * 3600) . '_' .
                    gmdate($GLOBALS['_CFG']['date_format'], intval($end_date) + intval($timezone) * 3600);

                $GLOBALS['smarty']->assign('action_link', array('text' => $GLOBALS['_LANG']['down_flow_stats'],
                    'href' => 'flow_stats.php?act=download&filename=' . $filename .
                        '&start_date=' . $start_date . '&end_date=' . $end_date));
            }

            /* 显示页面 */
            assign_query_info();
            $GLOBALS['smarty']->display('flow_stats.htm');
        } /* 报表下载 */
        elseif ($act = 'download') {
            $filename = !empty($_REQUEST['filename']) ? trim($_REQUEST['filename']) : '';

            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=$filename.xls");
            $start_date = empty($_GET['start_date']) ? strtotime('-20 day') : intval($_GET['start_date']);
            $end_date = empty($_GET['end_date']) ? time() : intval($_GET['end_date']);
            $sql = "SELECT FLOOR((access_time - $start_date) / (24 * 3600)) AS sn, access_time, COUNT(*) AS access_count" .
                " FROM " . $GLOBALS['ecs']->table('stats') .
                " WHERE access_time >= '$start_date' AND access_time <= " . ($end_date + 86400) .
                " GROUP BY sn";
            $res = $GLOBALS['db']->query($sql);

            $data = $GLOBALS['_LANG']['general_stats'] . "\t\n";
            $data .= $GLOBALS['_LANG']['date'] . "\t";
            $data .= $GLOBALS['_LANG']['access_count'] . "\t\n";

            foreach ($res as $val) {
                $val['access_date'] = gmdate('m-d', $val['access_time'] + $timezone * 3600);
                $data .= $val['access_date'] . "\t";
                $data .= $val['access_count'] . "\t\n";
            }

            $sql = "SELECT COUNT(*) AS access_count, area FROM " . $GLOBALS['ecs']->table('stats') .
                " WHERE access_time >= '$start_date' AND access_time <= " . ($end_date + 86400) .
                " GROUP BY area ORDER BY access_count DESC LIMIT 20";

            $res = $GLOBALS['db']->query($sql);

            $data .= $GLOBALS['_LANG']['area_stats'] . "\t\n";
            $data .= $GLOBALS['_LANG']['area'] . "\t";
            $data .= $GLOBALS['_LANG']['access_count'] . "\t\n";

            foreach ($res as $val) {
                $data .= $val['area'] . "\t";
                $data .= $val['access_count'] . "\t\n";
            }

            $sql = "SELECT COUNT(*) AS access_count, referer_domain FROM " . $GLOBALS['ecs']->table('stats') .
                " WHERE access_time >= '$start_date' AND access_time <= " . ($end_date + 86400) .
                " GROUP BY referer_domain ORDER BY access_count DESC LIMIT 20";

            $res = $GLOBALS['db']->query($sql);

            $data .= "\n" . $GLOBALS['_LANG']['from_stats'] . "\t\n";

            $data .= $GLOBALS['_LANG']['url'] . "\t";
            $data .= $GLOBALS['_LANG']['access_count'] . "\t\n";

            foreach ($res as $val) {
                $data .= ($val['referer_domain'] == "" ? $GLOBALS['_LANG']['input_url'] : $val['referer_domain']) . "\t";
                $data .= $val['access_count'] . "\t\n";
            }
            if (EC_CHARSET != 'gbk') {
                echo ecs_iconv(EC_CHARSET, 'gbk', $data) . "\t";
            } else {
                echo $data . "\t";
            }
        }
    }
}
