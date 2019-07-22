<?php

namespace app\console\controller;

/**
 * 生成显示商品的js代码
 */
class GenGoodsScript extends Init
{
    public function index()
    {


        /*------------------------------------------------------ */
        //-- 生成代码
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'setup') {
            /* 检查权限 */
            admin_priv('gen_goods_script');

            /* 编码 */
            $lang_list = array(
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            );

            /* 参数赋值 */
            $ur_here = $GLOBALS['_LANG']['16_goods_script'];
            $GLOBALS['smarty']->assign('ur_here', $ur_here);
            $GLOBALS['smarty']->assign('cat_list', cat_list());
            $GLOBALS['smarty']->assign('brand_list', get_brand_list());
            $GLOBALS['smarty']->assign('intro_list', $GLOBALS['_LANG']['intro']);
            $GLOBALS['smarty']->assign('url', $GLOBALS['ecs']->url());
            $GLOBALS['smarty']->assign('lang_list', $lang_list);

            /* 显示模板 */
            assign_query_info();
            return $GLOBALS['smarty']->display('gen_goods_script.view.php');
        }
    }
}
