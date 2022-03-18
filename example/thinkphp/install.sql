
CREATE TABLE `one_one_pay_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户标识',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1付款，2退款',
  `product_id` varchar(50) DEFAULT '' COMMENT '产品ID[选填]',
  `order_no` varchar(32) NOT NULL DEFAULT '0' COMMENT '商户订单号',
  `refund_no` varchar(64) DEFAULT '' COMMENT '退款单号',
  `trade_no` varchar(32) NOT NULL DEFAULT '' COMMENT '支付平台交易号',
  `method` varchar(50) NOT NULL COMMENT '支付方式code',
  `bank` varchar(50) NOT NULL DEFAULT '' COMMENT '支付银行code',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `request` text NOT NULL COMMENT '请求数据',
  `return` text COMMENT '返回数据',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态(0失败，1待处理，2成功)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='[pay] 支付日志';