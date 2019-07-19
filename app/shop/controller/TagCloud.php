<?php

namespace app\shop\controller;

/**
 * 标签云
 */
class TagCloud extends Init
{
    public function index()
    {
        assign_template();
        $position = assign_ur_here(0, $GLOBALS['_LANG']['tag_cloud']);
        $GLOBALS['smarty']->assign('page_title', $position['title']);    // 页面标题
        $GLOBALS['smarty']->assign('ur_here', $position['ur_here']);  // 当前位置
        $GLOBALS['smarty']->assign('categories', get_categories_tree()); // 分类树
        $GLOBALS['smarty']->assign('helps', get_shop_help());       // 网店帮助
        $GLOBALS['smarty']->assign('top_goods', get_top10());           // 销售排行
        $GLOBALS['smarty']->assign('promotion_info', get_promotion_info());

        /* 调查 */
        $vote = get_vote();
        if (!empty($vote)) {
            $GLOBALS['smarty']->assign('vote_id', $vote['id']);
            $GLOBALS['smarty']->assign('vote', $vote['content']);
        }

        assign_dynamic('tag_cloud');

        $tags = get_tags();

        if (!empty($tags)) {
            load_helper('clips');
            color_tag($tags);
        }

        $GLOBALS['smarty']->assign('tags', $tags);

        $GLOBALS['smarty']->display('tag_cloud.dwt');
    }
}
