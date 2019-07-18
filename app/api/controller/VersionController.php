<?php

namespace app\api\controller;

use think\facade\Request;
use app\api\model\v2\Version;

class VersionController extends Controller
{
    /**
     * POST ecapi.version.check
     */
    public function check(Request $request)
    {
        $data = Version::checkVersion();
        return $this->json($data);
    }
}
