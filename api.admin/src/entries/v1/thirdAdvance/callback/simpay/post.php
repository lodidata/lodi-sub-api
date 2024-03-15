<?php

use Logic\Admin\BaseController;
use Logic\Recharge\Recharge;
use Logic\Transfer\ThirdParty\BASES;
return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
//        'verifyToken', 'authorize',
    ];

    public function run() {
        $params = file_get_contents("php://input");
        $params = $this->signVerify($params);
        BASES::addLogByTxt($params);
        $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
        if(is_array($params)){
            $result = $params;
        }else{
            $result=json_decode($params,true);
        }
        try{
            $transfer->anotherCallbackResult($result['merorder'], $params);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'simpay',
                'method'    => $this->ci->request->getMethod(),
                'content'   => json_encode($params, JSON_UNESCAPED_UNICODE),
                'error'     => $e->getMessage()
            ];
            \DB::table('transfer_callback_failed')->insert($data);
            exit($e->getMessage());
        }
        exit('success');
    }
    //回调校验签名
    public function signVerify($data) {
        $config       = Recharge::getThirdConfig('simpay');
        $this->pubKey = $config['pub_key'];
        $this->key    = $config['key'];
        $body   = $this->de3des($data,$this->pubKey);
        if (!$body) return false;
        $debody = $this->stringToArray($body);
        return $debody;
    }
    public function stringToArray($str) {
        if (is_string($str)) {
            parse_str($str,$strArray);
            $arr = $strArray;
        } else {
            $arr = $str;
        }
        return $arr;
    }
    //解密
    function de3des($value,$deskey){
        $deskey = substr($deskey,0,24);
        $deskey = sprintf('%-024s', $deskey);
        $result = hex2bin($value);
        $result = openssl_decrypt($result, 'DES-EDE3', $deskey, OPENSSL_RAW_DATA);
        return $result;
    }
};
