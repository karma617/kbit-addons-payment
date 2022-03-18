<?php

namespace app\one_pay\home;
use app\common\controller\Common;
use think\facade\Log;

/**
 * 支付回调控制器
 * @package app\one_pay\home
 */
class Callback extends Common
{
    public $callback = '';// 内部回调地址
    public $method = '';// 支付平台
    public $options = '';// 支付平台

    protected function initialize()
    {
        parent::initialize();
        if (!defined('IS_SAFE_PAY')) {
            define('IS_SAFE_PAY', true);
        }
        Log::init([
            // 日志记录级别
            'level'       => ['payment'],
            // 独立日志级别
            'apart_level' => ['payment'],
        ]);
        trace('ticket'.PHP_EOL.'form-data:'.PHP_EOL.var_export(input('post.'),true).PHP_EOL.'raw:'.PHP_EOL.var_export(file_get_contents("php://input"),true),'payment');
        $this->params = $this->request->param();
        $this->options = config('payment.' . $this->params['method']);
        $this->factory = new \kbitAddons\payment\Factory($this->params['method'], $this->options);
        
        // 判断支付宝扫码支付，是否是支付成功的回调（支付宝在用户扫码后会给一次扫码成功的回调，但此时用户还未付款）
        if (strstr($this->params['method'], 'alipay')) {
            if ($this->params['trade_status'] != 'TRADE_SUCCESS') {
                throw new \Exception("等待用户支付", 1);
            }
        }
    }

    /**
     * 同步回调
     */
    public function sync()
    {
        return self::payCallback();
    }

    /**
     * 异步回调
     */
    public function async()
    {
        return self::payCallback(true);
    }

    /**
     * 支付回调业务处理
     * @param $async bool 异步调用
     */
    private function payCallback($async = false)
    {
        // 支付回调处理，系统记录,不处理实际业务
        try {
            $params = $async === true ? $_POST : $_GET;
            $rs = $this->factory->__call( $async ? '_async' : '_sync', $params);
            // 发起支付前记录的对应的支付日志及商品快照
            $row = PayLogModel::where('order_no', $rs['out_trade_no'])->where('method', input('param.method'))->find();
            if (!$row) {
                throw new \Exception("数据不存在", 1);
            }
            
            if ($row['status'] === 2) {
                throw new \Exception($rs['out_trade_no']."已支付成功！", 1);
            }
            $_trade_no = '';
            if(strstr(input('param.method'),'alipay')){
                $_trade_no =$rs['trade_no'];
            }
            if(strstr(input('param.method'),'wechat')){
                $_trade_no =$rs['transaction_id'];
            }

            $sqlmap = [];
            $sqlmap['trade_no'] = $_trade_no;
            $sqlmap['return'] = json_encode($rs);
            $sqlmap['status'] = 2;
            if (!PayLogModel::where('id', $row['id'])->update($sqlmap)) {
                throw new \Exception("支付处理失败！", 1);
            }
            //执行设置的回调
            $payment_callback = $this->options['payment_callback'];
            if(!empty($payment_callback)){
                $result = @action($payment_callback, ['param' => $this->params], 'home');
            }
            //异步
            if ($async === true) {
                exit('success');
            }
            // 同步返回，优先跳转到指定的提示页面
            if (isset($result['url']) && !empty($result['url'])) {
                exit(header('location: '.$result['url']));
            } else {
                return $this->success('恭喜您！支付业务处理成功。', '/');
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

    /**
     * 同步退款
     */
    public function syncRefund()
    {
        // TODO
        // 返回格式：['status' => true, 'message' => '错误说明', 'url' => '支付成功后的跳转页面']
    }

    /**
     * 异步退款
     */
    public function asyncRefund()
    {
        // TODO
        // 返回格式：['status' => true, 'message' => '错误说明', 'url' => '支付成功后的跳转页面']
    }
}