<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class Sns extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'sns';
    protected $primaryKey = 'user_id';
    public $timestamps = true;
}
