<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * OUSPAY
 */
class OUSPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new OUSPAY();
    }

    //与第三方交互
    public function start()
    {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam()
    {
        //请求参数 Request parameter
        $data = [
            'merchant_no' => $this->partnerID,
            'timestamp' => time(),
            'sign_type' => 'MD5'
        ];
        $data['params'] = json_encode([
            'merchant_ref' => $this->orderID,
            'product' => $this->rechargeType,  //ThaiQR, TrueH5
            'amount' => bcdiv($this->money, 100, 2)
        ]);
        $data['sign'] = $this->sign($data);

        $this->parameter = $data;
        $this->payUrl .= '/api/gateway/pay';
    }

    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        $str = $data['merchant_no'] . $data['params'] . $data['sign_type'] . $data['timestamp'] . $this->key;
        return md5($str);
    }

    public function formGet()
    {
        //        echo '<pre>';print_r($this->parameter);exit;
        //初始化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl . '?' . http_build_query($this->parameter));
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        $this->re = $output;
    }

    public function formPost()
    {
        //var_dump($this->payUrl);
        //        echo '<pre>';print_r($this->parameter);exit;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 201) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($code == 200) {
                $data = json_decode($result['params'], true);
                $targetUrl = $data['payurl'];
                $returnCode = 0;
            } else {
                $targetUrl = '';
                $returnCode = 1;
                $message = isset($result['message']) ? $result['message'] : 'unknown error';
            }

            $this->return['code'] = $returnCode;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $targetUrl;
            $this->return['pay_no'] = !empty($data['system_ref']) ? $data['system_ref'] : '';
            if ($this->rechargeType == 'ThaiAutoBilling') {
                $this->return['bank'] = [
                    'bank_code' => $data['bank_code'],
                    'bank_name' => $data['payee_name'],
                    'bank_number' => $data['payee_card_no']
                ];
            }
        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }
    }




    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = [])
    {
        $config = Recharge::getThirdConfig('ouspay');
        $this->key = $config['key'];
        $this->pubKey = $config['pub_key'];

        $params = $param;

        $data = json_decode($params['params'], true);
        $res = [
            'status' => 0,
            'order_number' => $data['merchant_ref'],
            'third_order' => $data['system_ref'],
            'third_money' => $data['amount'] * 100,
            'third_fee' => 0,
            'error' => '',
        ];

        if (isset($params['clearOrderNo']) && $params['clearOrderNo'] === true) {
            $params['params'] = str_replace('"' . $data['merchant_ref'] . '"', 'null', $params['params']);
        }

        //检验sign
        if ($param['sign'] != $this->sign($params)) {
            throw new \Exception('sign is wrong');
        }

        if ($data['status'] == 1) {
            $res['status'] = 1;
        } else {
            throw new \Exception('unpaid');
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

    }

    public function config()
    {
        $config = Recharge::getThirdConfig('ouspay');
        $this->key = $config['key'];
        $this->payUrl = $config['payurl'];
        $this->partnerID = $config['partner_id'];
    }

    public function register($userInfo)
    {
        $this->config();
        $bankCode = $this->getBankName($userInfo['bank_code']);
        if (!$bankCode) {
            return false;
        }
        //请求参数 Request parameter
        $data = [
            'merchant_no' => $this->partnerID,
            'timestamp' => time(),
            'sign_type' => 'MD5'
        ];
        $data['params'] = json_encode([
            'customerid' => $userInfo['user_id'],
            'currency' => 'THB',
            'extra' => [
                'account_no' => $userInfo['card'],
                'bank_code' => $bankCode
            ]
        ]);

        $data['sign'] = $this->sign($data);

        $this->parameter = $data;
        $this->payUrl .= '/api/gateway/bind-customer';
        $this->formPost();
        $result = json_decode($this->re, true);
        Recharge::addLogByTxt(['third' => 'ouspay register', 'date' => date('Y-m-d H:i:s'), 'json' => json_encode($data), 'response' => $this->re], '');

        if (isset($result['code']) && $result['code'] == 200) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName($bank)
    {
        global $app;
        $ci = $app->getContainer();
        $country_code = '';
        if(isset($ci->get('settings')['website']['site_type'])){
            $country_code = $ci->get('settings')['website']['site_type'];
        }

        if($country_code == 'ncg'){
            $banks = [
                "KBANK" => "KBANK",
                "BBL" => "BBL",
                "BAAC" => "BAAC",
                "BAY" => "BAY",
                "CIMB" => "CIMB",
                "CITI" => "CITI",
                "DB" => "DB",
                "GHB" => "GHB",
                "ICBC" => "ICBC",
                "KKB" => "KKB",
                "KTB" => "KTB",
                "MHCB" => "MHCB",
                "SCBT" => "SCBT",
                "TTB" => "TTB",
                "GSB" => "GSB",
                "HSBC" => "HSBC",
                "SCB" => "SCB",
                "SMBC" => "SMBC",
                "TCRB" => "TCRB",
                "TISCO" => "TISCO",
                "UOB" => "UOB"
            ];
        }else{
            $banks = [
                'Gcash' => 'GCASH',
                'Paymaya Philippines, Inc.' => 'PAYMAYA'
            ];
        }

        if (!isset($banks[$bank])) {
            return false;
        }

        return $banks[$bank];
    }

}
