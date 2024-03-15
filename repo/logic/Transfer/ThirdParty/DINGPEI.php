<?php
namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

/**
 *
 * DINGPEI
 * @author
 */
class DINGPEI extends BASES {
    private $httpCode = '';

        //回调，不处理逻辑
    public function callbackMsg(){
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data = array(
            'appId'     => $this->partnerID, //
            'appOrderNo'      => $this->orderID, //是  string  商户订单号 Merchant order number
            'orderAmt'       => bcdiv($this->money, 100,2),//代付金额 (单位：฿，不支持小数)
            'payId'           => $this->bankCode != 'Gcash' ? '101'  : '201', 
            'accNo'        => $this->bankCard, // string 收款银行账号 (示例：6227888888888888)
            'accName'      => $this->bankUserName, //收款人姓名 (示例：张三) 
        );

        if($data['payId'] == '101')
        {
            $data['bankName'] = $this->bankCode;
            //判断 额外参数是否为空 Determine whether the extra parameter is empty
            $data['sign'] = $this->sign($data);
        }else{
            $data['sign'] = $this->sign($data);
            $data['bankName'] = '';
        } 

        $data['notifyURL'] =  $this->payCallbackDomain . '/thirdAdvance/callback/dingpei'; //string 异步回调地址 (当代付完成时，平台将向此URL地址发送异步通知。建议使用 https)，
        $this->payUrl .= '/newbankPay/crtAgencyOrder.do';
        $this->parameter = $data;

        $this->basePostNew();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;
        if ($this->httpCode == 200) 
        {
            if($code === '0000')
            {
                $this->return['code']           = 10500;
                $this->return['balance']        = bcmul($result['data']['orderAmt'], 100);
                $this->return['msg']            = $message;
                $this->transferNo               = $result['data']['orderNo'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
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
                $this->return['msg'] = 'DINGPEI:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']           = 886;
        $this->return['balance']        = $this->money;
        $this->return['msg']            = $message;
        $this->transferNo               = '';//第三方订单号
    }



    //查询余额
    public function getThirdBalance()
    {


        $this->return['code']    = 10509;
        $this->return['balance'] = 1000000000;
        $this->return['msg']     = '';
        return;
    }




    //查询代付结果
    public function getTransferResult()
    {
        $data = [
            'appId' => $this->partnerID,
            'appOrderNo' => $this->orderID,
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl .= '/newbankPay/selAgencyOrder.do';
        $this->parameter = $data;

        $this->basePostNew();

        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;


        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;


        if ($this->httpCode == 200) {
            if($code ===  '0000'){
                //订单状态 pay_status (1、 付款中 2、 付款失败 3、 付款成功)
                if($result['data']['orderStatus'] === '02'){
                    $status = 'paid';
                    $this->return = ['code' => 1,  'msg' => $message];
                }elseif($result['data']['orderStatus'] === '99'){
                    $status = 'failed';
                    $this->return = ['code' => 0,  'msg' => $message];
                }elseif($result['data']['orderStatus'] === '03'){
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }else{
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }
                
                $real_money = bcmul($result['data']['orderAmt'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $result['data']['appOrderNo'],//第三方转账编号
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



    public function basePostNew() 
    {
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
  
        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }





    //生成签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }
        $str = trim($str, '&');

        $sign_str       = $str .'&key='. $this->key;
        $sign           = strtoupper(md5($sign_str));
        return $sign;
    }




    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $config    = Recharge::getThirdConfig('dingpei');
        $this->key = $config['key'];

        $field = [
            'appOrderNo'    => $params['appOrderNo'],
            'orderNo'       => $params['orderNo'],
            'orderTime'     => $params['orderTime'],
            'appId'         => $params['appId'],
            'orderAmt'      => $params['orderAmt'],
            'orderFee'      => $params['orderFee'],
            'orderStatus'   => $params['orderStatus'],
        ];

        if($this->sign($field) != $params['sign']){
            throw new \Exception('Sign error');
        }

        if($params['orderStatus'] != '02'){
            throw new \Exception("代付失败");
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid'){
            return;
        }

        $amount     = bcmul($field['orderAmt'] , 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(1、 付款中 2、 付款失败 3、 付款成功)
        if($field['orderStatus'] === '02'){
            $status = 'paid';
            $this->return = ['code' => 1,  'msg' => ''];
        }else{
            $this->return = ['code' => 0, 'msg' => $field['msg']];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder(
            $this->money,
            $real_money,
            $field['appOrderNo'],//第三方转账编号
            '',
            $status,
            0,
            $field['msg']
        );
    }



    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        $banks = [
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
        return $banks[$this->bankCode];
    }

}
