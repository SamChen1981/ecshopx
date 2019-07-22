<?php

namespace app\shop\controller;

/**
 * 商品相册
 */
class Gallery extends Init
{
    public function index()
    {


        /* 参数 */
        $_REQUEST['id'] = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0; // 商品编号
        $_REQUEST['img'] = isset($_REQUEST['img']) ? intval($_REQUEST['img']) : 0; // 图片编号

        /* 获得商品名称 */
        $sql = 'SELECT goods_name FROM ' . $GLOBALS['ecs']->table('goods') . "WHERE goods_id = '$_REQUEST[id]'";
        $goods_name = $GLOBALS['db']->getOne($sql);

        /* 如果该商品不存在，返回首页 */
        if ($goods_name === false) {
            return $this->redirect('/');


        }

        /* 获得所有的图片 */
        $sql = 'SELECT img_id, img_desc, thumb_url, img_url' .
            ' FROM ' . $GLOBALS['ecs']->table('goods_gallery') .
            " WHERE goods_id = '$_REQUEST[id]' ORDER BY img_id";
        $img_list = $GLOBALS['db']->getAll($sql);

        $img_count = count($img_list);

        $gallery = array('goods_name' => htmlspecialchars($goods_name, ENT_QUOTES), 'list' => array());
        if ($img_count == 0) {
            /* 如果没有图片，返回商品详情页 */
            return $this->redirect('goods.php?id=' . $_REQUEST['id'] . "');
            
        } else {
            foreach ($img_list as $key => $img) {
                $gallery['list'][] = array(
                    'gallery_thumb' => get_image_path($_REQUEST['id'], $img_list[$key]['thumb_url'], true, 'gallery'),
                    'gallery' => get_image_path($_REQUEST['id'], $img_list[$key]['img_url'], false, 'gallery'),
                    'img_desc' => $img_list[$key]['img_desc']
                );
            }
        }

        $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
        $GLOBALS['smarty']->assign('watermark', str_replace('../', './', $GLOBALS['_CFG']['watermark']));
        $GLOBALS['smarty']->assign('gallery', $gallery);
        return $GLOBALS['smarty']->display('gallery.view.php');
    }
}
