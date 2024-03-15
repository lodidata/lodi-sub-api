<?php

namespace Logic\Transfer\ThirdParty;

use http\Env\Request;
use Logic\Recharge\Recharge;

class ANTPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $merchant_key = '';
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if(!empty($config_params) && isset($config_params['merchant_key'])){
            $merchant_key = $config_params['merchant_key'];
        }
        //组装参数
        $data            = [
            'merchant_id'   => $this->partnerID,
            'amount'        => bcdiv($this->money,100,2),
            'order_no'      => $this->orderID,
            'bank_no'       => $this->getBankName(),
            'account_name'  => $this->bankUserName,
            'account_type'  => 1,
            'account_no'    => $this->bankCard,
        ];
        $data['sign'] = $this->buildRSASignByPrivateKey($this->sign($data, $merchant_key));
        $data['notify_url'] = $this->payCallbackDomain . '/thirdAdvance/callback/antpay';
        $this->payUrl    .= '/api/addWithdraw';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($result['status'] == '1') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['trans_id'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'ANTPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance() {
        $merchant_key = '';
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if(!empty($config_params) && isset($config_params['merchant_key'])){
            $merchant_key = $config_params['merchant_key'];
        }
        $params = [
            'merchant_id' => $this->partnerID,
        ];
        $params['sign'] = $this->buildRSASignByPrivateKey($this->sign($params, $merchant_key));

        $this->parameter = $params;

        $this->payUrl .= "/api/balance";
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {

            $this->return['code']    = 10509;
            $this->return['balance'] = bcmul($result['balance'], 100);
            $this->return['msg']     = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $merchant_key = '';
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if(!empty($config_params) && isset($config_params['merchant_key'])){
            $merchant_key = $config_params['merchant_key'];
        }
        $data         = [
            'merchant_id' => $this->partnerID,
            'order_no' => $this->orderID,
        ];
        $data['sign'] = $this->buildRSASignByPrivateKey($this->sign($data, $merchant_key));

        $this->payUrl    .= '/api/tradequery';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            //订单状态 status (1 创建订单成功 2 代收/代付成功  3 失败)
            if($result['status'] == '2') {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif($result['status'] == '3') {
                $status       = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else {
                $this->return = ['code' => 0, 'msg' => $message];
                return;
            }

            $real_money = bcmul($result['amount'], 100);
            $fee        = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $result['trans_id'],//第三方转账编号
                '', $status, $fee,$message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8'
        ]);
        // 请求头，可以传数组
        // curl_setopt($ch, CURLOPT_HEADER, $header);
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response        = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;

        curl_close($ch);
    }

    //生成签名
    function sign($array, $merchant_key)
    {
        unset($array['sign']);
        unset($array['s']);
        $result = "";
        try {
            $keys = array_keys($array);
            sort($keys);
            $str = "";
            foreach ($keys as $key) {
                $val = $array[$key];
                if (!empty($val) && $key != "sign") {
                    $str .= $key . "=" . $val . "&";
                }
            }
            $str = $str . "key=" . $merchant_key;
            $result = $str;
        } catch (Exception $e) {
            return null;
        }
        return $result;
    }

    /**
     * 私钥加密
     * @param string $data 要加密的数据
     * @return 加密后的字符串
     */
    public function buildRSASignByPrivateKey($data)
    {
        //获取完整的私钥
        $pem = "-----BEGIN RSA PRIVATE KEY-----" . PHP_EOL;

        $pem .= chunk_split($this->key, 64, PHP_EOL);

        $pem .= "-----END RSA PRIVATE KEY-----" . PHP_EOL;
        $privateKey = openssl_pkey_get_private($pem);

        $privatekey = openssl_get_privatekey($privateKey);
        //php5.4+ OPENSSL_ALGO_SHA256
        openssl_sign($data, $result, $privatekey, OPENSSL_ALGO_SHA256);

        $result = base64_encode($result);

        return $result;
    }

    /**
     * 私钥解密
     * @param string $data 要解密的数据
     * @return bool $bool 解密后的字符串
     */
    public function buildRSASignByPublicKey($data, $sign)
    {
        $pem = "-----BEGIN PUBLIC KEY-----" . PHP_EOL;

        $pem .= chunk_split($this->pubKey, 64, PHP_EOL);

        $pem .= "-----END PUBLIC KEY-----" . PHP_EOL;

        $public_key = openssl_pkey_get_public($pem);
        $publicKey = openssl_get_publickey($public_key);

        $result = openssl_verify($data, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);
        if($result == 1){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $merchant_key = '';
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if(!empty($config_params) && isset($config_params['merchant_key'])){
            $merchant_key = $config_params['merchant_key'];
        }
        $this->parameter = $params;
        if(!$this->buildRSASignByPublicKey($this->sign($params,$merchant_key), $params['sign'])){
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(：2 - 成功 : 3 – 失敗。)
        if($this->parameter['status'] == '2') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['status'] == '3') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['trans_id'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            'RB'  => 'RB',
            'PBB' => 'PBB',
            'PNB' => 'PNB',
            'PBC' => 'PBC',
            'EB'  => 'EB',
            'ESB' => 'ESB',
            'UCPB SAVINGS BANK' => 'UCPB SAVINGS BANK',
            'ERB' => 'ERB',
            'PSB' => 'PSB',
            'Rizal Commercial Banking Corporation' => 'Rizal Commercial Banking Corporation',
            'Wealth Development Bank, Inc.' => 'Wealth Development Bank, Inc.',
            'SSB' => 'SSB',
            'SBA' => 'SBA',
            'RBG' => 'RBG',
            'Queen City Development Bank, Inc.' => 'Queen City Development Bank, Inc.',
            'MB' => 'MB',
            'PB' => 'PB',
            'PTC' => 'PTC',
            'Starpay' => 'Starpay',
            'AUB' => 'AUB',
            'Yuanta Savings Bank, Inc.' => 'Yuanta Savings Bank, Inc.',
            'United Coconut Planters Bank' => 'United Coconut Planters Bank',
            'PVB' => 'PVB',
            'SBC' => 'SBC',
            'GrabPay' => 'GrabPay',
            'BOC' => 'BOC',
            'CTBC' => 'CTBC',
            'BPI' => 'BPI',
            'ING' => 'ING',
            'Metropolitan Bank and Trust Co' => 'Metropolitan Bank and Trust Co',
            'Landbank of the Philippines' => 'Landbank of the Philippines',
            'Banco De Oro Unibank, Inc.' => 'Banco De Oro Unibank, Inc.',
            'CBC' => 'CBC',
            'Binangonan Rural Bank Inc' => 'Binangonan Rural Bank Inc',
            'Maybank Philippines, Inc.' => 'Maybank Philippines, Inc.',
            'Omnipay' => 'Omnipay',
            'ALLBANK (A Thrift Bank), Inc.' => 'ALLBANK (A Thrift Bank), Inc.',
            'ISLA Bank (A Thrift Bank), Inc.' => 'ISLA Bank (A Thrift Bank), Inc.',
            'CSB' => 'CSB',
            'CBS' => 'CBS',
            'Chinabank' => 'Chinabank',
            'Bangko Mabuhay (A Rural Bank), Inc.' => 'Bangko Mabuhay (A Rural Bank), Inc.',
            'Allied Banking Corp' => 'Allied Banking Corp',
            'Paymaya Philippines, Inc.' => 'Paymaya Philippines, Inc.',
            'Cebuana Lhuillier Rural Bank, Inc.' => 'Cebuana Lhuillier Rural Bank, Inc.',
            'Partner Rural Bank (Cotabato), Inc.' => 'Partner Rural Bank (Cotabato), Inc.',
            'Gcash' => 'GCASH',
            'DBI' => 'DBI',
            'Camalig' => 'Camalig',
            'BPI Direct Banko, Inc., A Savings Bank' => 'BPI Direct Banko, Inc., A Savings Bank',
        ];
        return $banks[$this->bankCode];
    }
}
