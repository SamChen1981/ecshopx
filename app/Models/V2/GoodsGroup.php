<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class GoodsGroup extends BaseModel
{
    protected $table = 'group_goods';

    public $timestamps = false;

    protected $visible = ['id', 'name', 'photo', 'price', 'created_at', 'updated_at'];

    protected $appends = ['id', 'name', 'photo', 'price', 'created_at', 'updated_at'];

    protected $guarded = [];

    // protected $with = ['goods'];


    public function getIdAttribute()
    {
        return $this->goods_id;
    }

    public function getPhotoAttribute()
    {
        return GoodsGallery::getPhotosById($this->goods_id);
    }

    public function getNameAttribute()
    {
        return Goods::where('goods_id', $this->goods_id)->value('goods_name');
    }

    public function getPriceAttribute()
    {
        return $this->goods_price;
    }

    public function getCreatedatAttribute()
    {
        return time();
    }

    public function getUpdatedatAttribute()
    {
        return time();
    }

    public function intro($id)
    {
        return Goods::getIntro($id);
    }

    public static function getAccessories($parent_id)
    {
        if ($model = self::where('parent_id', $parent_id)->pluck('goods_id')) {
            return $model;
        }
        return [];
    }
    // public function goods()
    // {
    //     return $this->belongsTo('app\api\model\v2\Goods','goods_id','goods_id');
    // }
}
