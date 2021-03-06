<?php

namespace kbitAddons\payment;

use Exception;

/**
 * 支付宝支付基类
 * Class AliPay
 */
abstract class BasicAliPay
{
    /**
     * 支持配置
     * @var Array
     */
    protected $config;

    /**
     * 当前请求数据
     * @var Array
     */
    protected $options;

    /**
     * DzContent数据
     * @var Array
     */
    protected $params;

    /**
     * 正常请求网关
     * @var string
     */
    protected $gateway = 'https://openapi.alipay.com/gateway.do?charset=utf-8';

    /**
     * AliPay constructor.
     *
     * @param array $options
     * @author 617 <email：723875993@qq.com>
     */
    public function __construct($options)
    {
        $this->params = [];
        $this->config = $options;
        if (empty($options['app_id'])) {
            throw new Exception("Missing Config -- [app_id]");
        }
        if (empty($options['public_key'])) {
            throw new Exception("Missing Config -- [public_key]");
        }
        if (empty($options['private_key'])) {
            throw new Exception("Missing Config -- [private_key]");
        }
        if (!empty($options['debug'])) {
            $this->gateway = 'https://openapi.alipaydev.com/gateway.do?charset=utf-8';
        }
        $this->options = [
            'app_id'    => $this->config['app_id'],
            'charset'   => empty($options['charset']) ? 'utf-8' : $options['charset'],
            'format'    => 'JSON',
            'version'   => '1.0',
            'sign_type' => empty($options['sign_type']) ? 'RSA2' : $options['sign_type'],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->options['notify_url'] = url('/addons_payment_api/callback/async', ['method' => PAY_CODE], '', true);
        $this->options['return_url'] = url('/addons_payment_api/callback/sync', ['method' => PAY_CODE], '', true);

        if (isset($options['app_auth_token']) && $options['app_auth_token'] !== '') {
            $this->options['app_auth_token'] = $options['app_auth_token'];
        }
    }

    /**
     * 查询支付宝订单状态
     * @param string $out_trade_no
     * @return array|boolean
     * @author 617 <email：723875993@qq.com>
     */
    public function query($out_trade_no = '')
    {
        unset($this->options['notify_url'], $this->options['return_url']);
        $this->options['method'] = 'alipay.trade.query';
        return $this->getResult(['out_trade_no' => $out_trade_no]);
    }

    /**
     * 支付宝订单退款操作
     * @param array|string $options 退款参数或退款商户订单号
     * @param null $refund_amount 退款金额
     * @return array|boolean
     * @author 617 <email：723875993@qq.com>
     */
    public function refund($options, $refund_amount = null)
    {
        if (!is_array($options)) $options = ['out_trade_no' => $options, 'refund_amount' => $refund_amount];
        $this->options['method'] = 'alipay.trade.refund';
        return $this->getResult($options);
    }

    /**
     * 关闭支付宝进行中的订单
     * @param array|string $options
     * @return array|boolean
     * @author 617 <email：723875993@qq.com>
     */
    public function close($options)
    {
        if (!is_array($options)) $options = ['out_trade_no' => $options];
        $this->options['method'] = 'alipay.trade.close';
        return $this->getResult($options);
    }

    /**
     * 获取通知数据
     * @param boolean $needSignType 是否需要sign_type字段
     * @return boolean|array
     * @author 617 <email：723875993@qq.com>
     */
    public function notify($param)
    {
        if (empty($param) || empty($param['sign'])) {
            throw new Exception('Illegal push request.', 0);
        }
        $content = wordwrap($this->config['public_key'], 64, "\n", true);
        $res = "-----BEGIN PUBLIC KEY-----\n{$content}\n-----END PUBLIC KEY-----";
        $data = $this->getSignContent($param);
        $sign = $param['sign'];
        if ("RSA2" == $param['sign_type']) {
            if (openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256) !== 1) {
                throw new \Exception("Data signature verification failed1.", 1);
            }
        } else {
            if (openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA1) !== 1) {
                throw new \Exception('Data signature verification failed2.', 1);
            }
        }
        return $param;
    }

    /**
     * 验证接口返回的数据签名
     * @param array $data 通知数据
     * @param null|string $sign 数据签名
     * @return array|boolean
     * @author 617 <email：723875993@qq.com>
     */
    protected function verify($data, $sign)
    {
        $content = wordwrap($this->config['public_key'], 64, "\n", true);
        $res = "-----BEGIN PUBLIC KEY-----\n{$content}\n-----END PUBLIC KEY-----";
        if ($this->options['sign_type'] === 'RSA2') {
            if (openssl_verify(json_encode($data, 256), base64_decode($sign), $res, OPENSSL_ALGO_SHA256) !== 1) {
                throw new Exception('Data signature verification failed3.');
            }
        } else {
            if (openssl_verify(json_encode($data, 256), base64_decode($sign), $res, OPENSSL_ALGO_SHA1) !== 1) {
                throw new Exception('Data signature verification failed4.');
            }
        }
        return $data;
    }

    /**
     * 获取数据签名
     * @return string
     * @author 617 <email：723875993@qq.com>
     */
    protected function getSign()
    {
        $content = wordwrap($this->trimCert($this->config['private_key']), 64, "\n", true);
        $string = "-----BEGIN RSA PRIVATE KEY-----\n{$content}\n-----END RSA PRIVATE KEY-----";

        $data = $this->getSignContent($this->options, true);
        if ($this->options['sign_type'] === 'RSA2') {
            openssl_sign($data, $sign, $string, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $string, OPENSSL_ALGO_SHA1);
        }
        return base64_encode($sign);
    }

    /**
     * 去除证书前后内容及空白
     * @param string $sign
     * @return string
     * @author 617 <email：723875993@qq.com>
     */
    protected function trimCert($sign)
    {
        // if (file_exists($sign)) $sign = file_get_contents($sign);
        return preg_replace(['/\s+/', '/\-{5}.*?\-{5}/'], '', $sign);
    }

    /**
     * 数据签名处理
     * @param array $data 需要进行签名数据
     * @param boolean $needSignType 是否需要sign_type字段
     * @return bool|string
     * @author 617 <email：723875993@qq.com>
     */
    private function getSignContent(array $data, $needSignType = false)
    {
        list($attrs,) = [[], ksort($data)];
        if (isset($data['sign'])) unset($data['sign']);
        if (empty($needSignType)) unset($data['sign_type']);
        foreach ($data as $key => $value) {
            if ($value === '' || is_null($value)) continue;
            array_push($attrs, "{$key}={$value}");
        }
        return join('&', $attrs);
    }

    /**
     * 数据包生成及数据签名
     *
     * @param array $options
     * @author 617 <email：723875993@qq.com>
     */
    protected function applyData(array $options)
    {
        $this->options['biz_content'] = json_encode(array_merge($this->params, $options), 256);
        $this->options['sign'] = $this->getSign();
    }

    /**
     * 请求接口并验证访问数据
     *
     * @param array $options
     * @return array|boolean
     * @author 617 <email：723875993@qq.com>
     */
    protected function getResult(array $options)
    {
        $this->applyData($options);
        $method = str_replace('.', '_', $this->options['method']) . '_response';
        $data = json_decode(\one\Http::post($this->gateway, $this->options), true);
        if (!isset($data[$method]['code']) || $data[$method]['code'] !== '10000') {
            throw new Exception(
                "Error: " .
                    (empty($data[$method]['code']) ? '' : "{$data[$method]['msg']} [{$data[$method]['code']}]\r\n") .
                    (empty($data[$method]['sub_code']) ? '' : "{$data[$method]['sub_msg']} [{$data[$method]['sub_code']}]\r\n") .
                    json_encode($data, 256),
                1
            );
        }
        return $data[$method];
        // 去除返回结果签名检查
        // return $this->verify($data[$method], $data['sign']);
    }

    /**
     * 生成支付HTML代码
     *
     * @return string
     * @author 617 <email：723875993@qq.com>
     */
    protected function buildPayHtml()
    {
        $html = "<form id='alipaysubmit' name='alipaysubmit' action='{$this->gateway}' method='post'>" . PHP_EOL;
        foreach ($this->options as $key => $value) {
            $value = str_replace("'", '&apos;', $value);
            $html .= "<input type='hidden' name='{$key}' value='{$value}'/>" . PHP_EOL;
        }
        $html .= "<input type='submit' value='ok' style='display:none;'></form>";
        return "{$html}<script>document.forms['alipaysubmit'].submit();</script>";
    }

    /**
     * 新版 从证书中提取序列号
     *
     * @param string $sign
     * @return string
     * @author 617 <email：723875993@qq.com>
     */
    public function getCertSN(string $sign)
    {
        // if (file_exists($sign)) $sign = file_get_contents($sign);
        $ssl = openssl_x509_parse($sign);
        return md5($this->_arr2str(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
    }

    /**
     * 新版 提取根证书序列号
     *
     * @param string $sign
     * @return string|null
     * @author 617 <email：723875993@qq.com>
     */
    public function getRootCertSN(string $sign)
    {
        $sn = null;
        // if (file_exists($sign)) $sign = file_get_contents($sign);
        $array = explode("-----END CERTIFICATE-----", $sign);
        for ($i = 0; $i < count($array) - 1; $i++) {
            $ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
            if (strpos($ssl[$i]['serialNumber'], '0x') === 0) {
                $ssl[$i]['serialNumber'] = $this->_hex2dec($ssl[$i]['serialNumber']);
            }
            if ($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption") {
                if ($sn == null) {
                    $sn = md5($this->_arr2str(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {
                    $sn = $sn . "_" . md5($this->_arr2str(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
        return $sn;
    }

    /**
     * 新版 数组转字符串
     *
     * @param array $array
     * @return string
     * @author 617 <email：723875993@qq.com>
     */
    private function _arr2str(array $array)
    {
        $string = [];
        if ($array && is_array($array)) {
            foreach ($array as $key => $value) {
                $string[] = $key . '=' . $value;
            }
        }
        return implode(',', $string);
    }

    /**
     * 新版 0x转高精度数字
     *
     * @param string $hex
     * @return int|string
     * @author 617 <email：723875993@qq.com>
     */
    private function _hex2dec(string $hex)
    {
        list($dec, $len) = [0, strlen($hex)];
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }
}
