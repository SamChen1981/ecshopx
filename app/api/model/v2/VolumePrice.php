<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class VolumePrice extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'volume_price';

    public $timestamps = false;

    protected $visible = ['volume_number', 'volume_price'];

    // protected $appends = ['volume_number', 'volume_price'];

    protected $guarded = [];
}
