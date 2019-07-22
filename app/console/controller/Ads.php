<?php

namespace app\console\controller;

use app\common\libraries\Exchange;
use app\common\libraries\Image;

/**
 * 广告管理程序
 */
class Ads extends Init
{
    public function index()
    {
        $image = new Image($GLOBALS['_CFG']['bgcolor']);
        $exc = new Exchange($GLOBALS['ecs']->table("ad"), $db, 'ad_id', 'ad_name');
        $allow_suffix = array('gif', 'jpg', 'png', 'jpeg', 'bmp', 'swf');
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 广告列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('ad_manage');
            $pid = !empty($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['ad_list']);
            $GLOBALS['smarty']->assign('action_link', array('text' => $GLOBALS['_LANG']['ads_add'], 'href' => 'ads.php?act=add'));
            $GLOBALS['smarty']->assign('pid', $pid);
            $GLOBALS['smarty']->assign('full_page', 1);

            $ads_list = $this->get_adslist();

            $GLOBALS['smarty']->assign('ads_list', $ads_list['ads']);
            $GLOBALS['smarty']->assign('filter', $ads_list['filter']);
            $GLOBALS['smarty']->assign('record_count', $ads_list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $ads_list['page_count']);

            $sort_flag = sort_flag($ads_list['filter']);
            $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);

            assign_query_info();
            $GLOBALS['smarty']->display('ads_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $ads_list = $this->get_adslist();

            $GLOBALS['smarty']->assign('ads_list', $ads_list['ads']);
            $GLOBALS['smarty']->assign('filter', $ads_list['filter']);
            $GLOBALS['smarty']->assign('record_count', $ads_list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $ads_list['page_count']);

            $sort_flag = sort_flag($ads_list['filter']);
            $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);

            make_json_result(
                $GLOBALS['smarty']->fetch('ads_list.htm'),
                '',
                array('filter' => $ads_list['filter'], 'page_count' => $ads_list['page_count'])
            );
        }

        /*------------------------------------------------------ */
        //-- 添加新广告页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            admin_priv('ad_manage');

            $ad_link = empty($_GET['ad_link']) ? '' : trim($_GET['ad_link']);
            $ad_name = empty($_GET['ad_name']) ? '' : trim($_GET['ad_name']);

            $start_time = local_date('Y-m-d');
            $end_time = local_date('Y-m-d', gmtime() + 3600 * 24 * 30);  // 默认结束时间为1个月以后

            $GLOBALS['smarty']->assign(
                'ads',
                array('ad_link' => $ad_link, 'ad_name' => $ad_name, 'start_time' => $start_time,
                    'end_time' => $end_time, 'enabled' => 1)
            );

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['ads_add']);
            $GLOBALS['smarty']->assign('action_link', array('href' => 'ads.php?act=list', 'text' => $GLOBALS['_LANG']['ad_list']));
            $GLOBALS['smarty']->assign('position_list', get_position_list());

            $GLOBALS['smarty']->assign('form_act', 'insert');
            $GLOBALS['smarty']->assign('action', 'add');
            $GLOBALS['smarty']->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            assign_query_info();
            $GLOBALS['smarty']->display('ads_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 新广告的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('ad_manage');

            /* 初始化变量 */
            $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $type = !empty($_POST['type']) ? intval($_POST['type']) : 0;
            $ad_name = !empty($_POST['ad_name']) ? trim($_POST['ad_name']) : '';

            if ($_POST['media_type'] == '0') {
                $ad_link = !empty($_POST['ad_link']) ? trim($_POST['ad_link']) : '';
            } else {
                $ad_link = !empty($_POST['ad_link2']) ? trim($_POST['ad_link2']) : '';
            }

            /* 获得广告的开始时期与结束日期 */
            $start_time = local_strtotime($_POST['start_time']);
            $end_time = local_strtotime($_POST['end_time']);

            /* 查看广告名称是否有重复 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('ad') . " WHERE ad_name = '$ad_name'";
            if ($GLOBALS['db']->getOne($sql) > 0) {
                $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                sys_msg($GLOBALS['_LANG']['ad_name_exist'], 0, $link);
            }

            /* 添加图片类型的广告 */
            if ($_POST['media_type'] == '0') {
                if ((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] == 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] != 'none')) {
                    $ad_code = basename($image->upload_image($_FILES['ad_img'], 'afficheimg'));
                }
                if (!empty($_POST['img_url'])) {
                    $ad_code = $_POST['img_url'];
                }
                if (((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] > 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] == 'none')) && empty($_POST['img_url'])) {
                    $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                    sys_msg($GLOBALS['_LANG']['js_languages']['ad_photo_empty'], 0, $link);
                }
            } /* 如果添加的广告是Flash广告 */
            elseif ($_POST['media_type'] == '1') {
                if ((isset($_FILES['upfile_flash']['error']) && $_FILES['upfile_flash']['error'] == 0) || (!isset($_FILES['upfile_flash']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['upfile_flash']['tmp_name'] != 'none')) {
                    /* 检查文件类型 */
                    if ($_FILES['upfile_flash']['type'] != "application/x-shockwave-flash") {
                        $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                        sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }

                    /* 生成文件名 */
                    $urlstr = date('Ymd');
                    for ($i = 0; $i < 6; $i++) {
                        $urlstr .= chr(mt_rand(97, 122));
                    }

                    $source_file = $_FILES['upfile_flash']['tmp_name'];
                    $target = ROOT_PATH . DATA_DIR . '/afficheimg/';
                    $file_name = $urlstr . '.swf';

                    if (!move_upload_file($source_file, $target . $file_name)) {
                        $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                        sys_msg($GLOBALS['_LANG']['upfile_error'], 0, $link);
                    } else {
                        $ad_code = $file_name;
                    }
                } elseif (!empty($_POST['flash_url'])) {
                    if (substr(strtolower($_POST['flash_url']), strlen($_POST['flash_url']) - 4) != '.swf') {
                        $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                        sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }
                    $ad_code = $_POST['flash_url'];
                }

                if (((isset($_FILES['upfile_flash']['error']) && $_FILES['upfile_flash']['error'] > 0) || (!isset($_FILES['upfile_flash']['error']) && isset($_FILES['upfile_flash']['tmp_name']) && $_FILES['upfile_flash']['tmp_name'] == 'none')) && empty($_POST['flash_url'])) {
                    $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                    sys_msg($GLOBALS['_LANG']['js_languages']['ad_flash_empty'], 0, $link);
                }
            } /* 如果广告类型为代码广告 */
            elseif ($_POST['media_type'] == '2') {
                if (!empty($_POST['ad_code'])) {
                    $ad_code = $_POST['ad_code'];
                } else {
                    $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                    sys_msg($GLOBALS['_LANG']['js_languages']['ad_code_empty'], 0, $link);
                }
            } /* 广告类型为文本广告 */
            elseif ($_POST['media_type'] == '3') {
                if (!empty($_POST['ad_text'])) {
                    $ad_code = $_POST['ad_text'];
                } else {
                    $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                    sys_msg($GLOBALS['_LANG']['js_languages']['ad_text_empty'], 0, $link);
                }
            }

            /* 插入数据 */
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('ad') . " (position_id,media_type,ad_name,ad_link,ad_code,start_time,end_time,link_man,link_email,link_phone,click_count,enabled)
    VALUES ('$_POST[position_id]',
            '$_POST[media_type]',
            '$ad_name',
            '$ad_link',
            '$ad_code',
            '$start_time',
            '$end_time',
            '$_POST[link_man]',
            '$_POST[link_email]',
            '$_POST[link_phone]',
            '0',
            '1')";

            $GLOBALS['db']->query($sql);
            /* 记录管理员操作 */
            admin_log($_POST['ad_name'], 'add', 'ads');

            clear_cache_files(); // 清除缓存文件

            /* 提示信息 */

            $link[0]['text'] = $GLOBALS['_LANG']['show_ads_template'];
            $link[0]['href'] = 'template.php?act=setup';

            $link[1]['text'] = $GLOBALS['_LANG']['back_ads_list'];
            $link[1]['href'] = 'ads.php?act=list';

            $link[2]['text'] = $GLOBALS['_LANG']['continue_add_ad'];
            $link[2]['href'] = 'ads.php?act=add';
            sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['ad_name'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 广告编辑页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            admin_priv('ad_manage');

            /* 获取广告数据 */
            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('ad') . " WHERE ad_id='" . intval($_REQUEST['id']) . "'";
            $ads_arr = $GLOBALS['db']->getRow($sql);

            $ads_arr['ad_name'] = htmlspecialchars($ads_arr['ad_name']);
            /* 格式化广告的有效日期 */
            $ads_arr['start_time'] = local_date('Y-m-d', $ads_arr['start_time']);
            $ads_arr['end_time'] = local_date('Y-m-d', $ads_arr['end_time']);

            if ($ads_arr['media_type'] == '0') {
                if (strpos($ads_arr['ad_code'], 'http://') === false && strpos($ads_arr['ad_code'], 'https://') === false) {
                    $src = '../' . DATA_DIR . '/afficheimg/' . $ads_arr['ad_code'];
                    $GLOBALS['smarty']->assign('img_src', $src);
                } else {
                    $src = $ads_arr['ad_code'];
                    $GLOBALS['smarty']->assign('url_src', $src);
                }
            }
            if ($ads_arr['media_type'] == '1') {
                if (strpos($ads_arr['ad_code'], 'http://') === false && strpos($ads_arr['ad_code'], 'https://') === false) {
                    $src = '../' . DATA_DIR . '/afficheimg/' . $ads_arr['ad_code'];
                    $GLOBALS['smarty']->assign('flash_url', $src);
                } else {
                    $src = $ads_arr['ad_code'];
                    $GLOBALS['smarty']->assign('flash_url', $src);
                }
                $GLOBALS['smarty']->assign('src', $src);
            }
            if ($ads_arr['media_type'] == 0) {
                $GLOBALS['smarty']->assign('media_type', $GLOBALS['_LANG']['ad_img']);
            } elseif ($ads_arr['media_type'] == 1) {
                $GLOBALS['smarty']->assign('media_type', $GLOBALS['_LANG']['ad_flash']);
            } elseif ($ads_arr['media_type'] == 2) {
                $GLOBALS['smarty']->assign('media_type', $GLOBALS['_LANG']['ad_html']);
            } elseif ($ads_arr['media_type'] == 3) {
                $GLOBALS['smarty']->assign('media_type', $GLOBALS['_LANG']['ad_text']);
            }

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['ads_edit']);
            $GLOBALS['smarty']->assign('action_link', array('href' => 'ads.php?act=list', 'text' => $GLOBALS['_LANG']['ad_list']));
            $GLOBALS['smarty']->assign('form_act', 'update');
            $GLOBALS['smarty']->assign('action', 'edit');
            $GLOBALS['smarty']->assign('position_list', get_position_list());
            $GLOBALS['smarty']->assign('ads', $ads_arr);

            assign_query_info();
            $GLOBALS['smarty']->display('ads_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 广告编辑的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            admin_priv('ad_manage');

            /* 初始化变量 */
            $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $type = !empty($_POST['media_type']) ? intval($_POST['media_type']) : 0;

            if ($_POST['media_type'] == '0') {
                $ad_link = !empty($_POST['ad_link']) ? trim($_POST['ad_link']) : '';
            } else {
                $ad_link = !empty($_POST['ad_link2']) ? trim($_POST['ad_link2']) : '';
            }

            /* 获得广告的开始时期与结束日期 */
            $start_time = local_strtotime($_POST['start_time']);
            $end_time = local_strtotime($_POST['end_time']);

            /* 编辑图片类型的广告 */
            if ($type == 0) {
                if ((isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] == 0) || (!isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] != 'none')) {
                    $img_up_info = basename($image->upload_image($_FILES['ad_img'], 'afficheimg'));
                    $ad_code = "ad_code = '" . $img_up_info . "'" . ',';
                } else {
                    $ad_code = '';
                }
                if (!empty($_POST['img_url'])) {
                    $ad_code = "ad_code = '$_POST[img_url]', ";
                }
            } /* 如果是编辑Flash广告 */
            elseif ($type == 1) {
                if ((isset($_FILES['upfile_flash']['error']) && $_FILES['upfile_flash']['error'] == 0) || (!isset($_FILES['upfile_flash']['error']) && isset($_FILES['upfile_flash']['tmp_name']) && $_FILES['upfile_flash']['tmp_name'] != 'none')) {
                    /* 检查文件类型 */
                    if ($_FILES['upfile_flash']['type'] != "application/x-shockwave-flash") {
                        $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                        sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }
                    /* 生成文件名 */
                    $urlstr = date('Ymd');
                    for ($i = 0; $i < 6; $i++) {
                        $urlstr .= chr(mt_rand(97, 122));
                    }

                    $source_file = $_FILES['upfile_flash']['tmp_name'];
                    $target = ROOT_PATH . DATA_DIR . '/afficheimg/';
                    $file_name = $urlstr . '.swf';

                    if (!move_upload_file($source_file, $target . $file_name)) {
                        $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                        sys_msg($GLOBALS['_LANG']['upfile_error'], 0, $link);
                    } else {
                        $ad_code = "ad_code = '$file_name', ";
                    }
                } elseif (!empty($_POST['flash_url'])) {
                    if (substr(strtolower($_POST['flash_url']), strlen($_POST['flash_url']) - 4) != '.swf') {
                        $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                        sys_msg($GLOBALS['_LANG']['upfile_flash_type'], 0, $link);
                    }
                    $ad_code = "ad_code = '" . $_POST['flash_url'] . "', ";
                } else {
                    $ad_code = '';
                }
            } /* 编辑代码类型的广告 */
            elseif ($type == 2) {
                $ad_code = "ad_code = '$_POST[ad_code]', ";
            }

            /* 编辑文本类型的广告 */
            if ($type == 3) {
                $ad_code = "ad_code = '$_POST[ad_text]', ";
            }

            $ad_code = str_replace('../' . DATA_DIR . '/afficheimg/', '', $ad_code);
            /* 更新信息 */
            $sql = "UPDATE " . $GLOBALS['ecs']->table('ad') . " SET " .
                "position_id = '$_POST[position_id]', " .
                "ad_name     = '$_POST[ad_name]', " .
                "ad_link     = '$ad_link', " .
                $ad_code .
                "start_time  = '$start_time', " .
                "end_time    = '$end_time', " .
                "link_man    = '$_POST[link_man]', " .
                "link_email  = '$_POST[link_email]', " .
                "link_phone  = '$_POST[link_phone]', " .
                "enabled     = '$_POST[enabled]' " .
                "WHERE ad_id = '$id'";
            $GLOBALS['db']->query($sql);

            /* 记录管理员操作 */
            admin_log($_POST['ad_name'], 'edit', 'ads');

            clear_cache_files(); // 清除模版缓存

            /* 提示信息 */
            $href[] = array('text' => $GLOBALS['_LANG']['back_ads_list'], 'href' => 'ads.php?act=list');
            sys_msg($GLOBALS['_LANG']['edit'] . ' ' . $_POST['ad_name'] . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $href);
        }

        /*------------------------------------------------------ */
        //--生成广告的JS代码
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_js') {
            admin_priv('ad_manage');

            /* 编码 */
            $lang_list = array(
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            );

            $js_code = "<script type=" . '"' . "text/javascript" . '"';
            $js_code .= ' src=' . '"' . $GLOBALS['ecs']->url() . 'affiche.php?act=js&type=' . $_REQUEST['type'] . '&ad_id=' . intval($_REQUEST['id']) . '"' . '></script>';

            $site_url = $GLOBALS['ecs']->url() . 'affiche.php?act=js&type=' . $_REQUEST['type'] . '&ad_id=' . intval($_REQUEST['id']);

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['add_js_code']);
            $GLOBALS['smarty']->assign('action_link', array('href' => 'ads.php?act=list', 'text' => $GLOBALS['_LANG']['ad_list']));
            $GLOBALS['smarty']->assign('url', $site_url);
            $GLOBALS['smarty']->assign('js_code', $js_code);
            $GLOBALS['smarty']->assign('lang_list', $lang_list);

            assign_query_info();
            $GLOBALS['smarty']->display('ads_js.htm');
        }

        /*------------------------------------------------------ */
        //-- 编辑广告名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_ad_name') {
            check_authz_json('ad_manage');

            $id = intval($_POST['id']);
            $ad_name = json_str_iconv(trim($_POST['val']));

            /* 检查广告名称是否重复 */
            if ($exc->num('ad_name', $ad_name, $id) != 0) {
                make_json_error(sprintf($GLOBALS['_LANG']['ad_name_exist'], $ad_name));
            } else {
                if ($exc->edit("ad_name = '$ad_name'", $id)) {
                    admin_log($ad_name, 'edit', 'ads');
                    make_json_result(stripslashes($ad_name));
                } else {
                    make_json_error($GLOBALS['db']->error());
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 删除广告位置
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            check_authz_json('ad_manage');

            $id = intval($_GET['id']);
            $img = $exc->get_name($id, 'ad_code');

            $exc->drop($id);

            if ((strpos($img, 'http://') === false) && (strpos($img, 'https://') === false) && get_file_suffix($img, $allow_suffix)) {
                $img_name = basename($img);
                @unlink(ROOT_PATH . DATA_DIR . '/afficheimg/' . $img_name);
            }

            admin_log('', 'remove', 'ads');

            $url = 'ads.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return $this->redirect($url);

        }
    }

    /* 获取广告数据列表 */
    private function get_adslist()
    {
        /* 过滤查询 */
        $pid = !empty($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;

        $filter = array();
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ad.ad_name' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = 'WHERE 1 ';
        if ($pid > 0) {
            $where .= " AND ad.position_id = '$pid' ";
        }

        /* 获得总记录数据 */
        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('ad') . ' AS ad ' . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获得广告数据 */
        $arr = array();
        $sql = 'SELECT ad.*, COUNT(o.order_id) AS ad_stats, p.position_name ' .
            'FROM ' . $GLOBALS['ecs']->table('ad') . 'AS ad ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('ad_position') . ' AS p ON p.position_id = ad.position_id ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('order_info') . " AS o ON o.from_ad = ad.ad_id $where " .
            'GROUP BY ad.ad_id ' .
            'ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'];

        $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

        foreach ($res as $rows) {
            /* 广告类型的名称 */
            $rows['type'] = ($rows['media_type'] == 0) ? $GLOBALS['_LANG']['ad_img'] : '';
            $rows['type'] .= ($rows['media_type'] == 1) ? $GLOBALS['_LANG']['ad_flash'] : '';
            $rows['type'] .= ($rows['media_type'] == 2) ? $GLOBALS['_LANG']['ad_html'] : '';
            $rows['type'] .= ($rows['media_type'] == 3) ? $GLOBALS['_LANG']['ad_text'] : '';

            /* 格式化日期 */
            $rows['start_date'] = local_date($GLOBALS['_CFG']['date_format'], $rows['start_time']);
            $rows['end_date'] = local_date($GLOBALS['_CFG']['date_format'], $rows['end_time']);

            $arr[] = $rows;
        }

        return array('ads' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    }
}
