<?php

namespace Logic\Transfer\ThirdParty;

use Utils\Utils;

class BLIZZARDPAY extends BASES
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
            'appId' => $this->partnerID,
            'outOrderNo' => $this->orderID,
            'amount' => bcdiv($this->money, 100, 2),
            'bankName' => $this->getBankName(),
            'bankUserName' => $this->bankUserName,
            'bankCard' => $this->bankCard,
            'currency' => 'PHP',
            'callbackUrl' => $this->payCallbackDomain . '/thirdAdvance/callback/blizzardpay'
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/withdraw/apply';
        $this->parameter = $data;
        $this->basePostNew();
        
        $result = json_decode($this->re, true);
        $status = isset($result['code']) ? $result['code'] : '1';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        $code = 886;
        $this->transferNo = '';
        if ($this->httpCode == 200) {
            if ((int)$status === 200) {
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
        $data['appId'] = $this->partnerID;
        $data['currency'] = 'PHP';
        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= "/withdraw/balance";
        $this->formGet();
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : "1";
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if ((int)$code === 200) {
            $this->return['code'] = 10509;
            $this->return['balance'] = bcmul($result['data'], 100);
            $this->return['msg'] = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult()
    {

        $data = [
            'outOrderNo' => $this->orderID,
            'appId' => $this->partnerID
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/withdraw/query';

        $this->parameter = $data;
        $this->formGet();
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : '1';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if ($this->httpCode == 200) {
            if ((int)$code === 200) {
                //订单状态 refCode (1： 成功)
                switch ($result['data']['orderStatus']) {
                    case '0':
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    case '1':
                        $status = 'paid';
                        $this->return = ['code' => 1, 'msg' => ''];
                        break;
                    case '2':
                        $status = 'failed';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    default:
                        $status = 'failed';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                }

                $real_money = bcmul($result['data']['arrive'], 100);
                $fee = bcmul($result['data']['fee'], 100);
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);
    }

    public function formGet() {
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //释放curl句柄
        curl_close($ch);
        $this->re = $output;
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

        if ($this->sign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }

        switch ($params['orderStatus']) {
            case '0':
                $status = 'pending';
                $this->return = ['code' => 0, 'msg' => 'pending'];
                break;
            case '1':
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => 'paid'];
                break;
            case '2':
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => 'failed'];
                break;
            default:
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => 'failed'];
                break;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $this->money, $params['orderNo'],//第三方转账编号
            '', $status);
    }

    private function getBankName() {

        $banks = [
            "AUB" => "AUB",
            "Starpay" => "STP",
            "ESB" => "ESB",
            "PB" => "PDB",
            "PBC" => "PBC",
            "PBB" => "PBB",
            "PNB" => "PNB",
            "PSB" => "PSB",
            "PTC" => "PTC",
            "SBC" => "SBC",
            "SBA" => "SLB",
            "SSB" => "SSB",
            "UCPB SAVINGS BANK" => "USB",
            "United Coconut Planters Bank" => "UCPB",
            "GrabPay" => "GP",
            "BOC" => "BC",
            "CTBC" => "CTBC",
            "CBS" => "CBS",
            "CBC" => "CBC",
            "Camalig" => "CB",
            "Gcash" => "gcash",
            "Metropolitan Bank and Trust Co" => "mbt",
            "Omnipay" => "OP",
            "ING" => "IB",
            "BPI" => "bpi",
            "STP" => "STP",
            "SCB" => "SCB"
        ];
        if (isset($banks[$this->bankCode])) {
            return $banks[$this->bankCode];
        } else {
            return 'gcash';
        }
    }


}
