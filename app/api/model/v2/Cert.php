<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class Cert extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'cert';
    public $timestamps = true;
}
