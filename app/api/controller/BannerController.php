<?php

namespace app\api\controller;

use think\facade\Request;

use app\api\model\v2\Banner;

class BannerController extends Controller
{

    /**
    * POST ecapi.banner.list
    */
    public function index(Request $request)
    {
        $model = Banner::getList();

        return $this->json($model);
    }
}
