<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class Category extends BaseModel
{
    protected $table = 'category';
    public $timestamps = false;
    protected $guarded = [];
}
