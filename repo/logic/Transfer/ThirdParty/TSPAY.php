<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * TSPAY代付
 */
class TSPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $params          = [
            "appid"        => $this->partnerID, //商户号
            "out_trade_no" => $this->orderID,   //订单号
            "type"         => 1,
            "name"         => trim($this->bankUserName),
            "bank_account" => $this->bankCard,
            "bank_code"    => $this->getBankName(),
            "branch_code"  => $this->getBankName(),
            "email"        => 'transfer',
            "mobile"       => 'transfer',
            "amount"       => $this->money,   //代付金额
            "currency"     => "",
            "version"      => "v1.0",
            "notify_url"   => $this->payCallbackDomain . '/thirdAdvance/callback/tspay',   //回调地址
        ];
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if(!empty($config_params) && isset($config_params['currencyCode'])){
            $params['currency'] = $config_params['currencyCode'];
        }

        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/appclient/withdraw.do';

        $this->initParam($params);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code === 0) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['order_no'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'tspay:' . $message ?? '代付失败';
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
        $this->return['code']    = 10509;
        $this->return['balance'] = 3000000;
        $this->return['msg']     = "";
        return;
    }

    //代付订单查询
    public function getTransferResult() {
        $params = [
            'appId'      => $this->partnerID,
            'out_trade_no' => $this->orderID
        ];
        $this->payUrl .="/appclient/queryWithdraw.do";
        $this->initParam($params);
        $this->basePostNew();

        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            //订单状态：code:2为成功 -2为失败 其他为处理中
            if($code == 2){
                $status = 'paid';
                $this->return = ['code' => 1,  'msg' => $message];
            }elseif($code == -2){
                $status = 'failed';
                $this->return = ['code' => 0,  'msg' => $message];
            }else{
                $this->return = ['code' => 0,  'msg' => $message];
                return;
            }

            $real_money = $result['data']['amount'];
            $fee        = $this->money - $real_money;
            $this->updateTransferOrder(
                $this->money,
                $real_money,
                $result['data']['order_no'],
                '',
                $status,
                $fee,
                $message
            );
            return;

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:'.$this->httpCode.' code:'.$code.' message:'.$message];
    }

    //组装数组
    public function initParam($params = []) {
        $data            = $params;
        $data['sign']    = $this->sign($params);  //校验码
        $this->parameter = $data;
    }

    //验证回调签名
    public function sign($data) {
        unset($data['sign']);
        $str = '';
        ksort($data);

        foreach($data as $k => $v) {
            if($v === '') {
                continue;
            }
            $str .= $k . "=" . $v . "&";
        }
        $str     = rtrim($str, '&');
        $signStr = $str . '&key='. $this->key;
        return strtoupper(md5($signStr));
    }

    public function basePostNew($referer = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        // 请求头，可以传数组
        // curl_setopt($ch, CURLOPT_HEADER, $header);
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $output = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $output;
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }

        $real_money = 0;
        $orderNo = '';
        if($this->parameter['code'] === 0) {
            if($this->sign($params['data'])  != $params['data']['sign']) {
                throw new \Exception('Sign error');
            }
            $real_money = $this->parameter['data']['amount'];
            $orderNo = $this->parameter['data']['order_no'];
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        }elseif($this->parameter['code'] == '-1'){
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $orderNo,//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "BANXICO"         => "2001",
            "BANCOMEXT"       => "37006",
            "BANOBRAS"        => "37009",
            "BANJERCITO"      => "37019",
            "NAFIN"           => "37135",
            "HIPOTECARIA FED" => "37168",
            "BANAMEX"         => "40002",
            "BBVA BANCOMER"   => "40012",
            "SANTANDER"       => "40014",
            "HSBC"            => "40021",
            "BAJIO"           => "40030",
            "INBURSA"         => "40036",
            "MIFEL"           => "40042",
            "SCOTIABANK"      => "40044",
            "BANREGIO"        => "40058",
            "INVEX"           => "40059",
            "BANSI"           => "40060",
            "AFIRME"          => "40062",
            "BANORTE"         => "40072",
            "BANK OF AMERICA" => "40106",
            "MUFG"            => "40108",
            "JP MORGAN"       => "40110",
            "BMONEX"          => "40112",
            "VE POR MAS"      => "40113",
            "CREDIT SUISSE"   => "40126",
            "AZTECA"          => "40127",
            "AUTOFIN"         => "40128",
            "BARCLAYS"        => "40129",
            "COMPARTAMOS"     => "40130",
            "MULTIVA BANCO"   => "40132",
            "ACTINVER"        => "40133",
            "INTERCAM BANCO"  => "40136",
            "BANCOPPEL"       => "40137",
            "ABC CAPITAL"     => "40138",
            "CONSUBANCO"      => "40140",
            "VOLKSWAGEN"      => "40141",
            "CIBANCO"         => "40143",
            "BBASE"           => "40145",
            "BANKAOOL"        => "40147",
            "PAGATODO"        => "40148",
            "INMOBILIARIO"    => "40150",
            "DONDE"           => "40151",
            "BANCREA"         => "40152",
            "BANCO FINTERRA"  => "40154",
            "ICBC"            => "40155",
            "SABADELL"        => "40156",
            "SHINHAN"         => "40157",
            "MIZUHO BANK"     => "40158",
            "BANCO S3"        => "40160",
            "MONEXCB"         => "90600",
            "GBM"             => "90601",
            "MASARI"          => "90602",
            "VALUE"           => "90605",
            "VECTOR"          => "90608",
            "MULTIVA CBOLSA"  => "90613",
            "FINAMEX"         => "90616",
            "VALMEX"          => "90617",
            "PROFUTURO"       => "90620",
            "CB INTERCAM"     => "90630",
            "CI BOLSA"        => "90631",
            "FINCOMUN"        => "90634",
            "AKALA"           => "90638",
            "STP"             => "90646",
            "CREDICAPITAL"    => "90652",
            "KUSPIT"          => "90653",
            "UNAGRA"          => "90656",
            "ASP INTEGRA OPC" => "90659",
            "LIBERTAD"        => "90670",
            "CAJA POP MEXICA" => "90677",
            "CRISTOBAL COLON" => "90680",
            "CAJA TELEFONIST" => "90683",
            "FONDO (FIRA)"    => "90685",
            "INVERCAP"        => "90686",
            "FOMPED"          => "90689",
            "CLS"             => "90901",
            "INDEVAL"         => "90902",
            "CoDi Valida"     => "90903",
        ];
        return $banks[$this->bankCode];
    }
}
