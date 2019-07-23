<?php

namespace app\api\controller;

use app\api\model\v2\AreaCode;
use think\facade\Request;

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
