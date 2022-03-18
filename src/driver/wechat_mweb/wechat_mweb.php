<?php

namespace kbitAddons\payment\driver\wechat_mweb;

use kbitAddons\payment\BasicWePay;
use kbitAddons\payment\PayMentInterFace;

class wechat_mweb extends BasicWePay implements PayMentInterFace
{
    public function __construct($options = [])
    {
        parent::__construct($options);
    }
    /* 支付提交接口 */
    public function _submit($param)
    {
        // https://pay.weixin.qq.com/wiki/doc/api/H5.php
        $param['trade_type'] = 'MWEB';
        $param['spbill_create_ip'] = get_client_ip();
        $param['scene_info'] = '{"h5_info": {"type":"Wap","wap_url": "' . get_domain() . '","wap_name": "' . config('base.site_name') . '"}}';


        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        return $this->callPostApi($url, $param, false, 'MD5');
    }
    /* 同步通知接口 */
    public function _sync($param)
    {
    }
    /* 异步通知接口 */
    public function _async($param)
    {
        return parent::getNotify($param);
    }
    /* 退款提交接口 */
    public function _refundSubmit($param)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        return $this->callPostApi($url, $param, true);
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
