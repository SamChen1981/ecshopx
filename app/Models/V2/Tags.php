<?php

namespace app\api\model\v2;

use app\api\model\BaseModel;

class Tags extends BaseModel
{
    protected $table = 'tag';

    public $timestamps = false;

    protected $visible = ['id', 'name', 'created_at', 'updated_at'];

    protected $appends = ['id', 'name', 'created_at', 'updated_at'];

    protected $guarded = [];


    public function getIdAttribute()
    {
        return $this->tag_id;
    }

    public function getNameAttribute()
    {
        return $this->tag_words;
    }

    public function getCreatedatAttribute()
    {
        return time();
    }

    public function getUpdatedatAttribute()
    {
        return time();
    }
}
