<?php

namespace app\api\controller;

use think\facade\Request;

use app\api\model\v2\BonusType;
use app\api\model\v2\UserBonus;
use app\api\model\v2\Features;
use app\api\library\Token;

class CashGiftController extends Controller
{

    /**
    * POST ecapi.cashgift.list
    */
    public function index(Request $request)
    {
        $rules = [
            'page'      => 'required|integer|min:1',
            'per_page'  => 'required|integer|min:1',
            'status'    => 'required|integer',
        ];

        if ($res = Features::check('cashgift')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = BonusType::getListByUser($this->validated);

        return $this->json($model);
    }

    /**
    * POST ecapi.cashgift.available
    */
    public function available(Request $request)
    {
        $rules = [
            'page'          => 'required|integer|min:1',
            'per_page'      => 'required|integer|min:1',
            'total_price'   => 'required|numeric|min:0',
        ];

        if ($res = Features::check('cashgift')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = BonusType::getAvailableListByUser($this->validated);

        return $this->json($model);
    }

    /**
    * POST ecapi.cashgift.add
    */
    public function add(Request $request)
    {
        $rules = [
            'bonus_sn'  => 'required|integer',
        ];
        if ($res = Features::check('cashgift')) {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }
        $result = UserBonus::addBonus($this->validated);

        return $this->json($result);
    }
}
