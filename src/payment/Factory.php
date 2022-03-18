<?php

namespace kbitAddons\payment\payment;


class Factory
{

    public function __construct($code = '')
    {
        defined('PAY_CODE') OR define('PAY_CODE', $code);
        $this->adapter($code);
    }

    /**
     * 构造适配器
     *
     * @param string $code  支付平台code
     * @return void
     * @author 617 <email：723875993@qq.com>
     */
    public function adapter($code = '')
    {
        if (empty($code)) return false;
        
        // 获取对应支付模块配置信息
        $payment_config = config('payment' . $code);
        if (!$payment_config) {
            throw new \Exception("['.$code.']支付方式未配置相关信息！", 1);
        }
        
        $class = '\\KbitAddons\\payment\\payment\\driver\\'.$code.'\\'.$code;

        if (!class_exists($class)) {
            throw new \Exception('缺少['.$code.']支付驱动！', 1);

        }
        $payment_config['debug'] = true;
        $this->instance = new $class($payment_config);
        return $this->instance;
    }
    
    public function __call($method_name, $method_args) {
        if (method_exists($this, $method_name)) {
            return call_user_func_array(array(& $this, $method_name), [$method_args]);
        } elseif (
            !empty($this->instance)
            && ($this->instance instanceof PayMentInterFace)
            && method_exists($this->instance, $method_name) ) {
            return call_user_func_array(array(& $this->instance, $method_name), [$method_args]);
        }
    }
}