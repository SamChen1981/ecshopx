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
            $this->assign('ur_here', $ur_here);
            $this->assign('cat_list', cat_list());
            $this->assign('brand_list', get_brand_list());
            $this->assign('intro_list', $GLOBALS['_LANG']['intro']);
            $this->assign('url', $GLOBALS['ecs']->url());
            $this->assign('lang_list', $lang_list);

            /* 显示模板 */
            assign_query_info();
            return $this->fetch('gen_goods_script');
        }
    }
}
