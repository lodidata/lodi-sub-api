<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * MCOPAY代付
 */
class MCOPAY extends BASES
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
        $params = [
            'mchNo' => $this->partnerID,
            'appId' => $this->pubKey,
            'mchOrderNo' => $this->orderID,
            'ifCode' => '931',
            'entryType' => 'GCASH',//931	菲律宾二类钱包
            'amount' => bcdiv($this->money, 100),//代付金额，整数，以元为单位
            'currency' => $config_params['currency'] ?? 'PHP',
            "accountNo" => $this->bankCard,
            "accountName" => $this->bankUserName,
            "notifyUrl" => $this->payCallbackDomain . '/thirdAdvance/callback/mcopay',
            'reqTime' => time(),
            'version' => '1.0',
            'signType' => 'MD5',
        ];

        $params['sign'] = $this->sign($params);

        $this->payUrl .= '/api/transferOrder';
        $this->parameter = $params;

        $this->formPost();
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : false;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if ($this->httpCode == 200) {
            //true成功,false失败 转账状态: 1-转账中 3-转账失败
            if ($code === 0 && $result['data']['state'] == 1) {
                $this->return['code'] = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg'] = $message;
                $this->transferNo = $result['data']['transferId'];//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code'] = 886;
                $this->return['balance'] = 0;
                $this->return['msg'] = 'MCOPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code'] = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg'] = $message;
        $this->transferNo = '';//第三方订单号
    }

    public function getThirdBalance()
    {
        $this->payUrl .= "/api/balance/query";

        $params = [
            'mchNo' => $this->partnerID,
            'appId' => $this->pubKey,
            'reqTime' => time(),
            'version' => '1.0',
            'signType' => 'MD5',
        ];
        $params['sign'] = $this->sign($params);
        $this->parameter = $params;
        $this->formPost();
        $result = json_decode($this->re, true);

        $code = isset($result['code']) ? $result['code'] : '2';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            //code=0 代表查询成功,其他代表错误代码
            if ($code === 0) {
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($result['data']['balance'], 100);
                $this->return['msg'] = $message;
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    public function getTransferResult()
    {
        $this->payUrl .= '/api/transfer/query';

        $params = [
            'mchNo' => $this->partnerID,
            'appId' => $this->pubKey,
            'transferId' => $this->transferNo,
            'mchOrderNo' => $this->orderID,
            'reqTime' => time(),
            'version' => '1.0',
            'signType' => 'MD5',
        ];
        $params['sign'] = $this->sign($params);
        $this->parameter = $params;
        $this->formPost();
        $result = json_decode($this->re, true);

        $code = isset($result['code']) ? $result['code'] : 99999;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            //code=0 代表查询成功,其他代表错误代码
            if ($code === 0) {
                //订单状态,1代表等待处理,2代表处理成功,3代表处理失败.有错误发生时无此参数
                if ($result['data']['state'] == 2) {
                    $status = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif ($result['data']['state'] == 3) {
                    $status = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['amount'], 100);
                $fee = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $this->transferNo,//第三方转账编号
                    '', $status, $fee, $message);
                return;
            }

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    //验证回调签名
    public function sign($param)
    {
        unset($param['sign']);
        ksort($param);
        $str = '';
        foreach ($param as $k => $v) {
            if (is_null($v) || $v === '')
                continue;
            $str .= $k . '=' . $v . '&';
        }
        $sign_str = $str . 'key=' . $this->key;
        return strtoupper(md5($sign_str));
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     * @throws \Exception
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

        $real_money = bcmul($this->parameter['amount'], 100);//以分为单位

        //记录错误返回
        $message = isset($this->parameter['statusMsg']) ? $this->parameter['statusMsg'] : '';

        //订单状态.1代付中,2代付成功,3代付失败
        if ($this->parameter['state'] == 2) {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif ($this->parameter['state'] == 3) {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $this->parameter['transferId'],//第三方转账编号
            '', $status, 0, $message);
    }

    public function formPost()
    {
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
        ]);
        $response = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $response;
    }
}
