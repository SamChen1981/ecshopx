<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;
use app\api\library\Token;
use DB;

class AffiliateOrder extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'order_info';
    protected $primaryKey = 'order_id';
    public $timestamps = false;
    protected $guarded = [];
}
