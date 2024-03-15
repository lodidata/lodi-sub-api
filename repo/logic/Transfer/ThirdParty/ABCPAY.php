<?php

namespace Logic\Transfer\ThirdParty;

class ABCPAY extends BASES
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
            'app_id' => $this->key,
            'trade_no' => $this->orderID,
            'currency' => 'PHP',
            'money' => bcdiv($this->money, 100, 2),
            'bank' => $this->getBankName(),
            'name' => $this->bankUserName,
            'account' => $this->bankCard,
            'ip' => \Utils\Client::getIp(),
            'notify_url' => $this->payCallbackDomain . '/thirdAdvance/callback/abcpay'
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/api/withdraw/create';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $status = isset($result['status']) ? $result['status'] : false;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        $code = 886;
        $this->transferNo = '';
        if ($this->httpCode == 200) {
            if ($status === true) {
                $code = 10500;
                $this->transferNo = $result['data']['sn'];//第三方订单号
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

        $data['app_id'] = $this->key;
        $data['currency'] = 'PHP';
        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= "/api/account/balance";
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : false;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code === true) {
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($result['data'], 100);
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
            'app_id' => $this->key,
            'trade_no' => $this->orderID
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/api/withdraw/query';

        $this->parameter = $data;
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : false;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if ($this->httpCode == 200) {
            if ($code === true) {
                //代付处理状态，0：待处理 1：成功 -1：失败
                switch ($result['data']['status']) {
                    case 0:
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    case 1:
                        $status = 'paid';
                        $this->return = ['code' => 1, 'msg' => ''];
                        break;
                    case -1:
                        $status = 'failed';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    default:
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                }

                $real_money = bcmul($result['data']['money'], 100);
                $fee = bcmul($result['data']['fee'], 100);
                $this->updateTransferOrder($this->money, $real_money, $result['data']['sn'],//第三方转账编号
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
        unset($data['sign']);

        ksort($data);
        $str = urldecode(http_build_query($data));
        $str .= '&app_secret=' . $this->pubKey;
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

        //代付处理状态，1：成功 -1：失败
        if ((int)$params['status'] === 1) {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => '成功'];
        } else {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '失败'];
        }

        $this->re = $this->return;
        $realMoney = bcmul($params['money'], 100);//以分为单位
        $fee = bcmul($params['fee'], 100);//以分为单位

        $this->updateTransferOrder($this->money, $realMoney, $params['sn'],//第三方转账编号
            '', $status, $fee);
    }


    private function getBankName()
    {
        $banks = [
            "AUB" => "aub",
            "Starpay" => "stp",
            "ESB" => "esb",
            "PB" => "pdb",
            "PBC" => "pbc",
            "PBB" => "pbb",
            "PNB" => "pnb",
            "PSB" => "psb",
            "PTC" => "ptc",
            "SBC" => "sbc",
            "SBA" => "slb",
            "SSB" => "ssb",
            "UCPB SAVINGS BANK" => "usb",
            "Wealth Development Bank, Inc." => "wdb",
            "GrabPay" => "gp",
            "BOC" => "bc",
            "CTBC" => "ctbc",
            "CBS" => "cbs",
            "CBC" => "cbc",
            "ALLBANK (A Thrift Bank), Inc." => "ab",
            "BDO Network Bank, Inc." => "bnb",
            "Camalig" => "cb",
            "Gcash" => "GCASH",
            "Metropolitan Bank and Trust Co" => "mbt",
            "Omnipay" => "op",
            "Paymaya Philippines, Inc." => "pmp",
            "ING" => "ib",
            "BPI" => "bpi",
            "STP" => "stp",
            "SCB" => "scb",
            "UBPHPH" => "ubp",
        ];

        if (isset($banks[$this->bankCode])) {
            return $banks[$this->bankCode];
        } else {
            return 'GCASH';
        }
    }

}
