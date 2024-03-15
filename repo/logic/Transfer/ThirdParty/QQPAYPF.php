<?php

namespace Logic\Transfer\ThirdParty;

class QQPAYPF extends BASES
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
            'type' => 'api',
            'mchId' => $this->partnerID,
            'mchTransNo' => $this->orderID,
            'amount' => bcdiv($this->money, 100, 2),
            'notifyUrl' => $this->payCallbackDomain . '/thirdAdvance/callback/qqpaypf',
            'accountName' => $this->bankUserName,
            'accountNo' => $this->bankCard,
            'bankCode' => $this->getBankName(),
            'remarkInfo' => 'email:520155@gmail.com/phone:9784561230/mode:bank'
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/apitrans';
        $this->parameter = $data;
        $this->basePostNew();
        
        $result = json_decode($this->re, true);
        $status = isset($result['retCode']) ? $result['retCode'] : 'FAIL';
        $message = isset($result['retMsg']) ? $result['retMsg'] : 'errorMsg:' . (string)$this->re;

        $code = 886;
        $this->transferNo = '';
        if ($this->httpCode == 200) {
            if ($status == "SUCCESS") {
                $code = 10500;
                $this->transferNo = $result['mchTransNo'];//第三方订单号
            }
        } else {
            $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
        }

        if ($code != 10500) {
            $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
        }
        $this->return['code'] = $code;
        $this->return['balance'] = $this->money;
        $this->return['msg'] = $message;
    }

    //查询余额
    public function getThirdBalance()
    {

        $data['mchId'] = $this->partnerID;
        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl = "http://check.letspayfast.com/qaccount";
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['retCode']) ? $result['retCode'] : "FAIL";
        $message = isset($result['retMsg']) ? $result['retMsg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code == "SUCCESS") {
                $signParams = $result;
                unset($signParams['sign']);
                if (strtoupper($this->sign($signParams)) != $result['sign']) {
                    throw new \Exception('Sign error');
                }
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($result['balance'], 100);
                $this->return['msg'] = $message;
                return;
            }
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult()
    {

        $data = [
            'mchTransNo' => $this->orderID,
            'mchId' => $this->partnerID
        ];

        $data['sign'] = $this->sign($data);

        $this->payUrl = 'http://check.letspayfast.com/qtransorder';

        $this->parameter = $data;
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['retCode']) ? $result['retCode'] : 'error';
        $message = isset($result['retMsg']) ? $result['retMsg'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if ($this->httpCode == 200) {
            if ($code == 'SUCCESS') {
                //订单状态 refCode (1： 成功)
                switch ($result['status']) {
                    case 1:
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    case 2:
                        $status = 'paid';
                        $this->return = ['code' => 1, 'msg' => ''];
                        break;
                    case 3:
                        $status = 'failed';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    default:
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                }

                $real_money = bcmul($result['amount'], 100);
                $fee = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['mchTransNo'],//第三方转账编号
                    '', $status, $fee, $message);
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

        ksort($data);
        $str = urldecode(http_build_query($data));
        $str .= '&key=' . $this->key;
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

        $signParams = $params;
        unset($signParams['sign'], $signParams['msg']);

        if (strtoupper($this->sign($signParams)) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }

        //订单状态付款状态：(1 处理中 2 已打款 3已驳回)
        switch ($params['status']) {
            case 1:
                $status = 'pending';
                $this->return = ['code' => 0, 'msg' => '处理中'];
                break;
            case 2:
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => '成功'];
                break;
            case 3:
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => '失败'];
                break;
            default:
                $this->return = ['code' => 0, 'msg' => '失败'];
                return;
        }


        $this->re = $this->return;
        $realMoney = bcmul($params['amount'], 100);//以分为单位
        $fee = $this->money - $realMoney;

        $this->updateTransferOrder($this->money, $realMoney, $params['mchTransNo'],//第三方转账编号
            '', $status, $fee);
    }


    private function getBankName()
    {
        global $app;
        $ci = $app->getContainer();
        $country_code = '';
        if(isset($ci->get('settings')['website']['site_type'])){
            $country_code = $ci->get('settings')['website']['site_type'];
        }

        if($country_code != 'ncg'){
            $banks = [
                'GrabPay' => 'GrabPay',
                'Gcash' => 'Gcash',
                'Paymaya Philippines, Inc.' => 'Paymaya'
            ];
            return $banks[$this->bankCode];
        }else{
            return 'Gcash';
        }
    }

}
