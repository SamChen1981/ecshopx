<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class OrderReview extends BaseModel
{
    protected $table = 'order_review';
    public $timestamps = false;
    protected $primaryKey = 'user_id';
    protected $guarded = [];

    public static function toUpdate($uid, $order_id, $goods_id)
    {
        return self::updateOrCreate(['user_id' => $uid, 'order_id' => $order_id], ['user_id' => $uid, 'order_id' => $order_id, 'goods_id' => $goods_id]);
    }

    public static function isReviewed($uid, $order_id, $goods_id)
    {
        if ($model = self::where('user_id', $uid)->where('order_id', $order_id)->where('goods_id', $goods_id)->first()) {
            return true;
        }

        return false;
    }

    public static function isReviewedWithGoods($uid, $order_id, $goods_id)
    {
        if ($model = self::where(['user_id' => $uid, 'order_id' => $order_id, 'goods_id' => $goods_id])->first()) {
            return true;
        }

        return false;
    }
}
