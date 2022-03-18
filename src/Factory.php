<?php

namespace kbitAddons\payment;

/**
 * 支付工厂入口
 * new一个Factory即可纵享丝滑
 *
 * @author 617 <email：723875993@qq.com>
 */
class Factory
{
    public $code = '';
    public $payment_config;

    public function __construct(string $code = '', array $payment_config)
    {
        if (empty($code) || empty($payment_config)) {
            throw new \Exception('支付驱动名或支付配置不能为空！', 1);
        }
        $this->code = strtolower($code);
        $this->payment_config = $payment_config;
        defined('PAY_CODE') or define('PAY_CODE', $this->code);
        $this->adapter();
    }

    /**
     * 构造适配器
     *
     * @return void
     * @author 617 <email：723875993@qq.com>
     */
    public function adapter()
    {
        $class = '\\kbitAddons\\payment\\driver\\' . $this->code . '\\' . $this->code;
        if (!class_exists($class)) {
            throw new \Exception('缺少[' . $this->code . ']支付驱动！', 1);
        }
        $this->instance = new $class($this->payment_config);
        return $this->instance;
    }

    public function __call($method_name, $method_args)
    {
        if (method_exists($this, $method_name)) {
            return call_user_func_array(array(&$this, $method_name), [$method_args]);
        } elseif (
            !empty($this->instance)
            && ($this->instance instanceof PayMentInterFace)
            && method_exists($this->instance, $method_name)
        ) {
            return call_user_func_array(array(&$this->instance, $method_name), [$method_args]);
        }
    }
}
