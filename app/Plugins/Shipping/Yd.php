<?php

namespace app\common\modules\shipping;

load_lang('shipping/yd');

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    load_lang('admin/shipping');

    $i = (isset($modules)) ? count($modules) : 0;

    /* 配送方式插件的代码必须和文件名保持一致 */
    $modules[$i]['code'] = basename(__FILE__, '.php');

    $modules[$i]['version'] = '1.0.0';

    /* 配送方式的描述 */
    $modules[$i]['desc'] = 'yd_desc';

    /* 配送方式是否支持货到付款 */
    $modules[$i]['cod'] = false;

    /* 插件的作者 */
    $modules[$i]['author'] = 'ECSHOP TEAM';

    /* 插件作者的官方网站 */
    $modules[$i]['website'] = 'http://www.ecshop.com';

    /* 配送接口需要的参数 */
    $modules[$i]['configure'] = array(
        array('name' => 'item_fee', 'value' => 15), /* 单件商品的配送费用 */
        array('name' => 'base_fee', 'value' => 15), /* 1000克以内的价格           */
        array('name' => 'step_fee', 'value' => 10),  /* 续重每1000克增加的价格 */
    );

    /* 模式编辑器 */
    $modules[$i]['print_model'] = 2;

    return;
}

/**
 * 韵达快递费用计算方式:
 */
class Yd
{
    /**
     * 配置信息参数
     */
    public $configure;

    /**
     * 构造函数
     *
     * @param: $configure[array]    配送方式的参数的数组
     *
     * @return null
     */
    public function __construct($cfg = array())
    {
        foreach ($cfg as $key => $val) {
            $this->configure[$val['name']] = $val['value'];
        }
    }

    /**
     * 计算订单的配送费用的函数
     *
     * @param float $goods_weight 商品重量
     * @param float $goods_amount 商品金额
     * @param float $goods_amount 商品件数
     * @return  decimal
     */
    public function calculate($goods_weight, $goods_amount, $goods_number)
    {
        if ($this->configure['free_money'] > 0 && $goods_amount >= $this->configure['free_money']) {
            return 0;
        } else {
            @$fee = $this->configure['base_fee'];
            $this->configure['fee_compute_mode'] = !empty($this->configure['fee_compute_mode']) ? $this->configure['fee_compute_mode'] : 'by_weight';

            if ($this->configure['fee_compute_mode'] == 'by_number') {
                $fee = $goods_number * $this->configure['item_fee'];
            } else {
                if ($goods_weight > 1) {
                    $fee += (ceil(($goods_weight - 1))) * $this->configure['step_fee'];
                }
            }

            return $fee;
        }
    }

    /**
     * 查询快递状态
     *
     * @access  public
     * @param string $invoice_sn 发货单号
     * @return  string  查询窗口的链接地址
     */
    public function query($invoice_sn)
    {
        $str = '<form style="margin:0px" methods="post" ' .
            'action="http://115.238.100.211:8081/result.aspx" name="queryForm_' . $invoice_sn . '" target="_blank">' .
            '<input type="hidden" name="wen" value="' . str_replace("<br>", "\n", $invoice_sn) . '" />' .
            '<a href="javascript:document.forms[\'queryForm_' . $invoice_sn . '\'].submit();">' . $invoice_sn . '</a>' .
            '</form>';

        return $str;
    }
}
