<?php

namespace app\api\controller;

use app\api\model\v2\Region;
use think\facade\Request;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        $response = Region::getList();
        return $this->json($response);
    }
}
