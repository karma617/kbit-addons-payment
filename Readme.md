### 使用前配置

相应的支付驱动有：

> wechat_qr	微信扫码
>
> wechat_js	微信jsapi
>
> wechat_mini	微信小程序
>
> wechat_mweb	微信H5
>
> wechat_app	微信app
>
> alipay	支付宝即时到帐
>
> alipay_app	支付宝app
>
> alipay_scan	支付宝当面付

`.env`文件内设置相关支付驱动信息，具体配置项到 `kbit\tp5\kbit\addons-payment\src\config.php`中查看。

- 配置项字段说明：

  - 支付宝支付驱动

	  app_id	应用ID

    aes	AES密钥

    private_key	商户私钥

    public_key	支付宝公钥

    payment_callback	支付回调方法（支付成功后执行，格式：模块/控制器/方法）

    refund_callback	退款回调方法（退款成功后执行，格式：模块/控制器/方法）
	
    
	
	- 微信支付驱动
	
	  appid	应用ID
  
    secret	开发者密码
  
    mch_id	支付商户号
  
    key	API密钥
  
    cert_pem	证书文件
  
    key_pem	证书秘钥
  
    payment_callback	支付回调方法
  
    refund_callback	退款回调方法
  
  
  

### 使用方法

控制器内引入`use kbitAddons\payment\payment\Factory;`

> // new 一个工厂类，传入支付模块名称
>
> $factory = new Factory('wechat_js');
>
> // 执行下单方法，传入相关参数
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

### 相关方法说明

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
