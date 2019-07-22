<?php

namespace app\api\controller;

use app\api\model\v2\Shipping;
use app\api\model\v2\Features;

class ShippingController extends Controller
{
    /**
     * POST ecapi.shipping.vendor.list
     */
    public function index()
    {
        $rules = [
            'shop' => 'integer|min:1',
            'address' => 'required|integer|min:1',
            'products' => 'required|string',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Shipping::findAll($this->validated);

        return $this->json($data);
    }

    /**
     * POST ecapi.shipping.status.get
     */
    public function info()
    {
        $rules = [
            'order_id' => 'required|int',
        ];

        if ($res = Features::check('logistics')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Shipping::getDeliveyInfo($this->validated);

        return $this->json($data);
    }
}
