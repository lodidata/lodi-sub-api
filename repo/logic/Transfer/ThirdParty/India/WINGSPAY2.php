<?php

namespace Logic\Transfer\ThirdParty\India;

use Logic\Transfer\ThirdParty\BASES;

class WINGSPAY2 extends BASES
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
        $userId = \Model\FundsWithdraw::where('trade_no', $this->withdrawOrder)->value('user_id');
        //组装参数
        $data = [
            'amount'      => $this->money,
            'merchantId'   => $this->partnerID,
            'orderId'  => $this->orderID,
            'timestamp'   => time() * 1000,
            'notifyUrl'   => $this->payCallbackDomain . '/thirdAdvance/callback/india/wingspay2',
            'outType' => 'IMPS',
            'accountNumber' =>$this->bankCard,
            'accountHolder' => $this->bankUserName,
            'ifsc' => $this->ifscCode
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/api/payout/createOrder';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $status = isset($result['code']) ? $result['code'] : 'FAIL';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        $code = 886;
        $this->transferNo = '';
        if ($this->httpCode == 200) {
            if ($status == 100) {
                $code = 10500;
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
        $data = [
            'merchantId' => $this->partnerID,
            'timestamp' => time() * 1000
        ];
        $data['sign'] = md5($data['merchantId'].$data['timestamp'].$this->key);
        $this->parameter = $data;
        $this->payUrl .= "/api/payout/balance";
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : "FAIL";
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code == 100) {
                $this->return['code'] = 10509;
                $this->return['balance'] = $result['balance'];
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
            'merchantId' => $this->partnerID,
            'orderId' => $this->orderID,
            'timestamp'  => time() * 1000,
        ];

        $this->payUrl .= '/api/payout/status';

        $this->parameter = $data;
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 'error';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if ($this->httpCode == 200) {
            if ($code == 100) {
                switch ($result['status']) {
                    case '0':
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    case '2':
                        $status = 'paid';
                        $this->return = ['code' => 1, 'msg' => ''];
                        break;
                    case '3':
                    case '4':
                        $status = 'failed';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    default:
                        $status = 'failed';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                }

                $real_money = bcmul($this->money, 100);
                $this->updateTransferOrder($this->money, $real_money, $this->orderID,//第三方转账编号
                    '', $status, 0, $message);
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
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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

        return md5($data['amount'].$data['merchantId'].$data['orderId'].$data['timestamp'].$this->key);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter = $params;

        if ($params['sign'] != $this->sign($params)) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }

        switch ($params['status']) {
            case '2':
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => ''];
                break;
            case '3':
            case '4':
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => '失败'];
                break;
            default:
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => '失败'];
                break;
        }


        $this->re = $this->return;
        $realMoney = $params['amount'];//以分为单位

        $this->updateTransferOrder($this->money, $realMoney, $params['payOrderId'],//第三方转账编号
            '', $status);
    }

}
