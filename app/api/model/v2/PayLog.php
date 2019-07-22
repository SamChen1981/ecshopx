<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class PayLog extends BaseModel
{
    protected $table = 'pay_log';

    public $timestamps = false;
}
