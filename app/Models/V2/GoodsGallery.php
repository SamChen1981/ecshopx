<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class GoodsGallery extends BaseModel
{
    protected $table = 'goods_gallery';
    public $timestamps = false;

    /**
     * 商品图片
     * @param  [type] $id [description]
     * @return [type]           [description]
     */
    public static function getPhotosById($id)
    {
        $goods_images = [];

        $model = self::where('goods_id', $id)->orderBy('sort_order')->get();

        if (!$model->IsEmpty()) {
            foreach ($model as $value) {
                $photo = formatPhoto($value->img_url, $value->thumb_url);
                if (is_array($photo)) {
                    $goods_images[] = $photo;
                }
            }
        }

        return $goods_images;
    }


    public static function getCategoryPhoto($cat_id)
    {
        //获取分类ids
        $cat_ids = GoodsCategory::where('parent_id', $cat_id)->orWhere('cat_id', $cat_id)->lists('cat_id')->toArray();
        if (!empty($cat_ids)) {
            $goods_id = Goods::whereIn('cat_id', $cat_ids)->where(['is_delete' => 0])->orderBy('is_hot', 'DESC')->first();
            if ($goods_id) {
                return formatPhoto($goods_id->goods_img);
            }
        }

        return null;
    }
}
