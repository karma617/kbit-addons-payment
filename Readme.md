## 介绍

该插件为支付宝和微信支付聚合类，所有的支付只需要new一个对象即可纵享丝滑，接入简单，使用方便。

附带Thinkphp使用例程。

测试运行环境：php >= 7.3



### 已支持的支付驱动



> wechat_qr			微信扫码
>
> wechat_js			 微信jsapi
>
> wechat_mini		微信小程序
>
> wechat_mweb	 微信H5
>
> wechat_app		 微信app
>
> alipay					支付宝即时到帐
>
> alipay_app		   支付宝app
>
> alipay_scan		  支付宝当面付



### 配置项字段说明

- 支付宝

  > app_id	应用ID
  >
  > aes	AES密钥
  >
  > private_key	商户私钥
  >
  > public_key	支付宝公钥
  >
  > notify_url	异步回调地址
  >
  > return_url	同步跳转地址
  >
  > payment_callback	支付回调方法（thinkphp框架内：支付成功后执行，格式：模块/控制器/方法）
  >
  > refund_callback	退款回调方法（thinkphp框架内：退款成功后执行，格式：模块/控制器/方法）

  

- 微信

  > appid	AppId
  >
  > secret	Secret
  >
  > mch_id	支付商户号
  >
  > key	API密钥
  >
  > cert_pem	证书文件正文（不含头尾）
  >
  > key_pem	证书秘钥正文（不含头尾）
  >
  > notify_url	异步回调地址
  >
  > payment_callback	支付回调方法（thinkphp框架内：支付成功后执行，格式：模块/控制器/方法）
  >
  > refund_callback	退款回调方法（thinkphp框架内：退款成功后执行，格式：模块/控制器/方法）
  
  

### 使用方法



> // new 一个工厂类，传入支付模块名称和配置信息
>
> $options = [
> 	'appid' => ''
> 	'mch_id' => '',
> 	'key' => '',
> 	'cert_pem' => '',
> 	'key_pem' => '',
> 	'notify_url' => '',
> 	'payment_callback' => '',
> 	'refund_callback' => '',
> ];
>
> $factory = new \kbitAddons\payment\Factory('wechat_js', $options);
>
> // 执行对应方法，传入相关参数即可
>
> $rs = $factory->__call('_submit', [
>
> ​      'out_trade_no' => $param['order_no'],
>
> ​      'total_fee' => $param['money']*100,
>
> ​      'body'     => $param['body'],
>
> ​      'detail'    => $param['subject'],
>
> ​      'openid'    => $param['openid'],
>
> ]);

### 接口类方法

> */\* 支付提交接口 \*/*
>
>   public function _submit($param);
>
>   */\* 同步通知接口 \*/*
>
>   public function _sync($param);
>
>   */\* 异步通知接口 \*/*
>
>   public function _async($param);
>
>   */\* 退款提交接口 \*/*
>
>   public function _refundSubmit($param);
>
>   */\* 同步退款通知接口 \*/*
>
>   public function _syncRefund($param);
>
>   */\* 异步退款通知接口 \*/*
>
>   public function _asyncRefund($param);
