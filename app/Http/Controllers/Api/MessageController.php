<?php

namespace app\api\controller;

use app\api\model\v2\Push;
use app\api\model\v2\Device;
use think\facade\Request;

class MessageController extends Controller
{

    /**
     * POST ecapi.message.system.list
     */
    public function system(Request $request)
    {
        $rules = [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Push::getSystemList($this->validated);

        return $this->json($model);
    }

    /**
     * POST ecapi.message.order.list
     */
    public function order(Request $request)
    {
        $rules = [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Push::getOrderList($this->validated);

        return $this->json($model);
    }

    /**
     * POST ecapi.message.unread
     */
    public function unread(Request $request)
    {
        $rules = [
            'after' => 'required|string',
            'type' => 'int'
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Push::unread($this->validated);

        return $this->json($model);
    }

    /**
     * POST ecapi.push.update
     */
    public function updateDeviceId(Request $request)
    {
        $rules = [
            'device_id' => 'required|string'
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Device::updateDevice($this->validated);
        return $this->json($model);
    }
}
