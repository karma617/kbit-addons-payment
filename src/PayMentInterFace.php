<?php

namespace kbitAddons\payment;
/**
 * 接口类
 *
 * @author 617 <email：723875993@qq.com>
 */
interface PayMentInterFace
{
    /**
     * 支付提交接口
     *
     * @param [type] $param
     * @return void
     * @author 617 <email：723875993@qq.com>
     */
    public function _submit($param);
    /**
     * 同步通知接口
     *
     * @param [type] $param
     * @return void
     * @author 617 <email：723875993@qq.com>
     */
    public function _sync($param);
    /**
     * 异步通知接口
     *
     * @param [type] $param
     * @return void
     * @author 617 <email：723875993@qq.com>
     */
    public function _async($param);
    /**
     * 退款提交接口
     *
     * @param [type] $param
     * @return void
     * @author 617 <email：723875993@qq.com>
     */
    public function _refundSubmit($param);
    /**
     * 同步退款通知接口
     *
     * @param [type] $param
     * @return void
     * @author 617 <email：723875993@qq.com>
     */
    public function _syncRefund($param);
    /**
     * 异步退款通知接口
     *
     * @param [type] $param
     * @return void
     * @author 617 <email：723875993@qq.com>
     */
    public function _asyncRefund($param);
}
