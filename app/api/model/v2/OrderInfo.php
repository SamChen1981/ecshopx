<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

use app\api\library\Token;

class OrderInfo extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'order_info';
    public $timestamps = false;
}
