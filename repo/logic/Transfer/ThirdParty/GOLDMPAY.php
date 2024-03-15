<?php

namespace Logic\Transfer\ThirdParty;

use Utils\Utils;

class GOLDMPAY extends BASES
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
            'sid' => $this->partnerID,
            'outTradeNo' => $this->orderID,
            'amount' => bcdiv($this->money, 100, 2),
            'notifyUrl' => $this->payCallbackDomain . '/thirdAdvance/callback/goldmpay',
            'accountNo' => $this->bankCard,
            'accountName' => $this->bankUserName,
            'bankName' => 'gcash',
            'ifsc' => Utils::randStr(10)
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/v1/project-api/withdraw/apply';
        $this->parameter = $data;
        $this->basePostNew();
        
        $result = json_decode($this->re, true);
        $status = isset($result['code']) ? $result['code'] : '1';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        $code = 886;
        $this->transferNo = '';
        if ($this->httpCode == 200) {
            if ((int)$status === 100) {
                $code = 10500;
                $this->transferNo = $result['data']['orderNo'];//第三方订单号
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

        $this->return['code']    = 10509;
        $this->return['balance'] = 1000000;
        $this->return['msg']     = '';
        return ;
        $data['mchId'] = $this->partnerID;
        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl = "http://check.letspayfast.com/qaccount";
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['retCode']) ? $result['retCode'] : "FAIL";
        $message = isset($result['retMsg']) ? $result['retMsg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {

            $signParams = $result;
            unset($signParams['sign']);
            if (strtoupper($this->sign($signParams)) != $result['sign']) {
                throw new \Exception('Sign error');
            }

            if ($code == "SUCCESS") {
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
            'orderNo' => $this->transferNo,
            'sid' => $this->partnerID
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/v1/project-api/withdraw/query';

        $this->parameter = $data;
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : '1';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if ($this->httpCode == 200) {
            if ((int)$code === 100) {
                //订单状态 refCode (1： 成功)
                switch ($result['data']['status']) {
                    case '8001':
                    case '8002':
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    case '8003':
                        $status = 'paid';
                        $this->return = ['code' => 1, 'msg' => ''];
                        break;
                    case '8004':
                        $status = 'failed';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    default:
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                }

                $real_money = bcmul($result['data']['amount'], 100);
                $fee = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['data']['orderNo'],//第三方转账编号
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->parameter));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json;charset=utf-8',
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
        unset($data['sign']);

        ksort($data);
        $data = array_filter($data, function ($val) {
            return ($val !== "") && ($val !== 0) && ($val !== 'undefined');
        });

        $str = urldecode(http_build_query($data));
        $str .= '@key=' . $this->key;
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

        switch ($params['code']) {
            case '100':
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => '成功'];
                break;
            default:
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => $params['msg']];
                break;
        }

        $this->re = $this->return;
        $realMoney = bcmul($params['payAmount'], 100);//以分为单位
        $fee = $this->money - $realMoney;

        $this->updateTransferOrder($this->money, $realMoney, $params['tikuanNo'],//第三方转账编号
            '', $status, $fee);
    }

}
