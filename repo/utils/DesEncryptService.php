<?php
// +----------------------------------------------------------------------
// | AES加密验证 key 通过服务器发给客户端
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace Utils;
use Logic\GameApi\Cache\TokenRedis;

class DesEncryptService
{
    // 秘钥
    static $key      = 'abwe84f9e3b4k9t142049de20tc3bv0k';           // 加密使用
    static $sign_key = '9e7a4cf635y6b4t13d21612c87bf6ovf';           // 签名使用
    protected $token_redis;

    public function __construct()
    {
        $this->token_redis = new TokenRedis();
    }

    /**
     * @AES加密
     * @param string $string 需要加密的字符串
     * @return string
     */
    public  function encrypt($string)
    {
        $string = $this->pkcsPadding($string, 8);
        $key    = str_pad(self::$key, 8, '0');
        $sign   = openssl_encrypt($string, 'DES-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
        $sign   = base64_encode($sign);
        $sign   = str_replace('/','_',$sign);
        $sign   = str_replace('=','-',$sign);
        return $sign;
    }

    /**
     * @AES解密
     * @param string $string 需要解密的字符串
     * @return string
     */
    public  function decrypt($string)
    {
        $string    = str_replace(' ', '+', $string);
        $string    = str_replace('_', '/', $string);
        $string    = str_replace('-', '=', $string);
        $encrypted = base64_decode($string);
        $key       = str_pad(self::$key, 8, '0');
        $sign      = @openssl_decrypt($encrypted, 'DES-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
        $sign      = $this->unPkcsPadding($sign);
        $sign      = rtrim($sign);
        return $sign;
    }

    /**
     * 填充
     * @param $str
     * @param $blockSize
     * @return string
     */
    private  function pkcsPadding($str, $blockSize)
    {
        $pad = $blockSize - (strlen($str) % $blockSize);
        return $str . str_repeat(chr($pad), $pad);
    }


    /**
     * 去填充
     * @param $str
     * @return string
     */
    private  function unPkcsPadding($str)
    {
        $pad = ord($str[strlen($str) - 1]);
        if ($pad > strlen($str)) {
            return false;
        }
        return substr($str, 0, -1 * $pad);
    }


    /**
     * @签名
     * @param array $array 需要签名的数组
     * @param int $type 返回类型 1=字符串 2=Array
     * @return string|array
     */
    public  function sign($array, $type = 1)
    {
        $sign = '';
        foreach ($array as $key => $val) {
            if ($key != 'sign_key') {
                $sign .= $key . '=' . $val . '&';
            }
        }
        $sign              .= 'sign_key=' . self::$sign_key;
        $sign              = md5($sign);
        $array['sign_key'] = strtoupper($sign);
        if ($type == 1) {
            return strtoupper($sign);
        } else if ($type == 2) {
            return $array;
        } else {
            return strtoupper($sign);
        }
    }

    /**
     * @签名验证
     * @param array $array 需要验证签名的数组
     * @return bool
     */
    public  function signCheck($array)
    {
        $sign = '';
        foreach ($array as $key => $val) {
            if ($key != 'sign_key') {
                $sign .= $key . '=' . $val . '&';
            }
        }

        $sign .= 'sign_key=' . self::$sign_key;
        if ($array['sign_key'] == strtoupper(md5($sign))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取token
     * @param $params
     * @return string
     */
    public  function getToken($params){
        $jsondata         = $params;
        $jsondata['time'] = time();
        //获取签名后的数组
        $signArray = $this->sign($jsondata, 2);
        $string    = json_encode($signArray);
        $token     = $this->encrypt($string);
        if($token){
            $this->token_redis->setToken($params, $token);
        }
        return $token;
    }

    /**
     * 验证token
     * @param $token
     */
    public  function verifyToken($token){
        if(!$token){
            throw new \Exception('token不能为空', 401);
        }
        $data      = $this->decrypt($token);
        $dataArray = json_decode($data, true);
        $res       = $this->token_redis->tokenExist($dataArray, $token);
        if(!$res){
            throw new \Exception('token已失效', 401);
        }
        if (!$this->signCheck(json_decode($data, true))) {
            throw new \Exception('签名验证失败', 401);
        }
        return $dataArray;
    }

    // 测试方法
    public  function test($params)
    {
//        //需要提交的参数
//        $jsondata = array(
//            'action'    => 'demoLogin',
//            'appSecret' => md5('123456'),
//            'uid'       => '101',
//            'gameCode'  => 'FuLinMen',
//        );

        $jsondata =$params;

        //获取签名
        $sign = $this->sign($jsondata, 1);
        echo '签名:' . $sign;
        echo '<br>';
        //获取签名后的数组
        $signArray = $this->sign($jsondata, 2);
        $string    = json_encode($signArray);
        echo '签名结果:' . $string;
        echo '<br>';
        $a = $this->encrypt($string);
        echo '加密结果:' . $a;
        echo '<br>';
        $b = $this->decrypt($a);
        echo '解密结果:' . $b;
        echo '<br>';
        if ($this->signCheck(json_decode($b, true))) {
            echo '签名验证成功';
            echo '<br>';
        } else {
            echo '签名验证失败';
            echo '<br>';
        }
    }
}

