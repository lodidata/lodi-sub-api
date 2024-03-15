<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class WOWPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data = [
            'mch_id'          => $this->partnerID,
            'mch_transferId'  => $this->orderID,
            'transfer_amount' => bcdiv($this->money, 100, 2),
            'apply_date'      => date('Y-m-d H:i:s'),
            'bank_code'       => 'GCASH',
            'receive_name'    => $this->bankUserName,
            'receive_account' => $this->bankCard,
            'back_url'        => $this->payCallbackDomain . '/thirdAdvance/callback/wowpay',
        ];

        $data['sign']        = $this->sign($data);
        $data['sign_type']   = 'MD5';
        $this->payUrl        .= '/pay/transfer';
        $this->parameter     = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code    = isset($result['respCode']) ? $result['respCode'] : 1;
        $message = isset($result['errorMsg']) ? $result['errorMsg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($code == 'SUCCESS') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['tradeNo'];//第三方订单号
                //成功就直接返回了
                return;
            }
        }else{
            $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
            $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
            $this->return['code']    = 886;
            $this->return['balance'] = 0;
            $this->return['msg']     = 'WOWPAY:' . $message ?? '代付失败';
            return;
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance() {
        $data = [
            'mch_id' => $this->partnerID,
        ];
        $data['sign']        = $this->sign($data);
        $data['sign_type']   = 'MD5';
        $this->payUrl        .= '/query/balance';
        $this->parameter     = $data;
        $this->basePostNew();
        $result  = json_decode($this->re, true);

        $code    = isset($result['respCode']) ? $result['respCode'] : false;
        $message = isset($result['errorMsg']) ? $result['errorMsg'] : 'errorMsg:' . (string)$this->re;


        if($this->httpCode == 200) {
            if($code) {
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['availableAmount'], 100, 0);
                $this->return['msg']     = $message;
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data = [
            'mch_id' => $this->partnerID,
            'mch_transferId' => $this->orderID,
        ];
        $data['sign']        = $this->sign($data);
        $data['sign_type']   = 'MD5';
        $this->payUrl        .= '/query/transfer';
        $this->parameter     = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code    = isset($result['respCode']) ? $result['respCode'] : 1;
        $message = isset($result['errorMsg']) ? $result['errorMsg'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if($this->httpCode == 200) {
            if($code == 'SUCCESS'){
                //订单状态 tradeResult (0 申请成功 1 转账成功 2 转账失败 3 转账拒绝 4 处理中)
                if($result['tradeResult'] == 1) {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($result['tradeResult'] == 2 || $result['tradeResult'] == 3) {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['transferAmount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['tradeNo'],//第三方转账编号
                    '', $status, $fee, $message);
                return;
            }
        }

        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew() {
        $this->payRequestUrl = $this->payUrl;
        $ch                  = curl_init();
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

        $response        = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }

    //生成签名
    public function sign($param) {
        unset($param['sign']);
        unset($param['sign_type']);
        unset($param['signType']);
        unset($param['merRetMsg']);
        unset($param['s']);

        $newParam = $param;
        if (!empty($newParam)) {
            $fields = array_keys($newParam);
            $sortParam = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                if($newParam[$v] === ''){
                    continue;
                }
                $sortParam[] = $v . '=' . $newParam[$v];
            }
            $originalString = implode('&', $sortParam) . '&key=' . $this->key;
        } else {
            $originalString = $this->key;
        }

        return md5($originalString);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;

        if($this->sign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $result= $params;
        $amount     = bcmul($result['transferAmount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(：1： 成功2： 失败 )
        if($result['tradeResult'] == 1) {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($result['tradeResult'] == 2) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $result['tradeNo'],//第三方转账编号
            '', $status);
    }

}
