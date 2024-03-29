<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class GoodsExtendCategory extends BaseModel
{
    protected $table = 'goods_cat';
    public $timestamps = false;


    protected $visible = ['id', 'cat_id'];

    protected $appends = ['id', 'cat_id'];

    protected $guarded = [];


    public function getIdAttribute()
    {
        return $this->goods_id;
    }

    public function getCatIdAttribute()
    {
        return $this->cat_id;
    }


    //获得所有扩展分类属于指定分类的所有商品ID
    public static function get_extension_goods($cat = array())
    {
        return self::whereIn('goods_cat.cat_id', $cat)->lists('goods_id')->toArray();
    }
}
