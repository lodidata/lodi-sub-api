<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * YOUPAY代付
 */
class YOUPAY extends BASES
{
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg(){
        return 'success';
    }

    //请求代付接口
    public function runTransfer()
    {
        if($this->money % 100){
            $message = 'Transfer only supports integer';
            $this->updateTransferOrder(
                $this->money,
                0,
                '',
                '',
                'failed',
                null,
                $message
            );

            throw new \Exception('Transfer only supports integer');
            
        }

        $params = array(
            'orderNo'      => $this->orderID, //是	string	商户订单号 Merchant order number
            'amount'       => bcdiv($this->money, 100),//代付金额 (单位：฿，不支持小数；代付金额范围请与优付宝平台确认)
            'name'         => $this->bankUserName, //收款人姓名 (示例：张三)
            'bankName'     => $this->getBankName(),  //string 收款银行名称 (示例：中国建设银行)
            'bankAccount'  => $this->bankCard, // string 收款银行账号 (示例：6227888888888888)
            'bankBranch'   => null,
            'memo'         => null,
            'mobile'       => null,
            'datetime'     => date('Y-m-d H:i:s'), // string (date-time) 日期时间 (格式:2020-01-01 23:59:59)
            'notifyUrl'    => $this->payCallbackDomain . '/thirdAdvance/callback/youpay',  //string 异步回调地址 (当代付完成时，平台将向此URL地址发送异步通知。建议使用 https)，
            'reverseUrl'   => null,
            'extra'        => null,
        );

        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->initParam($params);

        $this->basePostNew('/payout/create');
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['text']) ? $result['text'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                $this->return['code']           = 10500;
                $this->return['balance']        = $result['amount'] * 100;
                $this->return['msg']            = $message;
                $this->transferNo               = $result['tradeNo'];
                return;
            }else{
                //代付失败，改成失败状态
                $message = 'http_code:'.$this->httpCode.'errorMsg:'.json_encode($result, JSON_UNESCAPED_UNICODE);
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
                $this->return['msg'] = 'YOUPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']           = 886;
        $this->return['balance']        = $this->money;
        $this->return['msg']            = $message;
        $this->transferNo               = '';
    }


    //查询余额
    public function getThirdBalance()
    {
        $this->initParam();
        $this->basePostNew ('/payout/balance');

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['text']) ? $result['text'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                $this->return['code']    = 10509;
                $this->return['balance'] = $result['balance']*100;
                $this->return['msg']     = $message;
                return;
            }

        }
        $this->_end();
        throw new \Exception('http_code:'.$this->httpCode.' code:'.$code.' message:'.$message);
    }

    //查询代付结果
    public function getTransferResult()
    {
        $params = [
            'tradeNo' => $this->transferNo,
            'orderNo' => $this->orderID,
        ];

        $this->initParam($params);
        $this->basePostNew('/payout/status');

        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['text']) ? $result['text'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                //订单状态
                if($result['status'] === 'PAID'){
                    $status = 'paid';
                    $this->return = ['code' => 1,  'msg' => $message];

                }elseif($result['status'] === 'CANCELLED'){
                    $status = 'failed';
                    $this->return = ['code' => 0,  'msg' => $message];
                }else{
                    $this->return = ['code' => 0,  'msg' => $result['status']];
                    return;
                }

                $real_money = $result['paid'] * 100;
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $result['tradeNo'],
                    '',
                    $status,
                    $fee,
                    $message
                );
                return;
            }

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:'.$this->httpCode.' code:'.$code.' message:'.$message];

    }

    //组装数组
    public function initParam($params=[])
    {
        //请求参数 Request parameter
        $data = array(
            'merchantNo'        => $this->partnerID,//	是	string	商户号 business number
            'time'              => time(),//	是	integer	发送请求的 10 位时间戳 10-bit timestamp of sending request
            'appSecret'         => $this->pubKey,//	是	string	默认为 MD5 Default is MD5
        );

        $params && $data = array_merge($data, $params);
        $data['sign']           = $this->sign($data);  //校验码
        $this->parameter = $data;
    }

    /**
     * 获取代付平台 的银行code
     */

    //生成签名
    public function sign($data)
    {
        unset($data['bankBranch']);
        unset($data['memo']);
        unset($data['appSecret']);
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            $str .= $k.'='.$v.'&';
        }
        $str = trim($str, '&');

        $sign_str       = $str . $this->key;
        $digest         = hash("sha256", $sign_str);
        $sign           = strtoupper(md5($digest));
        return $sign;
    }




    public function basePostNew($str, $referer = null)
    {
        $this->payRequestUrl = $this->payUrl . $str;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl . $str);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);

    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter  = $params;
        $field = [
            'status'      => $params['status'],
            'tradeNo'     => $params['tradeNo'],
            'orderNo'     => $params['orderNo'],
            'amount'      => $params['amount'],
            'name'        => $params['name'],
            'bankName'    => $params['bankName'],
            'bankAccount' => $params['bankAccount'],
            'bankBranch'  => $params['bankBranch'],
            'memo'        => $params['memo'],
            'mobile'      => $params['mobile'],
            'fee'         => $params['fee'],
            'extra'       => $params['extra'],
            'sign'        => $params['sign'],
        ];

        if($this->sign($field) != $params['sign']){
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid'){
            return;
        }

        $amount     = $field['amount'] * 100;//以分为单位
        $fee        = $field['fee'] * 100;//以分为单位
        $real_money = $amount - $fee;//实际到账金额

        //金额不一致
        if($this->money != $amount) {
            throw new \Exception('Inconsistent amount');
        }

        //订单状态
        if($field['status'] === 'PAID'){
            $status = 'paid';
            $this->return = ['code' => 1,  'msg' => ''];
        }else{
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        }
        $this->re = $this->return;
        $this->updateTransferOrder(
            $this->money,
            $real_money,
            $field['tradeNo'],
            '',
            $status,
            $fee,
            $params['extra']
        );
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        global $app;
        $ci = $app->getContainer();
        $country_code = '';
        if(isset($ci->get('settings')['website']['site_type'])){
            $country_code = $ci->get('settings')['website']['site_type'];
        }

        if($country_code == 'mxn'){
            $banks = [
                "ABC CAPITAL"     => "19000f001",
                "ACTINVER"        => "19000f003",
                "AFIRME"          => "19000f004",
                "AKALA"           => "19000f005",
                "ASP INTEGRA OPC" => "19000f007",
                "AUTOFIN"         => "19000f008",
                "AZTECA"          => "19000f009",
                "BAJIO"           => "19000f010",
                "BANAMEX"         => "19000f011",
                "BANCO FINTERRA"  => "19000f012",
                "BANCO S3"        => "19000f013",
                "BANCOMEXT"       => "19000f014",
                "BANCOPPEL"       => "19000f015",
                "BANCREA"         => "19000f016",
                "BANJERCITO"      => "19000f017",
                "BANK OF AMERICA" => "19000f018",
                "BANKAOOL"        => "19000f019",
                "BANOBRAS"        => "19000f020",
                "BANORTE"         => "19000f021",
                "BANREGIO"        => "19000f022",
                "BANSI"           => "19000f024",
                "BANXICO"         => "19000f025",
                "BARCLAYS"        => "19000f026",
                "BBASE"           => "19000f027",
                "BBVA BANCOMER"   => "19000f028",
                "BMONEX"          => "19000f029",
                "CAJA POP MEXICA" => "19000f030",
                "CAJA TELEFONIST" => "19000f031",
                "CB INTERCAM"     => "19000f032",
                "CI BOLSA"        => "19000f033",
                "CIBANCO"         => "19000f034",
                "CLS"             => "19000f035",
                "CoDi Valida"     => "19000f036",
                "COMPARTAMOS"     => "19000f037",
                "CONSUBANCO"      => "19000f038",
                "CREDICAPITAL"    => "19000f039",
                "CREDIT SUISSE"   => "19000f040",
                "CRISTOBAL COLON" => "19000f041",
                "DONDE"           => "19000f043",
                "FINAMEX"         => "19000f046",
                "FINCOMUN"        => "19000f047",
                "FOMPED"          => "19000f048",
                "FONDO (FIRA)"    => "19000f049",
                "GBM"             => "19000f050",
                "HIPOTECARIA FED" => "19000f052",
                "HSBC"            => "19000f053",
                "ICBC"            => "19000f054",
                "INBURSA"         => "19000f055",
                "INDEVAL"         => "19000f056",
                "INMOBILIARIO"    => "19000f057",
                "INTERCAM BANCO"  => "19000f058",
                "INVERCAP"        => "19000f059",
                "INVEX"           => "19000f060",
                "JP MORGAN"       => "19000f061",
                "KUSPIT"          => "19000f062",
                "LIBERTAD"        => "19000f063",
                "MASARI"          => "19000f064",
                "MIFEL"           => "19000f065",
                "MIZUHO BANK"     => "19000f066",
                "MONEXCB"         => "19000f067",
                "MUFG"            => "19000f068",
                "MULTIVA BANCO"   => "19000f069",
                "MULTIVA CBOLSA"  => "19000f070",
                "NAFIN"           => "19000f071",
                "PAGATODO"        => "19000f072",
                "PROFUTURO"       => "19000f073",
                "SABADELL"        => "19000f075",
                "SANTANDER"       => "19000f076",
                "SCOTIABANK"      => "19000f077",
                "SHINHAN"         => "19000f078",
                "STP"             => "19000f079",
                "UNAGRA"          => "19000f081",
                "VALMEX"          => "19000f082",
                "VALUE"           => "19000f083",
                "VE POR MAS"      => "19000f084",
                "VECTOR"          => "19000f085",
                "VOLKSWAGEN"      => "19000f086",
                "ARCUS"           => "19000f087",
            ];
        }else{
            $banks = [
                "Gcash" => 'GCASH',
                "KBANK" => "Kasikornbank",
                "BBL" => "BANGKOK BANK",
                "BAAC" => "Bank for Agriculture and Agricultural Cooperatives",
                "BAY" => "Bank of Ayudhya",
                "BOC" => "Bank of China (Thai)",
                "CIMB" => "CIMB Thai Bank",
                "CITI" => "Citibank Thailand",
                "GHB" => "Government Housing Bank",
                "ICBC" => "ICBC Bank",
                "TIBT" => "Islamic Bank of Thailand",
                "KKB" => "KIATNAKIN BANK",
                "KTB" => "Krung Thai Bank",
                "LHBA" => "LH Bank",
                "SCBT" => "Standard Chartered",
                "SMTB" => "Sumitomo Mitsui Trust Bank",
                "TTB" => "TMBThanachart Bank",
                "GSB" => "Government Savings Bank",
                "SCB" => "The Siam Commercial Bank",
                "TCRB" => "Thai Credit Retail Bank",
                "TISCO" => "TISCO Bank",
                "UOB" => "United Overseas Bank"
            ];
        }

        return $banks[$this->bankCode];
    }
}
