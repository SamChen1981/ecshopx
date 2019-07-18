<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class Category extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'category';
    public $timestamps = false;
    protected $guarded = [];
}
