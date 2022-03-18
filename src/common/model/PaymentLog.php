<?php

namespace kbitAddons\payment\common\model;

use think\Model;
/**
 * 支付日志
 *
 * @author 617 <email：723875993@qq.com>
 */
class PaymentLog extends Model
{
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $name = 'payment_log';
    protected $pk = 'payment_id';
}