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
        $this->assign('page_title', $position['title']);    // 页面标题
        $this->assign('ur_here', $position['ur_here']);  // 当前位置
        $this->assign('categories', get_categories_tree()); // 分类树
        $this->assign('helps', get_shop_help());       // 网店帮助
        $this->assign('top_goods', get_top10());           // 销售排行
        $this->assign('promotion_info', get_promotion_info());

        /* 调查 */
        $vote = get_vote();
        if (!empty($vote)) {
            $this->assign('vote_id', $vote['id']);
            $this->assign('vote', $vote['content']);
        }

        assign_dynamic('tag_cloud');

        $tags = get_tags();

        if (!empty($tags)) {
            load_helper('clips');
            color_tag($tags);
        }

        $this->assign('tags', $tags);

        return $this->display('tag_cloud.view.php');
    }
}
