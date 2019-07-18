<?php

namespace app\api\controller;

use think\facade\Request;

use app\api\model\v2\Region;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        $response = Region::getList();
        return $this->json($response);
    }
}
