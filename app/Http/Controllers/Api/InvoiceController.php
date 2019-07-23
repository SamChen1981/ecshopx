<?php

namespace app\api\controller;

use app\api\model\v2\Invoice;
use think\facade\Request;

class InvoiceController extends Controller
{

    /**
     * POST ecapi.invoice.type.list
     */
    public function type(Request $request)
    {
        $data = Invoice::getTypeList();
        return $this->json($data);
    }

    /**
     * POST ecapi.invoice.content.list
     */
    public function content(Request $request)
    {
        $data = Invoice::getContentList();
        return $this->json($data);
    }

    /**
     * POST ecapi.invoice.status.get
     */
    public function status(Request $request)
    {
        $data = Invoice::getStatus();
        return $this->json($data);
    }
}
