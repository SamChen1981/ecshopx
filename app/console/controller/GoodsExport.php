<?php

namespace app\console\controller;

class GoodsExport extends Init
{
    public function index()
    {
        if ($_REQUEST['act'] == 'goods_export') {
            /* 检查权限 */
            admin_priv('goods_export');

            $this->assign('ur_here', $GLOBALS['_LANG']['14_goods_export']);
            $this->assign('cat_list', cat_list());
            $this->assign('brand_list', get_brand_list());
            $this->assign('goods_type_list', goods_type_list(0));
            $goods_fields = $this->my_array_merge($GLOBALS['_LANG']['custom'], $this->get_attributes());
            $data_format_array = array(
                'ecshop' => $GLOBALS['_LANG']['export_ecshop'],
                'taobao V4.3' => $GLOBALS['_LANG']['export_taobao_v43'],
                'taobao V4.6' => $GLOBALS['_LANG']['export_taobao_v46'],
                'taobao' => $GLOBALS['_LANG']['export_taobao'],
                'paipai' => $GLOBALS['_LANG']['export_paipai'],
                'paipai4' => $GLOBALS['_LANG']['export_paipai4'],
                'custom' => $GLOBALS['_LANG']['export_custom'],
            );
            $this->assign('data_format', $data_format_array);
            $this->assign('goods_fields', $goods_fields);
            assign_query_info();
            return $this->display('goods_export.view.php');
        } elseif ($_REQUEST['act'] == 'act_export_taobao') {
            /* 检查权限 */
            admin_priv('goods_export');
            $zip = new PHPZip;

            $where = $this->get_export_where_sql($_POST);

            $goods_class = intval($_POST['goods_class']);
            $post_express = floatval($_POST['post_express']);
            $express = floatval($_POST['express']);
            $ems = floatval($_POST['ems']);

            $shop_province = '""';
            $shop_city = '""';
            if ($GLOBALS['_CFG']['shop_province'] || $GLOBALS['_CFG']['shop_city']) {
                $sql = "SELECT region_id,  region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id IN ('{$GLOBALS['_CFG']['shop_province']}',  '{$GLOBALS['_CFG']['shop_city']}')";
                $arr = $GLOBALS['db']->getAll($sql);

                if ($arr) {
                    if (count($arr) == 1) {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                        } else {
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    } else {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                            $shop_city = '"' . $arr[1]['region_name'] . '"';
                        } else {
                            $shop_province = '"' . $arr[1]['region_name'] . '"';
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    }
                }
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_number, g.goods_desc, g.goods_img " .
                " FROM " . $GLOBALS['ecs']->table('goods') . " AS g " . $where;

            $res = $GLOBALS['db']->query($sql);

            /* csv文件数组 */
            $goods_value = array('goods_name' => '""', 'goods_class' => $goods_class, 'shop_class' => 0, 'new_level' => 5, 'province' => $shop_province, 'city' => $shop_city, 'sell_type' => '"b"', 'shop_price' => 0, 'add_price' => 0, 'goods_number' => 0, 'die_day' => 14, 'load_type' => 1, 'post_express' => $post_express, 'ems' => $ems, 'express' => $express, 'pay_type' => 2, 'allow_alipay' => 1, 'invoice' => 0, 'repair' => 0, 'resend' => 1, 'is_store' => 0, 'window' => 0, 'add_time' => '"1980-1-1  0:00:00"', 'story' => '""', 'goods_desc' => '""', 'goods_img' => '""', 'goods_attr' => '""', 'group_buy' => 0, 'group_buy_num' => 0, 'template' => 0, 'discount' => 0, 'modify_time' => '""', 'upload_status' => 100, 'img_status' => 1);

            $content = implode(",", $GLOBALS['_LANG']['taobao']) . "\n";

            foreach ($res as $row) {
                $goods_value['goods_name'] = '"' . $row['goods_name'] . '"';
                $goods_value['shop_price'] = $row['shop_price'];
                $goods_value['goods_number'] = $row['goods_number'];
                $goods_value['goods_desc'] = $this->replace_special_char($row['goods_desc']);
                $goods_value['goods_img'] = '"' . $row['goods_img'] . '"';

                $content .= implode("\t", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(ROOT_PATH . $row['goods_img'])) {
                    $zip->add_file(file_get_contents(ROOT_PATH . $row['goods_img']), $row['goods_img']);
                }
            }

            if (EC_CHARSET != 'utf-8') {
                $content = ecs_iconv(EC_CHARSET, 'utf-8', $content);
            }
            $zip->add_file("\xFF\xFE" . $this->utf82u2($content), 'goods_list.csv');

            header("Content-Disposition: attachment; filename=goods_list.zip");
            header("Content-Type: application/unknown");
            die($zip->file());
        } elseif ($_REQUEST['act'] == 'act_export_taobao V4.3') {
            /* 检查权限 */
            admin_priv('goods_export');
            $zip = new PHPZip;

            $where = $this->get_export_where_sql($_POST);

            $goods_class = intval($_POST['goods_class']);
            $post_express = floatval($_POST['post_express']);
            $express = floatval($_POST['express']);
            $ems = floatval($_POST['ems']);

            $shop_province = '""';
            $shop_city = '""';
            if ($GLOBALS['_CFG']['shop_province'] || $GLOBALS['_CFG']['shop_city']) {
                $sql = "SELECT region_id,  region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id IN ('{$GLOBALS['_CFG']['shop_province']}',  '{$GLOBALS['_CFG']['shop_city']}')";
                $arr = $GLOBALS['db']->getAll($sql);

                if ($arr) {
                    if (count($arr) == 1) {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                        } else {
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    } else {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                            $shop_city = '"' . $arr[1]['region_name'] . '"';
                        } else {
                            $shop_province = '"' . $arr[1]['region_name'] . '"';
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    }
                }
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_number, g.goods_desc, g.goods_img " .
                " FROM " . $GLOBALS['ecs']->table('goods') . " AS g " . $where;

            $res = $GLOBALS['db']->query($sql);

            /* csv文件数组 */
            $goods_value = array('goods_name' => '""', 'goods_class' => $goods_class, 'shop_class' => 0, 'new_level' => 5, 'province' => $shop_province, 'city' => $shop_city, 'sell_type' => '"b"', 'shop_price' => 0, 'add_price' => 0, 'goods_number' => 0, 'die_day' => 14, 'load_type' => 1, 'post_express' => $post_express, 'ems' => $ems, 'express' => $express, 'pay_type' => 2, 'allow_alipay' => 1, 'invoice' => 0, 'repair' => 0, 'resend' => 1, 'is_store' => 0, 'window' => 0, 'add_time' => '"1980-1-1  0:00:00"', 'story' => '""', 'goods_desc' => '""', 'goods_img' => '""', 'goods_attr' => '""', 'group_buy' => 0, 'group_buy_num' => 0, 'template' => 0, 'discount' => 0, 'modify_time' => '""', 'upload_status' => 100, 'img_status' => 1);

            $content = implode("\t", $GLOBALS['_LANG']['taobao']) . "\n";

            foreach ($res as $row) {
                $goods_value['goods_name'] = '"' . $row['goods_name'] . '"';
                $goods_value['shop_price'] = $row['shop_price'];
                $goods_value['goods_number'] = $row['goods_number'];
                $goods_value['goods_desc'] = $this->replace_special_char($row['goods_desc']);
                $goods_value['goods_img'] = '"' . $row['goods_img'] . '"';

                $content .= implode("\t", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(ROOT_PATH . $row['goods_img'])) {
                    $zip->add_file(file_get_contents(ROOT_PATH . $row['goods_img']), $row['goods_img']);
                }
            }
            if (EC_CHARSET != 'utf-8') {
                $content = ecs_iconv(EC_CHARSET, 'utf-8', $content);
            }
            $zip->add_file("\xFF\xFE" . $this->utf82u2($content), 'goods_list.csv');

            header("Content-Disposition: attachment; filename=goods_list.zip");
            header("Content-Type: application/unknown");
            die($zip->file());
        } /* 从淘宝导入数据 */
        elseif ($_REQUEST['act'] == 'import_taobao') {
            return $this->display('import_taobao.view.php');
        } elseif ($_REQUEST['act'] == 'act_export_ecshop') {
            /* 检查权限 */
            admin_priv('goods_export');

            $zip = new PHPZip;

            $where = $this->get_export_where_sql($_POST);

            $sql = "SELECT g.*, b.brand_name as brandname " .
                " FROM " . $GLOBALS['ecs']->table('goods') . " AS g LEFT JOIN " . $GLOBALS['ecs']->table('brand') . " AS b " .
                "ON g.brand_id = b.brand_id" . $where;

            $res = $GLOBALS['db']->query($sql);

            /* csv文件数组 */
            $goods_value = array();
            $goods_value['goods_name'] = '""';
            $goods_value['goods_sn'] = '""';
            $goods_value['brand_name'] = '""';
            $goods_value['market_price'] = 0;
            $goods_value['shop_price'] = 0;
            $goods_value['integral'] = 0;
            $goods_value['original_img'] = '""';
            $goods_value['goods_img'] = '""';
            $goods_value['goods_thumb'] = '""';
            $goods_value['keywords'] = '""';
            $goods_value['goods_brief'] = '""';
            $goods_value['goods_desc'] = '""';
            $goods_value['goods_weight'] = 0;
            $goods_value['goods_number'] = 0;
            $goods_value['warn_number'] = 0;
            $goods_value['is_best'] = 0;
            $goods_value['is_new'] = 0;
            $goods_value['is_hot'] = 0;
            $goods_value['is_on_sale'] = 1;
            $goods_value['is_alone_sale'] = 1;
            $goods_value['is_real'] = 1;
            $content = '"' . implode('","', $GLOBALS['_LANG']['ecshop']) . "\"\n";

            foreach ($res as $row) {
                $goods_value['goods_name'] = '"' . $row['goods_name'] . '"';
                $goods_value['goods_sn'] = '"' . $row['goods_sn'] . '"';
                $goods_value['brand_name'] = '"' . $row['brandname'] . '"';
                $goods_value['market_price'] = $row['market_price'];
                $goods_value['shop_price'] = $row['shop_price'];
                $goods_value['integral'] = $row['integral'];
                $goods_value['original_img'] = '"' . $row['original_img'] . '"';
                $goods_value['goods_img'] = '"' . $row['goods_img'] . '"';
                $goods_value['goods_thumb'] = '"' . $row['goods_thumb'] . '"';
                $goods_value['keywords'] = '"' . $row['keywords'] . '"';
                $goods_value['goods_brief'] = '"' . $this->replace_special_char($row['goods_brief'], false) . '"';
                $goods_value['goods_desc'] = '"' . $this->replace_special_char($row['goods_desc'], false) . '"';
                $goods_value['goods_weight'] = $row['goods_weight'];
                $goods_value['goods_number'] = $row['goods_number'];
                $goods_value['warn_number'] = $row['warn_number'];
                $goods_value['is_best'] = $row['is_best'];
                $goods_value['is_new'] = $row['is_new'];
                $goods_value['is_hot'] = $row['is_hot'];
                $goods_value['is_on_sale'] = $row['is_on_sale'];
                $goods_value['is_alone_sale'] = $row['is_alone_sale'];
                $goods_value['is_real'] = $row['is_real'];

                $content .= implode(",", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(ROOT_PATH . $row['goods_img'])) {
                    $zip->add_file(file_get_contents(ROOT_PATH . $row['goods_img']), $row['goods_img']);
                }
                if (!empty($row['original_img']) && is_file(ROOT_PATH . $row['original_img'])) {
                    $zip->add_file(file_get_contents(ROOT_PATH . $row['original_img']), $row['original_img']);
                }
                if (!empty($row['goods_thumb']) && is_file(ROOT_PATH . $row['goods_thumb'])) {
                    $zip->add_file(file_get_contents(ROOT_PATH . $row['goods_thumb']), $row['goods_thumb']);
                }
            }
            $charset = empty($_POST['charset']) ? 'UTF8' : trim($_POST['charset']);

            $zip->add_file(ecs_iconv(EC_CHARSET, $charset, $content), 'goods_list.csv');

            header("Content-Disposition: attachment; filename=goods_list.zip");
            header("Content-Type: application/unknown");
            die($zip->file());
        } elseif ($_REQUEST['act'] == 'act_export_paipai') {
            /* 检查权限 */
            admin_priv('goods_export');

            $zip = new PHPZip;

            $where = $this->get_export_where_sql($_POST);

            $post_express = floatval($_POST['post_express']);
            $express = floatval($_POST['express']);
            if ($post_express < 0) {
                $post_express = 10;
            }
            if ($express < 0) {
                $express = 20;
            }

            $shop_province = '""';
            $shop_city = '""';
            if ($GLOBALS['_CFG']['shop_province'] || $GLOBALS['_CFG']['shop_city']) {
                $sql = "SELECT region_id,  region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id IN ('{$GLOBALS['_CFG']['shop_province']}',  '{$GLOBALS['_CFG']['shop_city']}')";
                $arr = $GLOBALS['db']->getAll($sql);

                if ($arr) {
                    if (count($arr) == 1) {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                        } else {
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    } else {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                            $shop_city = '"' . $arr[1]['region_name'] . '"';
                        } else {
                            $shop_province = '"' . $arr[1]['region_name'] . '"';
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    }
                }
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_number, g.goods_desc, g.goods_img " .
                " FROM " . $GLOBALS['ecs']->table('goods') . " AS g " . $where;

            $res = $GLOBALS['db']->query($sql);


            $goods_value = array();
            $goods_value['id'] = -1;
            $goods_value['tree_node_id'] = -1;
            $goods_value['old_tree_node_id'] = -1;
            $goods_value['title'] = '""';
            $goods_value['id_in_web'] = '""';
            $goods_value['auctionType'] = '"b"';
            $goods_value['category'] = 0;
            $goods_value['shopCategoryId'] = '""';
            $goods_value['pictURL'] = '""';
            $goods_value['quantity'] = 0;
            $goods_value['duration'] = 14;
            $goods_value['startDate'] = '""';
            $goods_value['stuffStatus'] = 5;
            $goods_value['price'] = 0;
            $goods_value['increment'] = 0;
            $goods_value['prov'] = $shop_province;
            $goods_value['city'] = $shop_city;
            $goods_value['shippingOption'] = 1;
            $goods_value['ordinaryPostFee'] = $post_express;
            $goods_value['fastPostFee'] = $express;
            $goods_value['paymentOption'] = 5;
            $goods_value['haveInvoice'] = 0;
            $goods_value['haveGuarantee'] = 0;
            $goods_value['secureTradeAgree'] = 1;
            $goods_value['autoRepost'] = 1;
            $goods_value['shopWindow'] = 0;
            $goods_value['failed_reason'] = '""';
            $goods_value['pic_size'] = 0;
            $goods_value['pic_filename'] = '""';
            $goods_value['pic'] = '""';
            $goods_value['description'] = '""';
            $goods_value['story'] = '""';
            $goods_value['putStore'] = 0;
            $goods_value['pic_width'] = 80;
            $goods_value['pic_height'] = 80;
            $goods_value['skin'] = 0;
            $goods_value['prop'] = '""';


            $content = '"' . implode('","', $GLOBALS['_LANG']['paipai']) . "\"\n";

            foreach ($res as $row) {
                $goods_value['title'] = '"' . $row['goods_name'] . '"';
                $goods_value['price'] = $row['shop_price'];
                $goods_value['quantity'] = $row['goods_number'];
                $goods_value['description'] = $this->replace_special_char($row['goods_desc']);
                $goods_value['pic_filename'] = '"' . $row['goods_img'] . '"';

                $content .= implode(",", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(ROOT_PATH . $row['goods_img'])) {
                    $zip->add_file(file_get_contents(ROOT_PATH . $row['goods_img']), $row['goods_img']);
                }
            }

            if (EC_CHARSET == 'utf-8') {
                $zip->add_file(ecs_iconv('UTF8', 'GB2312', $content), 'goods_list.csv');
            } else {
                $zip->add_file($content, 'goods_list.csv');
            }

            header("Content-Disposition: attachment; filename=goods_list.zip");
            header("Content-Type: application/unknown");
            die($zip->file());
        } elseif ($_REQUEST['act'] == 'act_export_paipai4') {
            /* 检查权限 */
            admin_priv('goods_export');

            $zip = new PHPZip;

            $where = $this->get_export_where_sql($_POST);

            $post_express = floatval($_POST['post_express']);
            $express = floatval($_POST['express']);
            if ($post_express < 0) {
                $post_express = 10;
            }
            if ($express < 0) {
                $express = 20;
            }

            $shop_province = '""';
            $shop_city = '""';
            if ($GLOBALS['_CFG']['shop_province'] || $GLOBALS['_CFG']['shop_city']) {
                $sql = "SELECT region_id,  region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id IN ('{$GLOBALS['_CFG']['shop_province']}',  '{$GLOBALS['_CFG']['shop_city']}')";
                $arr = $GLOBALS['db']->getAll($sql);

                if ($arr) {
                    if (count($arr) == 1) {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                        } else {
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    } else {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                            $shop_city = '"' . $arr[1]['region_name'] . '"';
                        } else {
                            $shop_province = '"' . $arr[1]['region_name'] . '"';
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    }
                }
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_number, g.goods_desc, g.goods_img " .
                " FROM " . $GLOBALS['ecs']->table('goods') . " AS g " . $where;

            $res = $GLOBALS['db']->query($sql);


            $goods_value = array();
            $goods_value['id'] = -1;
            $goods_value['goods_name'] = '""';
            $goods_value['auctionType'] = '"b"';
            $goods_value['category'] = 0;
            $goods_value['shopCategoryId'] = '""';
            $goods_value['quantity'] = 0;
            $goods_value['duration'] = 14;
            $goods_value['startDate'] = '""';
            $goods_value['stuffStatus'] = 5;
            $goods_value['price'] = 0;
            $goods_value['increment'] = 0;
            $goods_value['prov'] = $shop_province;
            $goods_value['city'] = $shop_city;
            $goods_value['shippingOption'] = 1;
            $goods_value['ordinaryPostFee'] = $post_express;
            $goods_value['fastPostFee'] = $express;
            $goods_value['buyLimit'] = 0;
            $goods_value['paymentOption'] = 5;
            $goods_value['haveInvoice'] = 0;
            $goods_value['haveGuarantee'] = 0;
            $goods_value['secureTradeAgree'] = 1;
            $goods_value['autoRepost'] = 1;
            $goods_value['failed_reason'] = '""';
            $goods_value['pic_filename'] = '""';
            $goods_value['description'] = '""';
            $goods_value['shelfOption'] = 0;
            $goods_value['skin'] = 0;
            $goods_value['attr'] = '""';
            $goods_value['chengBao'] = '""';
            $goods_value['shopWindow'] = 0;

            $content = '"' . implode('","', $GLOBALS['_LANG']['paipai4']) . "\"\n";

            foreach ($res as $row) {
                $goods_value['goods_name'] = '"' . $row['goods_name'] . '"';
                $goods_value['price'] = $row['shop_price'];
                $goods_value['quantity'] = $row['goods_number'];
                $goods_value['description'] = $this->replace_special_char($row['goods_desc']);
                $goods_value['pic_filename'] = '"' . $row['goods_img'] . '"';

                $content .= implode(",", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(ROOT_PATH . $row['goods_img'])) {
                    $zip->add_file(file_get_contents(ROOT_PATH . $row['goods_img']), $row['goods_img']);
                }
            }

            if (EC_CHARSET == 'utf-8') {
                $zip->add_file(ecs_iconv('UTF8', 'GB2312', $content), 'goods_list.csv');
            } else {
                $zip->add_file($content, 'goods_list.csv');
            }

            header("Content-Disposition: attachment; filename=goods_list.zip");
            header("Content-Type: application/unknown");
            die($zip->file());
        } /* 从拍拍网导入数据 */
        elseif ($_REQUEST['act'] == 'import_paipai') {
            return $this->display('import_paipai.view.php');
        } /* 处理Ajax调用 */
        elseif ($_REQUEST['act'] == 'get_goods_fields') {
            $cat_id = isset($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $goods_fields = $this->my_array_merge($GLOBALS['_LANG']['custom'], $this->get_attributes($cat_id));
            return make_json_result($goods_fields);
        } elseif ($_REQUEST['act'] == 'act_export_custom') {
            /* 检查输出列 */
            if (empty($_POST['custom_goods_export'])) {
                return sys_msg($GLOBALS['_LANG']['custom_goods_field_not_null'], 1, array(), false);
            }

            /* 检查权限 */
            admin_priv('goods_export');

            $zip = new PHPZip;

            $where = $this->get_export_where_sql($_POST);

            $sql = "SELECT g.*, b.brand_name as brandname " .
                " FROM " . $GLOBALS['ecs']->table('goods') . " AS g LEFT JOIN " . $GLOBALS['ecs']->table('brand') . " AS b " .
                "ON g.brand_id = b.brand_id" . $where;

            $res = $GLOBALS['db']->query($sql);

            $goods_fields = explode(',', $_POST['custom_goods_export']);
            $goods_field_name = $this->set_goods_field_name($goods_fields, $GLOBALS['_LANG']['custom']);

            /* csv文件数组 */
            $goods_field_value = array();
            foreach ($goods_fields as $field) {
                if ($field == 'market_price' || $field == 'shop_price' || $field == 'integral' || $field == 'goods_weight' || $field == 'goods_number' || $field == 'warn_number' || $field == 'is_best' || $field == 'is_new' || $field == 'is_hot') {
                    $goods_field_value[$field] = 0;
                } elseif ($field == 'is_on_sale' || $field == 'is_alone_sale' || $field == 'is_real') {
                    $goods_field_value[$field] = 1;
                } else {
                    $goods_field_value[$field] = '""';
                }
            }

            $content = '"' . implode('","', $goods_field_name) . "\"\n";
            foreach ($res as $row) {
                $goods_value = $goods_field_value;
                isset($goods_value['goods_name']) && ($goods_value['goods_name'] = '"' . $row['goods_name'] . '"');
                isset($goods_value['goods_sn']) && ($goods_value['goods_sn'] = '"' . $row['goods_sn'] . '"');
                isset($goods_value['brand_name']) && ($goods_value['brand_name'] = $row['brandname']);
                isset($goods_value['market_price']) && ($goods_value['market_price'] = $row['market_price']);
                isset($goods_value['shop_price']) && ($goods_value['shop_price'] = $row['shop_price']);
                isset($goods_value['integral']) && ($goods_value['integral'] = $row['integral']);
                isset($goods_value['original_img']) && ($goods_value['original_img'] = '"' . $row['original_img'] . '"');
                isset($goods_value['keywords']) && ($goods_value['keywords'] = '"' . $row['keywords'] . '"');
                isset($goods_value['goods_brief']) && ($goods_value['goods_brief'] = '"' . $this->replace_special_char($row['goods_brief']) . '"');
                isset($goods_value['goods_desc']) && ($goods_value['goods_desc'] = '"' . $this->replace_special_char($row['goods_desc']) . '"');
                isset($goods_value['goods_weight']) && ($goods_value['goods_weight'] = $row['goods_weight']);
                isset($goods_value['goods_number']) && ($goods_value['goods_number'] = $row['goods_number']);
                isset($goods_value['warn_number']) && ($goods_value['warn_number'] = $row['warn_number']);
                isset($goods_value['is_best']) && ($goods_value['is_best'] = $row['is_best']);
                isset($goods_value['is_new']) && ($goods_value['is_new'] = $row['is_new']);
                isset($goods_value['is_hot']) && ($goods_value['is_hot'] = $row['is_hot']);
                isset($goods_value['is_on_sale']) && ($goods_value['is_on_sale'] = $row['is_on_sale']);
                isset($goods_value['is_alone_sale']) && ($goods_value['is_alone_sale'] = $row['is_alone_sale']);
                isset($goods_value['is_real']) && ($goods_value['is_real'] = $row['is_real']);

                $sql = "SELECT `attr_id`, `attr_value` FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE `goods_id` = '" . $row['goods_id'] . "'";
                $query = $GLOBALS['db']->query($sql);
                foreach ($query as $attr) {
                    if (in_array($attr['attr_id'], $goods_fields)) {
                        $goods_value[$attr['attr_id']] = '"' . $attr['attr_value'] . '"';
                    }
                }

                $content .= implode(",", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(ROOT_PATH . $row['goods_img'])) {
                    $zip->add_file(file_get_contents(ROOT_PATH . $row['goods_img']), $row['goods_img']);
                }
            }
            $charset = empty($_POST['charset_custom']) ? 'UTF8' : trim($_POST['charset_custom']);
            $zip->add_file(ecs_iconv(EC_CHARSET, $charset, $content), 'goods_list.csv');

            header("Content-Disposition: attachment; filename=goods_list.zip");
            header("Content-Type: application/unknown");
            die($zip->file());
        } elseif ($_REQUEST['act'] == 'get_goods_list') {
            $filters = json_decode($_REQUEST['JSON']);
            $arr = get_goods_list($filters);
            $opt = array();

            foreach ($arr as $key => $val) {
                $opt[] = array('goods_id' => $val['goods_id'],
                    'goods_name' => $val['goods_name']
                );
            }
            return make_json_result($opt);
        } elseif ($_REQUEST['act'] == 'act_export_taobao V4.6') {
            /* 检查权限 */
            admin_priv('goods_export');
            $zip = new PHPZip;

            $where = $this->get_export_where_sql($_POST);

            $goods_class = intval($_POST['goods_class']);
            $post_express = floatval($_POST['post_express']);
            $express = floatval($_POST['express']);
            $ems = floatval($_POST['ems']);

            $shop_province = '""';
            $shop_city = '""';
            if ($GLOBALS['_CFG']['shop_province'] || $GLOBALS['_CFG']['shop_city']) {
                $sql = "SELECT region_id,  region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id IN ('{$GLOBALS['_CFG']['shop_province']}',  '{$GLOBALS['_CFG']['shop_city']}')";
                $arr = $GLOBALS['db']->getAll($sql);

                if ($arr) {
                    if (count($arr) == 1) {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                        } else {
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    } else {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                            $shop_city = '"' . $arr[1]['region_name'] . '"';
                        } else {
                            $shop_province = '"' . $arr[1]['region_name'] . '"';
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    }
                }
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_number, g.goods_desc, g.goods_img " .
                " FROM " . $GLOBALS['ecs']->table('goods') . " AS g " . $where;

            $res = $GLOBALS['db']->query($sql);

            /* csv文件数组 */
            $goods_value = array('goods_name' => '', 'goods_class' => $goods_class, 'shop_class' => 0, 'new_level' => 0, 'province' => $shop_province, 'city' => $shop_city, 'sell_type' => '"b"', 'shop_price' => 0, 'add_price' => 0, 'goods_number' => 0, 'die_day' => 14, 'load_type' => 1, 'post_express' => $post_express, 'ems' => $ems, 'express' => $express, 'pay_type' => '', 'allow_alipay' => '', 'invoice' => 0, 'repair' => 0, 'resend' => 1, 'is_store' => 0, 'window' => 0, 'add_time' => '"1980-1-1  0:00:00"', 'story' => '', 'goods_desc' => '', 'goods_img' => '', 'goods_attr' => '', 'group_buy' => '', 'group_buy_num' => '', 'template' => 0, 'discount' => 0, 'modify_time' => '"2011-5-1  0:00:00"', 'upload_status' => 100, 'img_status' => 1, 'img_status' => '', 'rebate_proportion' => 0, 'new_goods_img' => '', 'video' => '', 'marketing_property_mix' => '', 'user_input_ID_numbers' => '', 'input_user_name_value' => '', 'sellers_code' => '', 'another_of_marketing_property' => '', 'charge_type' => '0', 'treasure_number' => '', 'ID_number' => '',);

            $content = implode("\t", $GLOBALS['_LANG']['taobao46']) . "\n";

            foreach ($res as $row) {

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(ROOT_PATH . $row['goods_img'])) {
                    $row['new_goods_img'] = preg_replace("/(^images\/)+(.*)(.gif|.jpg|.jpeg|.png)$/", "\${2}.tbi", $row['goods_img']);
                    @copy(ROOT_PATH . $row['goods_img'], ROOT_PATH . "images\/" . $row['new_goods_img']);
                    if (is_file(ROOT_PATH . "images\/" . $row['new_goods_img'])) {
                        $zip->add_file(file_get_contents(ROOT_PATH . "images\/" . $row['new_goods_img']), $row['new_goods_img']);
                        unlink(ROOT_PATH . "images\/" . $row['new_goods_img']);
                    }
                }
                $goods_value['goods_name'] = '"' . $row['goods_name'] . '"';
                $goods_value['shop_price'] = $row['shop_price'];
                $goods_value['goods_number'] = $row['goods_number'];
                $goods_value['goods_desc'] = $this->replace_special_char($row['goods_desc']);
                if (!empty($row['new_goods_img'])) {
                    $row['new_goods_img'] = str_ireplace('/', '\\', $row['new_goods_img'], $row['new_goods_img']);
                    $row['new_goods_img'] = str_ireplace('.tbi', '', $row['new_goods_img'], $row['new_goods_img']);
                    $goods_value['new_goods_img'] = '"' . $row['new_goods_img'] . ':0:0:|;' . '"';
                }

                $content .= implode("\t", $goods_value) . "\n";
            }
            if (EC_CHARSET != 'utf-8') {
                $content = ecs_iconv(EC_CHARSET, 'utf-8', $content);
            }
            $zip->add_file("\xFF\xFE" . $this->utf82u2($content), 'goods_list.csv');

            header("Content-Disposition: attachment; filename=goods_list.zip");
            header("Content-Type: application/unknown");
            die($zip->file());
        }
    }

    /**
     *
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function utf82u2($str)
    {
        $len = strlen($str);
        $start = 0;
        $result = '';

        if ($len == 0) {
            return $result;
        }

        while ($start < $len) {
            $num = ord($str{$start});
            if ($num < 127) {
                $result .= chr($num) . chr($num >> 8);
                $start += 1;
            } else {
                if ($num < 192) {
                    /* 无效字节 */
                    $start++;
                } elseif ($num < 224) {
                    if ($start + 1 < $len) {
                        $num = (ord($str{$start}) & 0x3f) << 6;
                        $num += ord($str{$start + 1}) & 0x3f;
                        $result .= chr($num & 0xff) . chr($num >> 8);
                    }
                    $start += 2;
                } elseif ($num < 240) {
                    if ($start + 2 < $len) {
                        $num = (ord($str{$start}) & 0x1f) << 12;
                        $num += (ord($str{$start + 1}) & 0x3f) << 6;
                        $num += ord($str{$start + 2}) & 0x3f;

                        $result .= chr($num & 0xff) . chr($num >> 8);
                    }
                    $start += 3;
                } elseif ($num < 248) {
                    if ($start + 3 < $len) {
                        $num = (ord($str{$start}) & 0x0f) << 18;
                        $num += (ord($str{$start + 1}) & 0x3f) << 12;
                        $num += (ord($str{$start + 2}) & 0x3f) << 6;
                        $num += ord($str{$start + 3}) & 0x3f;
                        $result .= chr($num & 0xff) . chr($num >> 8) . chr($num >> 16);
                    }
                    $start += 4;
                } elseif ($num < 252) {
                    if ($start + 4 < $len) {
                        /* 不做处理 */
                    }
                    $start += 5;
                } else {
                    if ($start + 5 < $len) {
                        /* 不做处理 */
                    }
                    $start += 6;
                }
            }
        }

        return $result;
    }

    /**
     *
     *
     * @access  public
     * @param
     *
     * @return string
     */
    private function image_path_format($content)
    {
        $prefix = defined('FORCE_SSL_LOGIN') ? 'https://' : 'http://' . $_SERVER['SERVER_NAME'];
        $pattern = '/(background|src)=[\'|\"]((?!http:\/\/).*?)[\'|\"]/i';
        $replace = "$1='" . $prefix . "$2'";
        return preg_replace($pattern, $replace, $content);
    }

    /**
     * 获取商品类型属性
     *
     * @param int $cat_id 商品类型ID
     *
     * @return array
     */
    private function get_attributes($cat_id = 0)
    {
        $sql = "SELECT `attr_id`, `cat_id`, `attr_name` FROM " . $GLOBALS['ecs']->table('attribute') . " ";
        if (!empty($cat_id)) {
            $cat_id = intval($cat_id);
            $sql .= " WHERE `cat_id` = '{$cat_id}' ";
        }
        $sql .= " ORDER BY `cat_id` ASC, `attr_id` ASC ";
        $attributes = array();
        $query = $GLOBALS['db']->query($sql);
        foreach ($query as $row) {
            $attributes[$row['attr_id']] = $row['attr_name'];
        }
        return $attributes;
    }

    /**
     * 设置导出商品字段名
     *
     * @param array $array 字段数组
     * @param array $lang 字段名
     *
     * @return array
     */
    private function set_goods_field_name($array, $lang)
    {
        $tmp_fields = $array;
        foreach ($array as $key => $value) {
            if (isset($lang[$value])) {
                $tmp_fields[$key] = $lang[$value];
            } else {
                $tmp_fields[$key] = $GLOBALS['db']->getOne("SELECT `attr_name` FROM " . $GLOBALS['ecs']->table('attribute') . " WHERE `attr_id` = '" . intval($value) . "'");
            }
        }
        return $tmp_fields;
    }

    /**
     * 数组合并
     *
     * @param array $array1 数组1
     * @param array $array2 数组2
     *
     * @return array
     */
    private function my_array_merge($array1, $array2)
    {
        $new_array = $array1;
        foreach ($array2 as $key => $val) {
            $new_array[$key] = $val;
        }
        return $new_array;
    }

    /**
     * 生成商品导出过滤条件
     *
     * @param array $filter 过滤条件数组
     *
     * @return string
     */
    private function get_export_where_sql($filter)
    {
        $where = '';
        if (!empty($filter['goods_ids'])) {
            $goods_ids = explode(',', $filter['goods_ids']);
            if (is_array($goods_ids) && !empty($goods_ids)) {
                $goods_ids = array_unique($goods_ids);
                $goods_ids = "'" . implode("','", $goods_ids) . "'";
            } else {
                $goods_ids = "'0'";
            }
            $where = " WHERE g.is_delete = 0 AND g.goods_id IN (" . $goods_ids . ") ";
        } else {
            $_filter = new StdClass();
            $_filter->cat_id = $filter['cat_id'];
            $_filter->brand_id = $filter['brand_id'];
            $_filter->keyword = $filter['keyword'];
            $where = get_where_sql($_filter);
        }
        return $where;
    }

    /**
     * 替换影响csv文件的字符
     *
     * @param $str string 处理字符串
     */
    private function replace_special_char($str, $replace = true)
    {
        $str = str_replace("\r\n", "", $this->image_path_format($str));
        $str = str_replace("\t", "    ", $str);
        $str = str_replace("\n", "", $str);
        if ($replace == true) {
            $str = '"' . str_replace('"', '""', $str) . '"';
        }
        return $str;
    }
}
