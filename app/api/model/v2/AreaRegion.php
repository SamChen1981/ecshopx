<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class AreaRegion extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'area_region';

    public $timestamps = false;

    protected $visible = [];
}
