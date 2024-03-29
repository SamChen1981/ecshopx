<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class FavourableActivity extends BaseModel
{
    protected $table = 'favourable_activity';
    public $timestamps = false;

    const    FAT_GOODS = 0; // 送赠品或优惠购买
    const    FAT_PRICE = 1; // 现金减免
    const    FAT_DISCOUNT = 2; // 价格打折优惠

    /* 优惠活动的优惠范围 */
    const   FAR_ALL = 0; // 全部商品
    const   FAR_CATEGORY = 1; // 按分类选择
    const   FAR_BRAND = 2; // 按品牌选择
    const   FAR_GOODS = 3; // 按商品选择

    public static function getPromoByGoods($goods_id, $cat_id, $brand_id, $without_userrank = false)
    {
        $data = [];
        $now = time();

        $cat_parent_id = Category::where('cat_id', $cat_id)->value('parent_id');
        $sql = '';

        if (!$without_userrank) {
            $user_rank = UserRank::getUserRankByUid();
            if (!empty($user_rank)) {
                $sql = ' AND FIND_IN_SET(' . $user_rank['rank_id'] . ', `user_rank`)';
            }
        }

        $model = DB::connection('shop')->table('favourable_activity')->whereRaw('(`start_time` <= ' . $now . ' AND `end_time` >= ' . $now . ') AND (`act_range` = 0 OR (`act_range` = 1 AND FIND_IN_SET(' . $cat_id . ',`act_range_ext`) OR FIND_IN_SET(' . $cat_parent_id . ',`act_range_ext`)) OR (`act_range` = 2 AND FIND_IN_SET(' . $brand_id . ',`act_range_ext`)) OR (`act_range` = 3 AND FIND_IN_SET(' . $goods_id . ',`act_range_ext`)))' . $sql)->get();

        if (!empty($model)) {
            foreach ($model as $key => $value) {
                switch ($value->act_type) {
                    case 0:
                        // 满赠
                        $data[$key]['promo'] = '满¥' . $value->min_amount . '送赠品';
                        break;

                    case 1:
                        // 满减
                        $data[$key]['promo'] = '满¥' . $value->min_amount . '减¥' . $value->act_type_ext;
                        break;

                    case 2:
                        // 满折
                        $data[$key]['promo'] = '满¥' . $value->min_amount . '打' . ($value->act_type_ext / 10) . '折';
                        break;

                    default:
                        $data[$key]['promo'] = $value->act_name;
                        break;
                }

                $data[$key]['name'] = $value->act_name;
                $data[$key]['start_at'] = $value->start_time;
                $data[$key]['end_at'] = $value->end_time;
            }
        }
        return $data;
    }

    public static function discount($order_price)
    {
        //初始化，第一次有效，下次失效
        static $result = array();
        static $id_array = array();
        //判断优惠是否存在, 同时每条优惠只进行一次操作
        $model = FavourableActivity::where('min_amount', '<', $order_price)->where('start_time', '<=', time())->where('end_time', '>=', time())->orderBy('min_amount', 'DESC')->where('act_type', self::FAT_DISCOUNT)->whereNotIn('act_id', $id_array)->first();
        if ($model) {
            //1.优惠id保存数组
            Log::debug("优惠id" . $model->act_id);
            $id_array[] = $model->act_id;
            //2.折扣价数组 = 原价* （100 - 折扣），保存数组
            $result[] = $order_price * (100 - $model->act_type_ext) / 100;
            //原价乘折扣，作为下次订单原价
            $order_price = $order_price * ($model->act_type_ext / 100);
            self::discount($order_price);
        }

        return $result;
    }
}
