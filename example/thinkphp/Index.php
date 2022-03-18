<?php

namespace app\one_pay\home;

use app\common\controller\Common;
use app\one_pay\model\OnePayLog as PayLogModel;

/**
 * 支付请求控制器
 * @package app\one_pay\home
 */
class Index extends Common
{
    protected function initialize()
    {
        parent::initialize();
        if (!defined('IS_SAFE_PAY')) {
            define('IS_SAFE_PAY', true);
        }
    }

    /**
     * 发起支付请求
     *
     * @param string $method *[wechat_qr,wechat_js,wechat_mini,wechat_mweb,wechat_app,alipay,alipay_app,alipay_scan]
     * @param string $order_no 订单号
     * @param integer $money 金额 微信需要*100 生产环境需要高精度处理 intval(strval($val*100)); 
     * @param string $body 支付宝描述 微信描述
     * @param string $subject 支付宝标题 转换为微信detail
     * @param integer $uid 用户标识
     * @param string $back_url 返回地址
     * @return void
     * @author Leo <13708867890>
     * @since 2020-12-24 20:06:44
     */
    public function apply($method = 'wechat_qr', $order_no = '', $money = 1, $body = '', $subject = '', $uid = 1000001, $back_url = '')
    {
        $param             = [];
        $param['method']   = $method;
        $param['money']    = $money;
        $param['uid']      = $uid;
        $param['order_no'] = empty($order_no) ? order_number() : $order_no;
        $param['body']     = empty($body) ? '支付测试' . $money :  $body;
        $param['subject']  = empty($subject) ? $param['body'] : $subject;

        // 保存请求数据
        $_data                = [];
        $_data['order_no']    = $param['order_no'];
        $_data['type']        = 1;
        $_data['method']      = $method;
        $_data['money']       = $param['money'];
        $_data['bank']        = isset($param['bank']) ? $param['bank'] : '';
        $_data['request']     = json_encode($param);
        $_data['uid']         =  $param['uid'];

        $res = PayLogModel::create($_data);
        
        //实例化支付
        $options = config('payment.' . $method);
        $factory = new \kbitAddons\payment\Factory($method, $options);
        if(strstr($method,'alipay')){
            $rs = $factory->__call('_submit', [
                'out_trade_no' => $param['order_no'],
                'total_amount' => $param['money'],
                'body'         => $param['body'],
                'subject'      => $param['subject'],
            ]);
        }
        if(strstr($method,'wechat')){
            $rs = $factory->__call('_submit', [
                'out_trade_no' => $param['order_no'],
                'total_fee' => $param['money']*100,
                'body'         => $param['body'],
                'detail'      => $param['subject'],
            ]);
        }

        //以下方式返回参数到前端拼接
        if (in_array($method, ['wechat_qr','wechat_mweb','wechat_js', 'wechat_app', 'alipay_app','alipay_scan'])) {
            // wechat_js 
            // return $factory->jsapiParams($rs['prepay_id']);
            // wechat_app 
            // return $factory->appParams($rs['prepay_id']);

            return json($rs);
        }
       
        //直接输出 支付宝表单
        echo $rs;
    }

    /**
     * 检查订单支付状态
     */
    public function checkStatus()
    {
        $orderNo = $this->request->param('order_no');

        if (PayLogModel::where('order_no', $orderNo)->where('status', '=', 2)->find()) {
            return $this->success('支付成功');
        }
        return $this->error('待支付');
    }

    public function refund()
    {
        $method = 'wechat_qr';
        $options = config('payment.' . $method);
        $factory = new \kbitAddons\payment\Factory($method, $options);
        $rs = $factory->__call('_refundSubmit', [
            'out_trade_no' => '2020122620073705186',
            'out_refund_no'=>order_number(),
            'total_fee' => 10,
            'refund_fee' => 10,
        ]);
        
        var_dump($rs);
    }
}
