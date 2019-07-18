<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class Avatar extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'avatar';
    public $timestamps = false;
    protected $guarded = [];
}
