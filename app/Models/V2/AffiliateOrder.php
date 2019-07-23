<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class AffiliateOrder extends BaseModel
{
    protected $table = 'order_info';
    protected $primaryKey = 'order_id';
    public $timestamps = false;
    protected $guarded = [];
}
