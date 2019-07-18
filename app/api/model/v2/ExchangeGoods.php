<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class ExchangeGoods extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'exchange_goods';
}
