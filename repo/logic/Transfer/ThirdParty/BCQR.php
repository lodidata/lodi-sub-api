<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * 京都代付
 */
class BCQR extends BASES
{
    private $httpCode = '';

    //回调
    public function callbackMsg(){
        return 'OK';
    }

    //请求代付接口
    public function runTransfer()
    {
        //请求参数 Request parameter
        $data = array(
            'amount'            => bcdiv($this->money, 100, 2),
            'merchant'          => $this->partnerID,//	是	string	商户号 business number
            'bankname'          => $this->getBankName(),
            'subbankname'       => '',
            'cardno'            => $this->bankCard,
            'cardname'          => $this->bankUserName,
            'notifyurl'         => $this->payCallbackDomain . '/thirdAdvance/callback/bcqr',
            'outtransferno'     => $this->orderID,
            'VerifyChannelNo'   => '',
            'remark'            => '',
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;

        $this->basePostNew('/transfer/apply');
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 404;
        if ($code == 0) {
            $this->return['code'] = 10500;
            $this->return['balance'] = $this->money;
            $this->return['msg'] = '';
        } else {
            $message = isset($result['results']) ? $result['results'] : 'errorMsg:'.(string)$this->re;
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
            $this->return['msg'] = 'BCQR:' . $message ?? '查询失败';
        }
    }


    //查询余额
    public function getThirdBalance()
    {
        $data = array(
            'merchant' => $this->partnerID
        );
        $data['sign']    = $this->sign($data);
        $this->parameter = $data;

        $this->basePostNew ('/merchant/balance');

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 404;
        if ($code == 0) {
            $params = $result['results'];
            $this->return['code'] = 10509;
            $this->return['balance'] = $params['availableamount']*100;
            $this->return['msg'] = '';
        } else {
            $message = isset($result['results']) ? $result['results'] : 'errorMsg:'.(string)$this->re;
            $this->return['code'] = 886;
            $this->return['msg'] = 'BCQR:' . $message ?? '查询失败';
            $this->return['balance'] = 'balance';
        }
    }

    //查询代付结果
    public function getTransferResult()
    {
        $data = [
            'merchant' => $this->partnerID,
            'outtransferno' => $this->orderID
        ];
        $data['sign']    = $this->sign($data);
        $this->parameter = $data;

        $this->basePostNew('/transfer/query');


        $re = json_decode($this->re, true);
        $code = isset($re['code']) ? $re['code'] : 404;
        $pay_no = '';
        $real_money = $this->money;
        $fee = null;
        $success_time = '';
        if ($code == 0) {
            $results = json_decode($re['results'], true);
            $pay_no = $results['transferno'];
            $real_money = $results['transferamount'] * 100;//以分为单位
            $fee = bcdiv($results['tradeamount'], $results['transferamount'], 2) * 100;
            $success_time = $results['endtime'];
            //存款订单状态: 订单状态 0未结算,1已结算,2结算中,3结算中(人工复查处理),4已撤销
            switch ($results['status']) {
                case '1'://交易成功
                    $status = 'paid';
                    break;
                case '4'://交易失败
                    $status = 'failed';
                    break;
                default:
                    $status = 'pending';
                    break;
            }
        } else {
            $status = 'pending';//支付状态设置的宽泛一些！
            $message = isset($re['results']) ? $re['results'] : 'errorMsg:' . (string)$this->re;
        }
        if ($status == 'paid') {//支付成功
            $message = '代付成功';
            $this->return = ['code' => 10508, 'balance' => $real_money, 'msg' => ''];
        } else {
            $real_money = 0;
            $message = $status == 'pending' ? '代付中-' . $message : '代付失败-' . $message;
            $this->return = ['code' => 886, 'balance' => 0, 'msg' => 'BCQR:' . $message];
        }
        if(in_array($status, ['paid', 'failed'])){
            $this->updateTransferOrder(
                $this->money,
                $real_money,
                $pay_no,
                $success_time,
                $status,
                $fee,
                $message
            );
        }
    }

    //生成签名
    public function sign($data) {
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            $str .= $k.'='.urlencode(trim($v)).'&';
        }
        $str = trim($str, '&');
        $sign_str       = $str . '&secret=' . $this->key;
        $sign           = md5(strtolower($sign_str));
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
        $this->httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);

        $this->_end();
    }

    /**
     * 代付异步回调
     * @param array $params
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter = $params;
        $field = [
            'transferno'    => $params['transferno']??'',
            'outtransferno' => $params['outtransferno']??'',
            'tradeamount'   => $params['tradeamount']??'',
            'transferamount' => $params['transferamount']??'',
            'endtime'       => $params['endtime']??'',
            'remark'        => $params['remark']??'',
            'status'        => $params['status']??'',
        ];
        $change = 0;
        $pay_no = $field['transferno'];
        $real_money = $field['transferamount'] * 100;//以分为单位
        $fee = bcdiv($field['tradeamount'], $field['transferamount'], 2) * 100;
        $success_time = $field['endtime'];

        $sign = $this->sign($field);
        if($sign != $params['sign']){
            $this->return = ['code' => 886, 'msg' => 'Sign error'];
        }
        //订单状态已经成功 或者 消息与订单状态一致
        elseif($this->order['status'] == 'paid' || ($this->order['status'] == $field['status'])){
            $this->return = ['code' => 0, 'msg' => 'success'];
        }
        //金额不一致
        elseif( $this->order['money'] != $real_money) {
            $this->return = ['code' => 886, 'msg' => 'money error'];
        }

        //订单状态
        elseif($field['status'] == 1){
            $change = 1;
            $status = 'paid';
            $message = '代付成功';
            $this->return = ['code' => 0,  'msg' => ''];
        }else{
            $change = 1;
            $status = 'failed';
            $message = '代付失败';
            $this->return = ['code' => 0, 'msg' => 'BCQR:代付失败'];
        }

        $this->re = $this->return;
        if($change){
            $this->updateTransferOrder(
                $this->money,
                $real_money,
                $pay_no,
                $success_time,
                $status,
                $fee,
                $message
            );
        }
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        $banks = [
            "KBANK" => "KASIKORNBANK PUBLIC COMPANY LIMITED",
            "BBL" => "BANGKOK BANK PUBLIC COMPANY LTD.",
            "BAAC" => "BANK FOR AGRICULTURE AND AGRICULTURAL CO-OPERATIVES",
            "BAY" => "BANK OF AYUDHYA",
            "BOC" => "Bank of China",
            "CIMB" => "CIMB (THAI) PUBLIC COMPANY LIMITED",
            "CITI" => "CITIBANK, N.A. (CITI), BANGKOK BRANCH",
            "DB" => "DEUTSCHE BANK AKTIENGESELLSCHAFT (DB)",
            "GHB" => "GOVERNMENT HOUSING BANK",
            "ICBC" => "INDUSTRIAL AND COMMERCIAL BANK OF CHAINA (THAI)",
            "TIBT" => "ISLAMIC BANK OF THAILAND (ISBT)",
            "KKB" => "KIATNAKIN PHATRA BANK PUBLIC COMPANY LIMITED",
            "KTB" => "KRUNG THAI BANK PUBLIC COMPANY LTD.",
            "LHBA" => "LAND AND HOUSES RETAIL BANK PUBLIC COMPANY LIMITED",
            "MHCB" => "MIZUHO BANK,LTD.",
            "SCBT" => "STANDARD CHARTERED BANK (THAI) PUBLIC COMPANY LTD.",
            "TTB" => "TMB BANK PUBLIC COMPANY LIMITED",
            "GSB" => "GOVERNMENT SAVING BANK",
            "HSBC" => "HONGKONG and SHANGHAI CORPORATION LTD.",
            "SCB" => "SIAM COMMERCIAL BANK PUBLIC COMPANY LTD.",
            "SMBC" => "SUMITOMO MITSUI BANKING CORPORATION (SMBC)",
            "TCRB" => "THAI CREDIT RETAIL BANK PUBLIC COMPANY LIMITED (TCRB)",
            "TISCO" => "TISCO BANK PUBLIC COMPANY LIMITED",
            "UOB" => "UNITED OVERSEAS BANK (THAI) PUBLIC COMPANY LIMITED"
        ];
        return $banks[$this->bankCode];
    }
}
