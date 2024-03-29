<?php

namespace app\api\controller;

use app\api\model\v2\ShopConfig;
use app\api\model\v2\Configs;

class SiteController extends Controller
{
    //POST  ecapi.site.get
    public function index()
    {
        return $this->json(ShopConfig::getSiteInfo());
    }

    //POST  ecapi.site.configs
    public function configs()
    {
        $rules = [
            'key' => 'required|string',// 要获取的配置项name数组
        ];
        if ($error = $this->validateInput($rules)) {
            return $error;
        }
        $response = ShopConfig::getConfigs($this->validated);
        return $this->json($response);
    }

    //GET  ecapi.wxa.qrcode
    public function wxQrcode()
    {
        header("Content-type: image/jpeg");

        $wxa = Configs::getWxQrcode();
        Log::info('生成推广二维码结果' . json_encode($wxa));
        echo $wxa;

        return;
    }
}
