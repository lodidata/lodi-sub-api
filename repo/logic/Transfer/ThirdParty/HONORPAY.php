<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Utils;

class HONORPAY extends BASES
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
            'account' => $this->partnerID,
            'orderNo' => $this->orderID,
            'withdrawalType' => 'bankcard',
            'amount' => bcdiv($this->money, 100, 2),
            'nonceStr' => Utils::randStr(),
            'notifyURL' => $this->payCallbackDomain . '/thirdAdvance/callback/honorpay',
            'bankName' => $this->bankCode,
            'bankNumber' => $this->bankCard,
            'bankHolder' => $this->bankName
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/api/withdraw/request';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code = isset($result['retCode']) ? $result['retCode'] : 1;
        $message = isset($result['retMsg']) ? $result['retMsg'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if ($this->httpCode == 200) {
            //true成功,false失败
            if ($code === '0') {
                $this->return['code'] = 10500;
                $this->return['balance'] = bcmul($result['amount'], 100);   // 代付申请金额
                $this->return['msg'] = $message;
                $this->transferNo = $result['uuid'];     //第三方订单号
                $this->fee = bcmul($result['fee'], 100);      // 手续费
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code'] = 886;
                $this->return['balance'] = 0;
                $this->return['msg'] = 'HONORPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code'] = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg'] = $message;
        $this->transferNo = '';   //第三方订单号
    }

    //查询余额
    public function getThirdBalance()
    {

        $data = [
            'account' => $this->partnerID
        ];

        $this->parameter = $data;

        $this->payUrl .= "/api/withdraw/cash_balance";
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['retCode']) ? $result['retCode'] : 1;
        $message = isset($result['retMsg']) ? $result['retMsg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code === 0) {
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
            'account' => $this->partnerID,
            'orderNo' => $this->orderID,
            'nonceStr' => Utils::randStr(),
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl .= '/api/withdraw/status';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code = isset($result['retCode']) ? $result['retCode'] : 1;
        $message = isset($result['retMsg']) ? $result['retMsg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200 && $code === 0) {
            //订单状态 status (0 = 申请中, 1 = 处理中, 2 = 完成, 3 = 已取消 )
            $third_no = $result['uuid'];
            $real_money = bcmul($result['amount'], 100);    // 金额
            $fee = bcmul($result['fee'], 100);  // 手续费
            switch ($result['status']) {
                case '0':
                case '1':
                    $status = 'pending';
                    $this->return = ['code' => 0, 'msg' => $message];
                    break;
                case '2':
                    $status = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                    break;
                default:
                    $status = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                    break;
            }

            $this->updateTransferOrder($this->money, $real_money, $third_no, //第三方转账编号
                '', $status, $fee, $message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $this->key
        ));

        $response = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->curlError = curl_error($ch);
        curl_close($ch);
        $this->re = $response;
    }

    //生成签名
    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        ksort($data);
        $str = urldecode(http_build_query($data)) . '&key=' . $this->pubKey;
        return strtoupper(hash_hmac("sha256", $str, $this->pubKey));
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter = $params;
        $sign = $params['sign'];
        unset($params['sign']);

        if ($this->sign($params) != $sign) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }
        $amount = bcmul($params['amount'], 100);//以分为单位
        $fee = bcmul($params['fee'], 100);

        //订单状态付款状态(1 = 完成, 2 = 已取消)
        if ($params['status'] == 1) {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } else {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => $params['message']];
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $amount, $params['uuid'],//第三方转账编号
            '', $status, $fee, $params['message'] ?? '');
    }

}
