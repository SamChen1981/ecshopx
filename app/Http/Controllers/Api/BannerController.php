<?php

namespace app\api\controller;

use app\api\model\v2\Banner;
use think\facade\Request;

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
