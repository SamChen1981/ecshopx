<?php

namespace app\api\controller;

use app\api\model\v2\Ad;

class AdController extends Controller
{
    /**
     * POST ecapi.ad.list 广告列表
     *
     * @return \App\Http\Controllers\json|\App\Http\Controllers\response
     */
    public function ad_list()
    {
        $rules = [
            'ad_postions' => 'required|string',
        ];
        if ($error = $this->validateInput($rules)) {
            return $error;
        }
        $data = Ad::getlist(explode(',', $this->validated['ad_postions']));

        return $this->json(['ad' => $data]);
    }
}
