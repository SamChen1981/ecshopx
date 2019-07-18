<?php

namespace app\api\controller;

use think\facade\Request;

use app\api\model\v2\AreaCode;

class AreaCodeController extends Controller
{
    /**
    * POST ecapi.areacode.list
    */
    public function index(Request $request)
    {
        $model = AreaCode::getList();

        return $this->json($model);
    }
}
