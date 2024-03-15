<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class TGPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data            = [
            'merchantId'    => $this->partnerID,
            'payMethod'     => '00000',
            'money'         => $this->money,
            'bizNum'        => $this->orderID,
            'account'       => $this->bankCard,
            'notifyAddress' => $this->payCallbackDomain . '/thirdAdvance/callback/tgpay',
        ];
        $data['sign']    = $this->sign($data);
        $this->payUrl    .= '/pay/order/paid';
        $this->parameter = $data;

        $this->basePostNew();

        $result  = json_decode($this->re, true);
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($result['success']) {
                $this->return['code']    = 10500;
                $this->return['balance'] = bcmul($result['data']['money'], 100);
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['sysOrderNum'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'TGPAY:' . $message ?? '代付失败';
                return;
            }
        }

        //$message = json_encode($result);
        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance() {


        $params         = [
            'merchantId' => $this->partnerID,
            'random'     => rand(0, 1000),
        ];
        $params['sign'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl   .= "/pay/merchant/balance";
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['success']) ? $result['success'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code) {
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['data']['balance'], 100);
                $this->return['msg']     = $message;
                return;
            }
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data         = [
            'merchantId' => $this->partnerID,
            'bizNum'     => $this->orderID,
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl    .= '/pay/order/paid/query';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code   = isset($result['success']) ? $result['success'] : 1;

        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($result['success']) {
                //订单状态 status (1、成功,2、失败,3、处理中，4、关闭 )
                if($result['data']['status'] == '1') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($result['data']['status'] == '2') {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } elseif($result['data']['status'] === '3') {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['data']['money'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['data']['sysBizNum'],//第三方转账编号
                    '', $status, $fee, $message);
                return;
            }
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
    }

    public function basePostNew() {
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

        $response        = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }

    //生成签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach($data as $k => $v) {
            if(is_null($v) || $v === '')
                continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $sign_str = $str . '&key=' . $this->key;
        return strtoupper(md5($sign_str));
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
        $amount     = bcmul($params['money'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：PROCESSING 处理中FAILED 失败SUCCESS 成功)
        if($this->parameter['status'] == '1') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['status'] == '3') {
            $this->return = ['code' => 0, 'msg' => '代付处理中'];
        }elseif($this->parameter['status'] == '2') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['sysBizNum'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {
        $banks = [
            "KBANK" => "Kasikornbank",
            "BBL"   => "BANGKOK BANK",
            "BAAC"  => "Bank for Agriculture and Agricultural Cooperatives",
            "BAY"   => "Bank of Ayudhya",
            "BOC"   => "Bank of China (Thai)",
            "CIMB"  => "CIMB Thai Bank",
            "CITI"  => "Citibank Thailand",
            "GHB"   => "Government Housing Bank",
            "ICBC"  => "ICBC Bank",
            "TIBT"  => "Islamic Bank of Thailand",
            "KKB"   => "KIATNAKIN BANK",
            "KTB"   => "Krung Thai Bank",
            "LHBA"  => "LH Bank",
            "SCBT"  => "Standard Chartered",
            "SMTB"  => "Sumitomo Mitsui Trust Bank",
            "TTB"   => "TMBThanachart Bank",
            "GSB"   => "Government Savings Bank",
            "SCB"   => "The Siam Commercial Bank",
            "TCRB"  => "Thai Credit Retail Bank",
            "TISCO" => "TISCO Bank",
            "UOB"   => "United Overseas Bank"
        ];
        return $banks[$this->bankCode];
    }

}
