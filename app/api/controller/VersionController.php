<?php

namespace app\api\controller;

use app\api\model\v2\Version;
use think\facade\Request;

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
