<?php

namespace app\console\controller;

/**
 * 地区列表管理文件
 */
class AreaManage extends Init
{
    public function index()
    {
        $exc = new Exchange($GLOBALS['ecs']->table('region'), $GLOBALS['db'], 'region_id', 'region_name');

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 列出某地区下的所有地区列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('area_manage');

            /* 取得参数：上级地区id */
            $region_id = empty($_REQUEST['pid']) ? 0 : intval($_REQUEST['pid']);
            $this->assign('parent_id', $region_id);

            /* 取得列表显示的地区的类型 */
            if ($region_id == 0) {
                $region_type = 0;
            } else {
                $region_type = $exc->get_name($region_id, 'region_type') + 1;
            }
            $this->assign('region_type', $region_type);

            /* 获取地区列表 */
            $region_arr = area_list($region_id);
            $this->assign('region_arr', $region_arr);

            /* 当前的地区名称 */
            if ($region_id > 0) {
                $area_name = $exc->get_name($region_id);
                $area = '[ ' . $area_name . ' ] ';
                if ($region_arr) {
                    $area .= $region_arr[0]['type'];
                }
            } else {
                $area = $GLOBALS['_LANG']['country'];
            }
            $this->assign('area_here', $area);

            /* 返回上一级的链接 */
            if ($region_id > 0) {
                $parent_id = $exc->get_name($region_id, 'parent_id');
                $action_link = array('text' => $GLOBALS['_LANG']['back_page'], 'href' => 'area_manage.php?act=list&&pid=' . $parent_id);
            } else {
                $action_link = '';
            }
            $this->assign('action_link', $action_link);

            /* 赋值模板显示 */
            $this->assign('ur_here', $GLOBALS['_LANG']['05_area_list']);
            $this->assign('full_page', 1);

            assign_query_info();
            return $this->fetch('area_list');
        }

        /*------------------------------------------------------ */
        //-- 添加新的地区
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add_area') {
            check_authz_json('area_manage');

            $parent_id = intval($_POST['parent_id']);
            $region_name = json_str_iconv(trim($_POST['region_name']));
            $region_type = intval($_POST['region_type']);

            if (empty($region_name)) {
                return make_json_error($GLOBALS['_LANG']['region_name_empty']);
            }

            /* 查看区域是否重复 */
            if (!$exc->is_only('region_name', $region_name, 0, "parent_id = '$parent_id'")) {
                return make_json_error($GLOBALS['_LANG']['region_name_exist']);
            }

            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('region') . " (parent_id, region_name, region_type) " .
                "VALUES ('$parent_id', '$region_name', '$region_type')";
            if ($GLOBALS['db']->query($sql, 'SILENT')) {
                admin_log($region_name, 'add', 'area');

                /* 获取地区列表 */
                $region_arr = area_list($parent_id);
                $this->assign('region_arr', $region_arr);

                $this->assign('region_type', $region_type);

                return make_json_result($GLOBALS['smarty']->fetch('area_list.htm'));
            } else {
                return make_json_error($GLOBALS['_LANG']['add_area_error']);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑区域名称
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_area_name') {
            check_authz_json('area_manage');

            $id = intval($_POST['id']);
            $region_name = json_str_iconv(trim($_POST['val']));

            if (empty($region_name)) {
                return make_json_error($GLOBALS['_LANG']['region_name_empty']);
            }

            $msg = '';

            /* 查看区域是否重复 */
            $parent_id = $exc->get_name($id, 'parent_id');
            if (!$exc->is_only('region_name', $region_name, $id, "parent_id = '$parent_id'")) {
                return make_json_error($GLOBALS['_LANG']['region_name_exist']);
            }

            if ($exc->edit("region_name = '$region_name'", $id)) {
                admin_log($region_name, 'edit', 'area');
                return make_json_result(stripslashes($region_name));
            } else {
                return make_json_error($GLOBALS['db']->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 删除区域
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_area') {
            check_authz_json('area_manage');

            $id = intval($_REQUEST['id']);

            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id = '$id'";
            $region = $GLOBALS['db']->getRow($sql);

//    /* 如果底下有下级区域,不能删除 */
//    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('region') . " WHERE parent_id = '$id'";
//    if ($GLOBALS['db']->getOne($sql) > 0)
//    {
//        return make_json_error($GLOBALS['_LANG']['parent_id_exist']);
//    }
            $region_type = $region['region_type'];
            $delete_region[] = $id;
            $new_region_id = $id;
            if ($region_type < 6) {
                for ($i = 1; $i < 6 - $region_type; $i++) {
                    $new_region_id = $this->new_region_id($new_region_id);
                    if (count($new_region_id)) {
                        $delete_region = array_merge($delete_region, $new_region_id);
                    } else {
                        continue;
                    }
                }
            }
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table("region") . "WHERE region_id" . db_create_in($delete_region);
            $GLOBALS['db']->query($sql);
            if ($exc->drop($id)) {
                admin_log(addslashes($region['region_name']), 'remove', 'area');

                /* 获取地区列表 */
                $region_arr = area_list($region['parent_id']);
                $this->assign('region_arr', $region_arr);
                $this->assign('region_type', $region['region_type']);

                return make_json_result($GLOBALS['smarty']->fetch('area_list.htm'));
            } else {
                return make_json_error($GLOBALS['db']->error());
            }
        }
    }

    private function new_region_id($region_id)
    {
        $regions_id = array();
        if (empty($region_id)) {
            return $regions_id;
        }
        $sql = "SELECT region_id FROM " . $GLOBALS['ecs']->table("region") . "WHERE parent_id " . db_create_in($region_id);
        $result = $GLOBALS['db']->getAll($sql);
        foreach ($result as $val) {
            $regions_id[] = $val['region_id'];
        }
        return $regions_id;
    }
}
