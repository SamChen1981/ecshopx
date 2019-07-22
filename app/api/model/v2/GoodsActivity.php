<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class GoodsActivity extends BaseModel
{
    protected $table = 'goods_activity';

    public $timestamps = false;

    protected $visible = ['promo', 'name'];

    protected $appends = ['promo', 'name'];

    protected $guarded = [];
}
