<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class ShippingArea extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'shipping_area';

    public $timestamps = false;
}
