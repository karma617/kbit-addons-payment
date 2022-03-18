<?php
namespace kbitAddons\payment\payment;

use Exception;

/**
 * 微信支付基础类
 *
 * @author 617 <email：723875993@qq.com>
 */
class BasicWePay
{
    /**
     * 商户配置
     * @var DataArray
     */
    protected $config;

    /**
     * 当前请求数据
     * @var DataArray
     */
    protected $params;

    /**
     * Wepay constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (empty($options['appid'])) {
            throw new Exception("Missing Config -- [appid]");
        }
        if (empty($options['mch_id'])) {
            throw new Exception("Missing Config -- [mch_id]");
        }
        if (empty($options['key'])) {
            throw new Exception("Missing Config -- [key]");
        }
    
        $this->config = $options;
        // 商户基础参数
        $this->params = [
            'appid'     => $this->config['appid'],
            'mch_id'    => $this->config['mch_id'],
            'nonce_str' => $this->createNoncestr(),
        ];
        // 商户参数支持
        if (isset($this->config['sub_appid'])) {
            $this->params['sub_appid']=$this->config['sub_appid'];
        }
        if (isset($this->config['sub_mch_id'])) {
            $this->params['sub_mch_id'] =$this->config['sub_mch_id'];
        }
        $this->params['notify_url'] = url('/addons_payment_api/callback/async', ['method' => PAY_CODE],'',true);
    }

    /**
     * 获取微信支付通知
     * @return array
     */
    public function getNotify()
    {
        $data = $this->xml2arr(file_get_contents('php://input'));
        if (isset($data['sign']) && $this->getPaySign($data) === $data['sign']) {
            return $data;
        }
        throw new Exception('Invalid Notify.');
    }

    /**
     * 获取微信支付通知回复内容
     * @return string
     */
    public function getNotifySuccessReply()
    {
        return $this->arr2xml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']);
    }

    /**
     * 生成支付签名
     * @param array $data 参与签名的数据
     * @param string $signType 参与签名的类型
     * @param string $buff 参与签名字符串前缀
     * @return string
     */
    public function getPaySign(array $data, $signType = 'MD5', $buff = '')
    {
        ksort($data);
        if (isset($data['sign'])) unset($data['sign']);
        foreach ($data as $k => $v) $buff .= "{$k}={$v}&";
        $buff .= ("key=" . $this->config['key']);
        if (strtoupper($signType) === 'MD5') {
            return strtoupper(md5($buff));
        }
        return strtoupper(hash_hmac('SHA256', $buff, $this->config['key']));
    }

    /**
     * 创建JsApi及H5支付参数
     * @param string $prepayId 统一下单预支付码
     * @return array
     */
    public function jsapiParams($prepayId)
    {
        $option = [];
        $option["appId"] = $this->config['appid'];
        $option["timeStamp"] = (string)time();
        $option["nonceStr"] = $this->createNoncestr();
        $option["package"] = "prepay_id={$prepayId}";
        $option["signType"] = "MD5";
        $option["paySign"] = $this->getPaySign($option, 'MD5');
        $option['timestamp'] = $option['timeStamp'];
        return $option;
    }
    /**
     * 获取微信App支付秘需参数
     * @param string $prepayId 统一下单预支付码
     * @return array
     */
    public function appParams($prepayId)
    {
        $data = [
            'appid'     => $this->config['appid'],
            'partnerid' => $this->config['mch_id'],
            'prepayid'  => (string)$prepayId,
            'package'   => 'Sign=WXPay',
            'timestamp' => (string)time(),
            'noncestr'  => $this->createNoncestr(),
        ];
        $data['sign'] = $this->getPaySign($data, 'MD5');
        return $data;
    }
    /**
     * 获取支付规则二维码
     * @param string $productId 商户定义的商品id或者订单号
     * @return string
     */
    public function qrcParams($productId)
    {
        $data = [
            'appid'      => $this->config['appid'],
            'mch_id'     => $this->config['mch_id'],
            'time_stamp' => (string)time(),
            'nonce_str'  => $this->createNoncestr(),
            'product_id' => (string)$productId,
        ];
        $data['sign'] = $this->getPaySign($data, 'MD5');
        return "weixin://wxpay/bizpayurl?" . http_build_query($data);
    }
    /**
     * 转换短链接
     * @param string $longUrl 需要转换的URL，签名用原串，传输需URLencode
     * @return array
     */
    public function shortUrl($longUrl)
    {
        $url = 'https://api.mch.weixin.qq.com/tools/shorturl';
        return $this->callPostApi($url, ['long_url' => $longUrl]);
    }


    /**
     * 数组直接转xml数据输出
     * @param array $data
     * @param bool $isReturn
     * @return string
     */
    public function toXml(array $data, $isReturn = false)
    {
        $xml = $this->arr2xml($data);
        if ($isReturn) {
            return $xml;
        }
        echo $xml;
    }

    /**
     * 以Post请求接口
     * @param string $url 请求
     * @param array $data 接口参数
     * @param bool $isCert 是否需要使用双向证书
     * @param string $signType 数据签名类型 MD5|SHA256
     * @param bool $needSignType 是否需要传签名类型参数
     * @return array
     */
    protected function callPostApi($url, array $data, $isCert = false, $signType = 'HMAC-SHA256', $needSignType = true)
    {
        $option = [];
        if ($isCert) {
            $option['ssl_p12'] = @$this->config['ssl_p12'];
            $option['ssl_cer'] = @$this->config['cert_pem'];
            $option['ssl_key'] = @$this->config['key_pem'];
            if (is_string($option['ssl_p12']) && file_exists($option['ssl_p12'])) {
                $content = file_get_contents($option['ssl_p12']);
                if (!openssl_pkcs12_read($content, $certs, $this->config['mch_id'])) {
                    throw new Exception("P12 certificate does not match MCH_ID --- ssl_p12");
                } 
            }
            if(is_string($option['ssl_cer']) && is_string($option['ssl_key'])){
                $_string = [
                    'ssl_cer'=>$option['ssl_cer'],
                    'ssl_key'=>$option['ssl_key'],
                ];
                $_temp_cert_file = $this->getCertPemBystring($_string);
                $option['ssl_cer'] = $_temp_cert_file['ssl_cer'];
                $option['ssl_key'] = $_temp_cert_file['ssl_key'];
            }
            if (empty($option['ssl_cer']) || !file_exists($option['ssl_cer'])) {
                throw new Exception("Missing Config -- ssl_cer", '0');
            }
            if (empty($option['ssl_key']) || !file_exists($option['ssl_key'])) {
                throw new Exception("Missing Config -- ssl_key", '0');
            }
        }
        $params = array_merge($this->params,$data);
        $needSignType && ($params['sign_type'] = strtoupper($signType));
        $params['sign'] = $this->getPaySign($params, $signType);

        $option['data'] = $this->arr2xml($params);
        $result = $this->xml2arr($this->doRequest('post',$url, $option));
        if ($result['return_code'] !== 'SUCCESS') {
            throw new Exception($result['return_msg']);
        }
        return $result;
    }
    /**
     * 产生随机字符串
     * @param int $length 指定字符长度
     * @param string $str 字符串前缀
     * @return string
     */
    public function createNoncestr($length = 32, $str = "")
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    /**
     * 数组转XML内容
     * @param array $data
     * @return string
     */
    public static function arr2xml($data)
    {
        return "<xml>" . self::_arr2xml($data) . "</xml>";
    }

    /**
     * XML内容生成
     * @param array $data 数据
     * @param string $content
     * @return string
     */
    private static function _arr2xml($data, $content = '')
    {
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = 'item';
            $content .= "<{$key}>";
            if (is_array($val) || is_object($val)) {
                $content .= self::_arr2xml($val);
            } elseif (is_string($val)) {
                $content .= '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $val) . ']]>';
            } else {
                $content .= $val;
            }
            $content .= "</{$key}>";
        }
        return $content;
    }

    /**
     * 解析XML内容到数组
     * @param string $xml
     * @return array
     */
    public static function xml2arr($xml)
    {
        $entity = libxml_disable_entity_loader(true);
        $data = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_disable_entity_loader($entity);
        return json_decode(json_encode($data), true);
    }
    /**
     * 获取cert证书文件
     * @return array
     * @throws BaseException
     */
    private function getCertPemBystring($cert_string)
    {
        // cert目录
        static $certFile = null;
        static $keyFile = null;
        $certFile = tmpfile();
        fwrite($certFile, $cert_string['ssl_cer']);
        $tempCertPath = stream_get_meta_data($certFile);

        $keyFile = tmpfile();
        fwrite($keyFile, $cert_string['ssl_key']);
        $tempKeyPath = stream_get_meta_data($keyFile);
        return [
            'ssl_cer' => $tempCertPath['uri'],
            'ssl_key' => $tempKeyPath['uri']
        ];
    }
    /**
     * CURL模拟网络请求
     * @param string $method 请求方法
     * @param string $url 请求方法
     * @param array $options 请求参数[headers,data,ssl_cer,ssl_key]
     * @return boolean|string
     * @throws LocalCacheException
     */
    public static function doRequest($method, $url, $options = [])
    {
        $curl = curl_init();
        // GET参数设置
        if (!empty($options['query'])) {
            $url .= (stripos($url, '?') !== false ? '&' : '?') . http_build_query($options['query']);
        }
        // CURL头信息设置
        if (!empty($options['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }
        // POST数据设置
        if (strtolower($method) === 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $options['data']);
        }
        // 证书文件设置
        if (!empty($options['ssl_cer'])) if (file_exists($options['ssl_cer'])) {
            curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($curl, CURLOPT_SSLCERT, $options['ssl_cer']);
        } else throw new Exception("Certificate files that do not exist. --- [ssl_cer]");
        // 证书文件设置
        if (!empty($options['ssl_key'])) if (file_exists($options['ssl_key'])) {
            curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($curl, CURLOPT_SSLKEY, $options['ssl_key']);
        } else throw new Exception("Certificate files that do not exist. --- [ssl_key]");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        list($content) = [curl_exec($curl), curl_close($curl)];
        
        return $content;
    }
}