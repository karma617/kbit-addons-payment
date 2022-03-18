<?php

namespace kbitAddons\payment\common\driver\wechat_mini;
use kbitAddons\payment\common\BasicWePay;
use kbitAddons\payment\common\PayMentInterFace;

class wechat_mini extends BasicWePay implements PayMentInterFace
{
    public function __construct($options = [])
    {
        parent::__construct($options);
    }
    
    /* 支付提交接口 */
    public function _submit($param){
        // https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php
        $param['trade_type'] = 'JSAPI';
        $param['spbill_create_ip'] = get_client_ip();
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        return $this->callPostApi($url, $param, false, 'MD5');
    }
    /* 同步通知接口 */
    public function _sync($param){
    }
    /* 异步通知接口 */
    public function _async($param){
        return parent::getNotify($param);
    }
    /* 退款提交接口 */
    public function _refundSubmit($param){
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        return $this->callPostApi($url, $param, true);
    }
    /* 同步退款通知接口 */
    public function _syncRefund($param){

    }
    /* 异步退款通知接口 */
    public function _asyncRefund($param){

    }
}
