<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class OUSPAY extends BASES
{
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg()
    {
        return 'success';
    }

    //请求代付接口
    public function runTransfer()
    {
        //组装参数
        $data = [
            'amount' => bcdiv($this->money, 100, 2),
            'merchant_ref' => $this->orderID,
            'product' => 'ThaiPayout',
            'extra' => [
                'account_name' => $this->bankUserName,
                'account_no' => $this->bankCard,
                'bank_code' => $this->getBankName(),
            ],
        ];
        $data['params'] = json_encode($data);
        $data['merchant_no'] = $this->partnerID;
        $data['timestamp'] = time();
        $data['sign_type'] = 'MD5';
        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/api/gateway/withdraw';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->httpCode == 201) {
            if ($code == 200) {
                $resultData = json_decode($result['params'], true);
                $this->return['code'] = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg'] = $message;
                $this->transferNo = $resultData['system_ref'];//第三方订单号
                //成功就直接返回了
                return;
            }
        } elseif ($this->httpCode == 400 && $code == 400) {
            $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
            $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
            $this->return['code'] = 886;
            $this->return['balance'] = 0;
            $this->return['msg'] = 'OUSPAY:' . $message ?? '代付失败';
            return;
        }

        $this->return['code'] = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg'] = $message;
        $this->transferNo = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance()
    {
        $data = [
            'currency' => 'THB',
        ];
        $data['params'] = json_encode($data);
        $data['merchant_no'] = $this->partnerID;
        $data['timestamp'] = time();
        $data['sign_type'] = 'MD5';
        $data['sign'] = $this->sign($data);

        $this->parameter = $data;

        $this->payUrl .= "/api/gateway/query/balance";
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 201) {
            if ($code == 200) {
                $resultData = json_decode($result['params'], true);
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($resultData['available_balance'], 100);
                $this->return['msg'] = $message;
                return;
            }
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);

        //$this->return['code'] = 10509;
        //$this->return['balance'] = 3000000;
        //$this->return['msg'] = '';
    }

    //查询代付结果
    public function getTransferResult()
    {
        $redisKey = 'OUSPAY';
        global $app;
        if ($app->getContainer()->redis->incr($redisKey) > 1) {
            return;
        }
        $app->getContainer()->redis->expire($redisKey, 6);
        $data = [
            'merchant_refs' => [$this->orderID],
        ];
        $data['params'] = json_encode($data);
        $data['merchant_no'] = $this->partnerID;
        $data['timestamp'] = time();
        $data['sign_type'] = 'MD5';
        $data['sign'] = $this->sign($data);

        $this->payUrl .= '/api/gateway/batch-query/order';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if ($this->httpCode == 201) {
            if ($code == 200) {
                $resultData = json_decode($result['params'], true)[0];
                //订单状态 status (1： 成功2： 待定5： 拒绝 )
                if ($resultData['status'] == 1) {
                    $status = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif ($resultData['status'] == 5) {
                    $status = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($resultData['amount'], 100);
                $fee = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $resultData['system_ref'],//第三方转账编号
                    '', $status, $fee, $message);
                return;
            }
        } elseif ($this->httpCode == 400 && $code == 400) {
            $msgStr = 'Order does not exist';
            if (strstr($message, $msgStr)) {
                //找不到订单
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', 0, $message);
                return;
            }
        }

        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew()
    {
        $this->payRequestUrl = $this->payUrl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);
    }

    //生成签名
    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        $str = $data['merchant_no'] . $data['params'] . $data['sign_type'] . $data['timestamp'] . $this->key;
        return md5($str);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter = $params;

        if ($this->sign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }
        $result = json_decode($params['params'], true);
        $amount = bcmul($result['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(：1： 成功2： 待定5： 拒绝 )
        if ($result['status'] == 1) {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif ($result['status'] == 5) {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $result['system_ref'],//第三方转账编号
            '', $status);
    }


    private function getBankName()
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
        return $banks[$this->bankCode];
    }

}
