<?php

namespace kbitAddons\payment\driver\alipay;

use kbitAddons\payment\BasicAliPay;
use kbitAddons\payment\PayMentInterFace;

class alipay extends BasicAliPay implements PayMentInterFace
{
    public function __construct($options = [])
    {
        parent::__construct($options);
        //兼容手机和PC
        $this->options['method'] = request()->isMobile() ? 'alipay.trade.wap.pay' : 'alipay.trade.page.pay';
        $this->params['product_code']   = request()->isMobile() ? 'QUICK_WAP_WAY' : 'FAST_INSTANT_TRADE_PAY';
    }
    /* 支付提交接口 */
    public function _submit($options)
    {
        parent::applyData($options);
        return $this->buildPayHtml();
    }
    /* 同步通知接口 */
    public function _sync($param)
    {
        return parent::notify($param);
    }
    /* 异步通知接口 */
    public function _async($param)
    {
        return parent::notify($param);
    }
    /* 退款提交接口 */
    public function _refundSubmit($param)
    {
        return parent::refund($param);
    }
    /* 同步退款通知接口 */
    public function _syncRefund($param)
    {
    }
    /* 异步退款通知接口 */
    public function _asyncRefund($param)
    {
    }
}
