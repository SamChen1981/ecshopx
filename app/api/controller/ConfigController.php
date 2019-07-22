<?php

namespace app\api\controller;

use app\api\model\v2\Configs;

class ConfigController extends Controller
{
    public function index()
    {
        $rules = [
            'url' => 'string',
        ];
        if ($error = $this->validateInput($rules)) {
            return $error;
        }
        $data = Configs::getList($this->validated);
        return $this->json($data);
    }

    public function wechat()
    {
        $rules = [
            'url' => 'string',
        ];
        if ($error = $this->validateInput($rules)) {
            return $error;
        }
        $data = Configs::getWeChat($this->validated);
        return $this->json($data);
    }
}
