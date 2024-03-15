<?php

namespace Logic\Transfer\ThirdParty;

use http\Env\Request;
use Logic\Recharge\Recharge;

class GLOBALPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $currencyCode = 'PHP';
        $config_params = !empty($this->data['params']) ? json_decode($this->data['params'],true) : [];
        if(!empty($config_params) && isset($config_params['currency_code'])){
            $currencyCode = $config_params['currency_code'];
        }

        //组装参数
        $data            = [
            'mer_no'        => $this->partnerID,
            'mer_order_no'  => $this->orderID,
            'acc_no'        => $this->bankCard,
            'acc_name'      => $this->bankUserName,
            'ccy_no'        => $currencyCode,
            'order_amount'  => bcdiv($this->money,100,2),
            'bank_code'     => $this->getBankName(),
            'mobile_no'     => $this->mobile,
            'summary'       => 'summary',
            'notifyUrl'     => $this->payCallbackDomain . '/thirdAdvance/callback/globalpay'
        ];
        $data['sign'] = $this->sign($data);
        $this->payUrl    .= '/withdraw/singleOrder';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['err_msg']) ? $result['err_msg'] : 'errorMsg:' . (string)$this->re;
        $status = isset($result['status']) ? $result['status'] : 'SUCCESS';

        if ($status == "SUCCESS") {
            $this->return['code'] = 10500;
            $this->return['balance'] = $this->money;
            $this->return['msg'] = '';
            $this->transferNo = $result['order_no'];//交易ID号
        } else {
            $message = isset($message) ? $message : 'errorMsg:'.(string)json_encode($result, JSON_UNESCAPED_UNICODE);
            $this->updateTransferOrder(
                $this->money,
                0,
                '',
                '',
                'failed',
                null,
                $message
            );
            $this->return['code'] = 886;
            $this->return['balance'] = 0;
            $this->return['msg'] = 'GLOBALPAY:' . $message ?? '代付失败';
        }
    }

    //查询余额
    public function getThirdBalance() {
        $params = [
            'mer_no' => $this->partnerID,
            'request_time' => date('YmdHis', time()),
            'request_no' => date('YmdHis', time())
        ];
        $params['sign'] = $this->makeMd5Sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/withdraw/balanceQuery";
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['query_status']) ? $result['query_status'] : "SUCCESS";
        $message = isset($result['query_err_msg']) ? $result['query_err_msg'] : 'errorMsg:' . (string)$this->re;

        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];

        if($code === "SUCCESS") {
            $balance = 0;
            $tmp=[];
            if(!empty($result['list'])) {
                foreach($result['list'] as $v) {
                    if($v['ccy_no'] == $config_params['currency_code']){
                        $balance = $v['balance'];
                        $tmp = $v;
                    }
                }
            }

            $this->return['code']    = 10509;
            $this->return['balance'] = bcmul($balance ,100);
            $this->return['msg']     = json_encode($tmp);
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data         = [
            'mer_no' => $this->partnerID,
            'order_no' => $this->orderID,
            'request_time' => date('YmdHis', time()),
            'request_no' => date('YmdHis', time())
        ];
        $data['sign'] = $this->makeMd5Sign($data);

        $this->payUrl    .= '/withdraw/singleQuery';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['query_err_msg']) ? $result['query_err_msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            //订单状态 status (1 创建订单成功 2 代收/代付成功  3 失败)
            if ($result['query_status'] == 'SUCCESS') {
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif ($result['query_status'] == 'FAIL') {
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else {
                $this->return = ['code' => 0, 'msg' => $message];
                return;
            }

            $real_money = bcmul($result['amount'], 100);
            $fee = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $result['order_no'],//第三方转账编号
                '', $status, $fee, $message);
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew() {
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }

    //生成签名
    function sign($data)
    {
        unset($data['sign']);

        ksort($data);
        $str = '';
        foreach ($data as $k => $v){
            if(!empty($v)){
                $str .= $k.'='.$v.'&';
            }
        }
        $str = rtrim($str,'&');

        $pem = chunk_split($this->key, 64, "\n");
        $pem = "-----BEGIN RSA PRIVATE KEY-----\n" . $pem . "-----END RSA PRIVATE KEY-----\n";
        $private_key = openssl_pkey_get_private($pem);

        $crypto = '';
        foreach (str_split($str, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $private_key);
            $crypto .= $encryptData;
        }
        $encrypted = base64_encode($crypto);
        $return = str_replace(array('+','/','='),array('-','_',''),$encrypted);

        return $return;
    }

    /**
     * 私钥解密
     * @param string $data 要解密的数据
     * @return bool $bool 解密后的字符串
     */
    function makeMd5Sign($data){
        $config    = Recharge::getThirdConfig('globalpay');

        unset($data['sign']);

        ksort($data);
        $str = '';
        foreach ($data as $k => $v){
            if(!empty($v)){
                $str .= $k.'='.$v.'&';
            }
        }

        $str .='key='.$config['pub_key'];

        return md5($str);
    }

    /**
     * 私钥解密
     * @param string $data 要解密的数据
     * @return bool $bool 解密后的字符串
     */
    function revifyMd5Sign($data, $sign){
        $config    = Recharge::getThirdConfig('globalpay');

        unset($data['sign']);

        ksort($data);
        $str = '';
        foreach ($data as $k => $v){
            if(!empty($v)){
                $str .= $k.'='.$v.'&';
            }
        }

        $str .='key='.$config['pub_key'];

        if($sign != md5($str)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;
        if(!$this->revifyMd5Sign($params, $params['sign'])){
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['order_amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态
        if($this->parameter['status'] == 'SUCCESS') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['status'] == 'FAIL') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['mer_order_no'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            'HSBC' => 'HSBC',
            'SCB' => 'SCB',
            'BANK OF AMERICA' => '51755',
            'AZTECA' => 'MXNAZTECA',
            'BANAMEX'  => 'MXNBANAMEX',
            'BANCOMEXT' => 'MXNBCT',
            'BANCOMEXT' => 'MXNBCT',
            'BANCOPPEL' => 'MXNBANCOPPEL',
            'BANORTE' => 'MXNBANORTE',
            'BANREGIO' => 'MXNBANREGIO',
            'INBURSA' => 'MXNIBA',
            'SANTANDER' => 'MXNSANTANDER',
            'SCOTIABANK' => 'MXNSCOTIABANK',
            'STP' => 'MXNSTP',
            'AUB' => 'AUB',
            'Starpay' => 'Starpay',
            'UnionBank EON' => 'UnionBankEON',
            'EB' => 'EB',
            'ESB' => 'ESB',
            'MB' => 'MB',
            'ERB' => 'ERB',
            'PB' => 'PB',
            'PBC' => 'PBC',
            'PBB' => 'PBB',
            'PNB' => 'PNB',
            'PSB' => 'PSB',
            'PTC' => 'PTC',
            'PVB' => 'PVB',
            'RBG' => 'RBG',
            'Rizal Commercial Banking Corporation' => 'RCBC',
            'RB' => 'RB',
            'SBC' => 'SBC',
            'SBA' => 'SBA',
            'SSB' => 'SSB',
            'UCPB SAVINGS BANK' => 'UCPBSAVINGSBANK',
            'Queen City Development Bank, Inc.' => 'QCDBI',
            'United Coconut Planters Bank' => 'UCPB',
            'Wealth Development Bank, Inc.' => 'WDBI',
            'Yuanta Savings Bank, Inc.' => 'YSBI',
            'GrabPay' => 'GrabPay',
            'Banco De Oro Unibank, Inc.' => 'BDOUI',
            'Bangko Mabuhay (A Rural Bank), Inc.' => 'BMI',
            'BOC' => 'BOC',
            'CTBC' => 'CTBC',
            'Chinabank' => 'Chinabank',
            'CBS' => 'CBS',
            'CBC' => 'CBC',
            'ALLBANK (A Thrift Bank), Inc.' => 'ALLBANK',
            'BDO Network Bank, Inc.' => 'BNBI',
            'Binangonan Rural Bank Inc' => 'BRBI',
            'Camalig' => 'Camalig',
            'DBI' => 'DBI',
            'Gcash' => 'GlobeGcash',
            'Cebuana Lhuillier Rural Bank, Inc.' => 'CLRBI',
            'ISLA Bank (A Thrift Bank), Inc.' => 'ISLABANK',
            'Landbank of the Philippines' => 'LOTP',
            'Maybank Philippines, Inc.' => 'MPI',
            'Metropolitan Bank and Trust Co' => 'MBATC',
            'Omnipay' => 'Omnipay',
            'Partner Rural Bank (Cotabato), Inc.' => 'PRBI',
            'Paymaya Philippines, Inc.' => 'PPI',
            'Allied Banking Corp' => 'AlliedBankingCorp',
            'ING' => 'ING',
            'BPI Direct Banko, Inc., A Savings Bank' => 'BDBIASB',
            'CSB' => 'CSB',
            'BPI' => 'BPI'
        ];
        return $banks[$this->bankCode];
    }
}
