<?php

namespace app\console\controller;

/**
 * 图片批量处理程序
 */
class PictureBatch extends Init
{
    public function index()
    {
        load_helper('goods', 'console');
        $image = new Image($GLOBALS['_CFG']['bgcolor']);

        /* 权限检查 */
        admin_priv('picture_batch');

        if (empty($_GET['is_ajax'])) {
            assign_query_info();
            $this->assign('ur_here', $GLOBALS['_LANG']['12_batch_pic']);
            $this->assign('cat_list', cat_list(0, 0));
            $this->assign('brand_list', get_brand_list());
            return $this->fetch('picture_batch');
        } elseif (!empty($_GET['get_goods'])) {
            $brand_id = intval($_GET['brand_id']);
            $cat_id = intval($_GET['cat_id']);
            $goods_where = '';

            if (!empty($cat_id)) {
                $goods_where .= ' AND ' . get_children($cat_id);
            }
            if (!empty($brand_id)) {
                $goods_where .= " AND g.`brand_id` = '$brand_id'";
            }

            $sql = 'SELECT `goods_id`, `goods_name` FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g WHERE 1 ' . $goods_where . ' LIMIT 50';

            die(json_encode($GLOBALS['db']->getAll($sql)));
        } else {
            $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0);
            $do_album = empty($_GET['do_album']) ? 0 : 1;
            $do_icon = empty($_GET['do_icon']) ? 0 : 1;
            $goods_id = trim($_GET['goods_id']);
            $brand_id = intval($_GET['brand_id']);
            $cat_id = intval($_GET['cat_id']);
            $goods_where = '';
            $album_where = '';
            $module_no = 0;

            if ($do_album == 1 and $do_icon == 0) {
                $module_no = 1;
            }
            if (empty($goods_id)) {
                if (!empty($cat_id)) {
                    $goods_where .= ' AND ' . get_children($cat_id);
                }
                if (!empty($brand_id)) {
                    $goods_where .= " AND g.`brand_id` = '$brand_id'";
                }
            } else {
                $goods_where .= ' AND g.`goods_id` ' . db_create_in($goods_id);
            }

            if (!empty($goods_where)) {
                $album_where = ', ' . $GLOBALS['ecs']->table('goods') . " AS g WHERE album.img_original > '' AND album.goods_id = g.goods_id " . $goods_where;
            } else {
                $album_where = " WHERE album.img_original > ''";
            }


            /* 设置最长执行时间为5分钟 */
            @set_time_limit(300);

            if (isset($_GET['start'])) {
                $page_size = 50; // 默认50张/页
                $thumb = empty($_GET['thumb']) ? 0 : 1;
                $watermark = empty($_GET['watermark']) ? 0 : 1;
                $change = empty($_GET['change']) ? 0 : 1;
                $silent = empty($_GET['silent']) ? 0 : 1;

                /* 检查GD */
                if ($image->gd_version() < 1) {
                    return make_json_error($GLOBALS['_LANG']['missing_gd']);
                }

                /* 如果需要添加水印，检查水印文件 */
                if ((!empty($GLOBALS['_CFG']['watermark'])) && ($GLOBALS['_CFG']['watermark_place'] > 0) && $watermark && (!$image->validate_image($GLOBALS['_CFG']['watermark']))) {
                    return make_json_error($image->error_msg());
                }
                $title = '';

                if (isset($_GET['total_icon'])) {
                    $count = $GLOBALS['db']->GetOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " AS g WHERE g.original_img <> ''" . $goods_where);
                    $title = sprintf($GLOBALS['_LANG']['goods_format'], $count, $page_size);
                }

                if (isset($_GET['total_album'])) {
                    $count = $GLOBALS['db']->GetOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_gallery') . ' AS album ' . $album_where);
                    $title = sprintf('&nbsp;' . $GLOBALS['_LANG']['gallery_format'], $count, $page_size);
                    $module_no = 1;
                }
                $result = array('error' => 0, 'message' => '', 'content' => '', 'module_no' => $module_no, 'done' => 1, 'title' => $title, 'page_size' => $page_size,
                    'page' => 1, 'thumb' => $thumb, 'watermark' => $watermark, 'total' => 1, 'change' => $change, 'silent' => $silent,
                    'do_album' => $do_album, 'do_icon' => $do_icon, 'goods_id' => $goods_id, 'brand_id' => $brand_id, 'cat_id' => $cat_id,
                    'row' => array('new_page' => sprintf($GLOBALS['_LANG']['page_format'], 1),
                        'new_total' => sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $page_size)),
                        'new_time' => $GLOBALS['_LANG']['wait'],
                        'cur_id' => 'time_1'));

                die(json_encode($result));
            } else {
                $result = array('error' => 0, 'message' => '', 'content' => '', 'done' => 2, 'do_album' => $do_album, 'do_icon' => $do_icon, 'goods_id' => $goods_id, 'brand_id' => $brand_id, 'cat_id' => $cat_id);
                $result['thumb'] = empty($_GET['thumb']) ? 0 : 1;
                $result['watermark'] = empty($_GET['watermark']) ? 0 : 1;
                $result['change'] = empty($_GET['change']) ? 0 : 1;
                $result['page_size'] = empty($_GET['page_size']) ? 100 : intval($_GET['page_size']);
                $result['module_no'] = empty($_GET['module_no']) ? 0 : intval($_GET['module_no']);
                $result['page'] = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $result['total'] = isset($_GET['total']) ? intval($_GET['total']) : 1;
                $result['silent'] = empty($_GET['silent']) ? 0 : 1;

                if ($result['silent']) {
                    $err_msg = array();
                }

                /*------------------------------------------------------ */
                //-- 商品图片
                /*------------------------------------------------------ */
                if ($result['module_no'] == 0) {
                    $count = $GLOBALS['db']->GetOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " AS g WHERE g.original_img > ''" . $goods_where);
                    /* 页数在许可范围内 */
                    if ($result['page'] <= ceil($count / $result['page_size'])) {
                        $start_time = gmtime(); //开始执行时间

                        /* 开始处理 */
                        if ($proc_thumb) {
                            $this->process_image_ex($result['page'], $result['page_size'], $result['module_no'], $result['thumb'], $result['watermark'], $result['change'], $result['silent']);
                        } else {
                            $this->process_image($result['page'], $result['page_size'], $result['module_no'], $result['thumb'], $result['watermark'], $result['change'], $result['silent']);
                        }
                        $end_time = gmtime();
                        $result['row']['pre_id'] = 'time_' . $result['total'];
                        $result['row']['pre_time'] = ($end_time > $start_time) ? $end_time - $start_time : 1;
                        $result['row']['pre_time'] = sprintf($GLOBALS['_LANG']['time_format'], $result['row']['pre_time']);
                        $result['row']['cur_id'] = 'time_' . ($result['total'] + 1);
                        $result['page']++; // 新行
                        $result['row']['new_page'] = sprintf($GLOBALS['_LANG']['page_format'], $result['page']);
                        $result['row']['new_total'] = sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $result['page_size']));
                        $result['row']['new_time'] = $GLOBALS['_LANG']['wait'];
                        $result['total']++;
                    } else {
                        --$result['total'];
                        --$result['page'];
                        $result['done'] = 0;
                        $result['message'] = ($do_album) ? '' : $GLOBALS['_LANG']['done'];
                        /* 清除缓存 */
                        clear_cache_files();
                        die(json_encode($result));
                    }
                } elseif ($result['module_no'] == 1 && $result['do_album'] == 1) {
                    //商品相册
                    $count = $GLOBALS['db']->GetOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_gallery') . ' AS album ' . $album_where);

                    if ($result['page'] <= ceil($count / $result['page_size'])) {
                        $start_time = gmtime(); // 开始执行时间
                        /* 开始处理 */
                        if ($proc_thumb) {
                            $this->process_image_ex($result['page'], $result['page_size'], $result['module_no'], $result['thumb'], $result['watermark'], $result['change'], $result['silent']);
                        } else {
                            $this->process_image($result['page'], $result['page_size'], $result['module_no'], $result['thumb'], $result['watermark'], $result['change'], $result['silent']);
                        }
                        $end_time = gmtime();

                        $result['row']['pre_id'] = 'time_' . $result['total'];
                        $result['row']['pre_time'] = ($end_time > $start_time) ? $end_time - $start_time : 1;
                        $result['row']['pre_time'] = sprintf($GLOBALS['_LANG']['time_format'], $result['row']['pre_time']);
                        $result['row']['cur_id'] = 'time_' . ($result['total'] + 1);
                        $result['page']++;
                        $result['row']['new_page'] = sprintf($GLOBALS['_LANG']['page_format'], $result['page']);
                        $result['row']['new_total'] = sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $result['page_size']));
                        $result['row']['new_time'] = $GLOBALS['_LANG']['wait'];

                        $result['total']++;
                    } else {
                        $result['row']['pre_id'] = 'time_' . $result['total'];
                        $result['row']['cur_id'] = 'time_' . ($result['total'] + 1);
                        $result['row']['new_page'] = sprintf($GLOBALS['_LANG']['page_format'], $result['page']);
                        $result['row']['new_total'] = sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $result['page_size']));
                        $result['row']['new_time'] = $GLOBALS['_LANG']['wait'];

                        /* 执行结束 */
                        $result['done'] = 0;
                        $result['message'] = $GLOBALS['_LANG']['done'];
                        /* 清除缓存 */
                        clear_cache_files();
                    }
                }

                if ($result['silent'] && $err_msg) {
                    $result['content'] = implode('<br />', $err_msg);
                }

                die(json_encode($result));
            }
        }
    }

    /**
     * 图片处理函数
     *
     * @access  public
     * @param integer $page
     * @param integer $page_size
     * @param integer $type
     * @param boolen $thumb 是否生成缩略图
     * @param boolen $watermark 是否生成水印图
     * @param boolen $change true 生成新图，删除旧图 false 用新图覆盖旧图
     * @param boolen $silent 是否执行能忽略错误
     *
     * @return void
     */
    private function process_image($page = 1, $page_size = 100, $type = 0, $thumb = true, $watermark = true, $change = false, $silent = true)
    {
        if ($type == 0) {
            $sql = "SELECT g.goods_id, g.original_img, g.goods_img, g.goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . " AS g WHERE g.original_img > ''" . $GLOBALS['goods_where'];
            $res = $GLOBALS['db']->SelectLimit($sql, $page_size, ($page - 1) * $page_size);
            foreach ($res as $row) {
                $goods_thumb = '';
                $image = '';

                /* 水印 */
                if ($watermark) {
                    /* 获取加水印图片的目录 */
                    if (empty($row['goods_img'])) {
                        $dir = dirname(ROOT_PATH . $row['original_img']) . '/';
                    } else {
                        $dir = dirname(ROOT_PATH . $row['goods_img']) . '/';
                    }

                    $image = $GLOBALS['image']->make_thumb(ROOT_PATH . $row['original_img'], $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height'], $dir); //先生成缩略图

                    if (!$image) {
                        //出错返回
                        $msg = sprintf($GLOBALS['_LANG']['error_pos'], $row['goods_id']) . "\n" . $GLOBALS['image']->error_msg();
                        if ($silent) {
                            $GLOBALS['err_msg'][] = $msg;
                            continue;
                        } else {
                            return make_json_error($msg);
                        }
                    }

                    $image = $GLOBALS['image']->add_watermark(ROOT_PATH . $image, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']);

                    if (!$image) {
                        //出错返回
                        $msg = sprintf($GLOBALS['_LANG']['error_pos'], $row['goods_id']) . "\n" . $GLOBALS['image']->error_msg();
                        if ($silent) {
                            $GLOBALS['err_msg'][] = $msg;
                            continue;
                        } else {
                            return make_json_error($msg);
                        }
                    }

                    /* 重新格式化图片名称 */
                    $image = reformat_image_name('goods', $row['goods_id'], $image, 'goods');
                    if ($change || empty($row['goods_img'])) {
                        /* 要生成新链接的处理过程 */
                        if ($image != $row['goods_img']) {
                            $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET goods_img = '$image' WHERE goods_id = '" . $row['goods_id'] . "'";
                            $GLOBALS['db']->query($sql);
                            /* 防止原图被删除 */
                            if ($row['goods_img'] != $row['original_img']) {
                                @unlink(ROOT_PATH . $row['goods_img']);
                            }
                        }
                    } else {
                        $this->replace_image($image, $row['goods_img'], $row['goods_id'], $silent);
                    }
                }

                /* 缩略图 */
                if ($thumb) {
                    if (empty($row['goods_thumb'])) {
                        $dir = dirname(ROOT_PATH . $row['original_img']) . '/';
                    } else {
                        $dir = dirname(ROOT_PATH . $row['goods_thumb']) . '/';
                    }

                    $goods_thumb = $GLOBALS['image']->make_thumb(ROOT_PATH . $row['original_img'], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height'], $dir);

                    /* 出错处理 */
                    if (!$goods_thumb) {
                        $msg = sprintf($GLOBALS['_LANG']['error_pos'], $row['goods_id']) . "\n" . $GLOBALS['image']->error_msg();
                        if ($silent) {
                            $GLOBALS['err_msg'][] = $msg;
                            continue;
                        } else {
                            return make_json_error($msg);
                        }
                    }
                    /* 重新格式化图片名称 */
                    $goods_thumb = reformat_image_name('goods_thumb', $row['goods_id'], $goods_thumb, 'thumb');
                    if ($change || empty($row['goods_thumb'])) {
                        if ($row['goods_thumb'] != $goods_thumb) {
                            $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET goods_thumb = '$goods_thumb' WHERE goods_id = '" . $row['goods_id'] . "'";
                            $GLOBALS['db']->query($sql);
                            /* 防止原图被删除 */
                            if ($row['goods_thumb'] != $row['original_img']) {
                                @unlink(ROOT_PATH . $row['goods_thumb']);
                            }
                        }
                    } else {
                        $this->replace_image($goods_thumb, $row['goods_thumb'], $row['goods_id'], $silent);
                    }
                }
            }
        } else {
            /* 遍历商品相册 */
            $sql = "SELECT album.goods_id, album.img_id, album.img_url, album.thumb_url, album.img_original FROM " . $GLOBALS['ecs']->table('goods_gallery') . " AS album " . $GLOBALS['album_where'];
            $res = $GLOBALS['db']->SelectLimit($sql, $page_size, ($page - 1) * $page_size);

            foreach ($res as $row) {
                $thumb_url = '';
                $image = '';

                /* 水印 */
                if ($watermark && file_exists(ROOT_PATH . $row['img_original'])) {
                    if (empty($row['img_url'])) {
                        $dir = dirname(ROOT_PATH . $row['img_original']) . '/';
                    } else {
                        $dir = dirname(ROOT_PATH . $row['img_url']) . '/';
                    }

                    $file_name = cls_image::unique_name($dir);
                    $file_name .= cls_image::get_filetype(empty($row['img_url']) ? $row['img_original'] : $row['img_url']);

                    copy(ROOT_PATH . $row['img_original'], $dir . $file_name);
                    $image = $GLOBALS['image']->add_watermark($dir . $file_name, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']);
                    if (!$image) {
                        @unlink($dir . $file_name);
                        $msg = sprintf($GLOBALS['_LANG']['error_pos'], $row['goods_id']) . "\n" . $GLOBALS['image']->error_msg();
                        if ($silent) {
                            $GLOBALS['err_msg'][] = $msg;
                            continue;
                        } else {
                            return make_json_error($msg);
                        }
                    }
                    /* 重新格式化图片名称 */
                    $image = reformat_image_name('gallery', $row['goods_id'], $image, 'goods');
                    if ($change || empty($row['img_url']) || $row['img_original'] == $row['img_url']) {
                        if ($image != $row['img_url']) {
                            $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_gallery') . " SET img_url='$image' WHERE img_id='$row[img_id]'";
                            $GLOBALS['db']->query($sql);
                            if ($row['img_original'] != $row['img_url']) {
                                @unlink(ROOT_PATH . $row['img_url']);
                            }
                        }
                    } else {
                        $this->replace_image($image, $row['img_url'], $row['goods_id'], $silent);
                    }
                }

                /* 缩略图 */
                if ($thumb) {
                    if (empty($row['thumb_url'])) {
                        $dir = dirname(ROOT_PATH . $row['img_original']) . '/';
                    } else {
                        $dir = dirname(ROOT_PATH . $row['thumb_url']) . '/';
                    }

                    $thumb_url = $GLOBALS['image']->make_thumb(ROOT_PATH . $row['img_original'], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height'], $dir);

                    if (!$thumb_url) {
                        $msg = sprintf($GLOBALS['_LANG']['error_pos'], $row['goods_id']) . "\n" . $GLOBALS['image']->error_msg();
                        if ($silent) {
                            $GLOBALS['err_msg'][] = $msg;
                            continue;
                        } else {
                            return make_json_error($msg);
                        }
                    }
                    /* 重新格式化图片名称 */
                    $thumb_url = reformat_image_name('gallery_thumb', $row['goods_id'], $thumb_url, 'thumb');
                    if ($change || empty($row['thumb_url'])) {
                        if ($thumb_url != $row['thumb_url']) {
                            $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_gallery') . " SET thumb_url='$thumb_url' WHERE img_id='$row[img_id]'";
                            $GLOBALS['db']->query($sql);
                            @unlink(ROOT_PATH . $row['thumb_url']);
                        }
                    } else {
                        $this->replace_image($thumb_url, $row['thumb_url'], $row['goods_id'], $silent);
                    }
                }
            }
        }
    }

    /**
     * 图片处理函数
     *
     * @access  public
     * @param integer $page
     * @param integer $page_size
     * @param integer $type
     * @param boolen $thumb 是否生成缩略图
     * @param boolen $watermark 是否生成水印图
     * @param boolen $change true 生成新图，删除旧图 false 用新图覆盖旧图
     * @param boolen $silent 是否执行能忽略错误
     *
     * @return void
     */
    private function process_image_ex($page = 1, $page_size = 100, $type = 0, $thumb = true, $watermark = true, $change = false, $silent = true)
    {
        if ($type == 0) {
            $sql = "SELECT g.goods_id, g.original_img, g.goods_img, g.goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . " AS g WHERE g.original_img > ''" . $goods_where;
            $res = $GLOBALS['db']->SelectLimit($sql, $page_size, ($page - 1) * $page_size);

            foreach ($res as $row) {
                if ($thumb) {
                    get_image_path($row['goods_id'], '', true, 'goods', true);
                }
                if ($watermark) {
                    get_image_path($row['goods_id'], '', false, 'goods', true);
                }
            }
        } else {
            $sql = "SELECT album.goods_id, album.img_id, album.img_url, album.thumb_url, album.img_original FROM " . $GLOBALS['ecs']->table('goods_gallery') . " AS album " . $GLOBALS['album_where'];
            $res = $GLOBALS['db']->SelectLimit($sql, $page_size, ($page - 1) * $page_size);

            foreach ($res as $row) {
                if ($thumb) {
                    get_image_path($row['goods_id'], $row['img_original'], true, 'gallery', true);
                }
                if ($watermark) {
                    get_image_path($row['goods_id'], $row['img_original'], false, 'gallery', true);
                }
            }
        }
    }

    /**
     *  用新图片替换指定图片
     *
     * @access  public
     * @param string $new_image 新图片
     * @param string $old_image 旧图片
     * @param string $goods_id 商品图片
     * @param boolen $silent 是否使用静态函数
     *
     * @return void
     */
    private function replace_image($new_image, $old_image, $goods_id, $silent)
    {
        $error = false;
        if (file_exists(ROOT_PATH . $old_image)) {
            @rename(ROOT_PATH . $old_image, ROOT_PATH . $old_image . '.bak');
            if (!@rename(ROOT_PATH . $new_image, ROOT_PATH . $old_image)) {
                $error = true;
            }
        } else {
            if (!@rename(ROOT_PATH . $new_image, ROOT_PATH . $old_image)) {
                $error = true;
            }
        }
        if ($error === true) {
            if (file_exists(ROOT_PATH . $old_image . '.bak')) {
                @rename(ROOT_PATH . $old_image . '.bak', ROOT_PATH . $old_image);
            }
            $msg = sprintf($GLOBALS['_LANG']['error_pos'], $goods_id) . "\n" . sprintf($GLOBALS['_LANG']['error_rename'], $new_image, $old_image);
            if ($silent) {
                $GLOBALS['err_msg'][] = $msg;
            } else {
                return make_json_error($msg);
            }
        } else {
            if (file_exists(ROOT_PATH . $old_image . '.bak')) {
                @unlink(ROOT_PATH . $old_image . '.bak');
            }
            return;
        }
    }
}
