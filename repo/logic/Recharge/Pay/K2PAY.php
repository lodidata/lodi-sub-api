<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use Model\BankUser;
use Model\Profile;
/**
 *
 * K2PAY
 * @author
 */
class K2PAY extends BASES {
    public $http_code;
    static function instantiation() {
        return new K2PAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        if($this->money % 100){
            throw new \Exception('Transfer only supports integer');
        }
        $channelNo = 8;
        if(!empty($this->rechargeType)){
            $channelNo = $this->rechargeType;
        }

        //请求参数 Request parameter
        $time = time();
        $data = array(
            'platformId'        => $this->partnerID,//  是   string  商户号 business number
            'amount'            => bcdiv($this->money, 100),
            'playerName'        => $this->userId,//  是   string  用户id business number
            'userNo'            => '',
            'realName'          => '',
            'callbackUrl'       => $this->payCallbackDomain .'/pay/callback/k2pay',
            'proposalId'        => $this->orderID,
            'depositMethod'     => $channelNo,
            'clientType'        => "0",
            'entryType'         => "0",
            'createTime'        => $time,
        );

        $data['sign']    = $this->createHmac($data);
        $this->parameter = $data;
        $this->payUrl   .= '/api/deposit-url';
    }
    public function sortObjAsc($obj) {
        ksort($obj);

        return array_filter($obj, function ($val){
            return ($val !== "") && ($val !== 0) && ($val !== 'undefined');
        });
    }
    public function getQueryStringOnly($obj)
    {
        $queryString = "";

        foreach ($obj as $key => $value) {
            if (!empty($queryString)) {
                $queryString .= "&";
            }

            $queryString .= $key . "=" . $value;
        }

        return $queryString;
    }
    public function createHmac($obj) {
        $sign_arr = $this->sortObjAsc($obj);
        $queryString = $this->getQueryStringOnly($sign_arr);
        return hash_hmac('sha256', $queryString, $this->key);
    }
    //生成签名
    public function sign($data) {
        unset($data['bankName']);
        unset($data['userName']);
        unset($data['channelNo']);
        unset($data['payeeName']);
        unset($data['appSecret']);
        unset($data['amountBeforeFixed']);
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            $str .= $k.'='.$v.'&';
        }
        $str = trim($str, '&');

        $sign_str       = $str . $this->key;
        $digest         = hash("sha256", $sign_str);
        $sign           = strtoupper(md5($digest));
        return $sign;

    }

    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code       = isset($result['status']) ? $result['status'] : 1;
        $message    = isset($result['errorMsg']) ? $result['errorMsg'] : 'errorMsg:'.(string)$this->re;


        if ($code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            $this->return['code']    = 0;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $result['reqUrl'];
        } else{
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }

    }

    public function formPost() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->arrayToURL($this->parameter));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'platform:'.$this->partnerID,
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;

    }


    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = []) {
        $config     = Recharge::getThirdConfig('K2PAY');
        $this->key  = $config['key'];
        $params = $param['content'];
        $res = [
            'status'        => 0,
            'order_number'  => $params['proposalId'],
            'third_order'   => $params['billNo'],
            'third_money'   => $params['amount'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];
        if ($param['sign'] == $this->createHmac($params)) {
            if($params['status'] == 'SUCCESS' ){
                $res['status'] = 1;
            }else if($params['status'] == 'PENDING' ){
                throw new \Exception('PENDING');
            }else{
                throw new \Exception('unpaid');

            }
        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('YFBPAY');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'merchantNo' => $config['partner_id'],//	是	string	商户号 business number
            'tradeNo'    => $payNo,
            'orderNo'    => $order_number,
            'time'       => time(),
            'appSecret'  => $config['pub_key'],
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/order/status';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['text']) ? $result['text'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code == 0){
                //未支付
                if($result['status'] != 'PAID'){
                    throw new \Exception($result['status']);
                }
                $res = [
                    'status'       => $result['status'],
                    'order_number' => $result['orderNo'],
                    'third_order'  => $result['tradeNo'],
                    'third_money'  => $result['paid'] * 100,
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }
}
