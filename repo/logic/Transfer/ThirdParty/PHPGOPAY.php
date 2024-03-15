<?php

namespace Logic\Transfer\ThirdParty;

/**
 * PHPGO代付
 */
class PHPGOPAY extends BASES
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
            'merchant'          => $this->partnerID,    
            'total_amount'      => (int)bcdiv($this->money, 100, 2),
            'callback_url'      => $this->payCallbackDomain . '/thirdAdvance/callback/phpgopay',
            'order_id'          => $this->orderID,
            'bank'              => $this->getBankName(),
            'bank_card_name'    => $this->bankUserName,
            'bank_card_account' => $this->bankCard,
            'bank_card_remark'  => 'no',

        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;

        $this->basePostNew('/api/daifu');

        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 404;
        if ($code == 1) {
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
            $this->return['msg'] = 'PHPGOPAY:' . $message ?? '查询失败';
        }
    }


    // 查询余额
    public function getThirdBalance()
    {
        $data = array(
            'merchant' => $this->partnerID
        );
        $data['sign']    = $this->sign($data);
        $this->parameter = $data;

        $this->basePostNew ('/api/me');

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 404;
        if (isset($result['status']) && $result['status'] == 1) {
            $message = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;
            $this->return['code'] = 886;
            $this->return['msg'] = 'PHPGOPAY:' . $message ?? '查询失败';
            $this->return['balance'] = 'balance';
        } else {
            $this->return['code'] = 10509;
            $this->return['balance'] = bcmul($result['balance'], 100, 2);
            $this->return['msg'] = '';
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
            $this->return = ['code' => 886, 'balance' => 0, 'msg' => 'TUPAY:' . $message];
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
    public function sign($param) {
       if(isset($param['sign'])) {
            unset($param['sign']);
        }
        ksort($param);
        $originalString='';

        foreach($param as $key=>$val){
            $originalString = $originalString . $key . "=" . $val . "&";
        }

        $originalString.= "key=" . $this->key;;
        return md5($originalString);
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
            'merchant'      => $params['merchant'] ?? '',
            'order_id'      => $params['order_id'] ?? '',
            'amount'        => $params['amount'] ?? 0,
            'status'        => $params['status'] ?? '',
            'message'       => $params['message'] ?? '',
        ];

        $change = 0;
        $pay_no = $field['order_id'];
        $real_money = $field['amount'] * 100;//以分为单位

        $sign = $this->sign($field);
        if($sign != $params['sign']){
            throw new \Exception('Sign error');
        }

        if($params['status'] != 5) {
            $change = 1;
            $status = 'failed';
            $message = '代付失败';
            $this->return = ['code' => 0, 'msg' => 'PHPGOPAY:代付失败'];
        } else {
            $change = 1;
            $status = 'paid';
            $message = '代付成功';
            $this->return = ['code' => 0,  'msg' => ''];
        }

        $this->re = $this->return;
        if($change){
            $this->updateTransferOrder(
                $this->money,
                $real_money,
                $pay_no,
                date('Y-m-d H:i:s'),
                $status,
                0,
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
            'Gcash'                                 => 'gcash',
            'BPI'                                   => 'bpi',
            'Metropolitan Bank and Trust Co'        => 'Metropolitan Bank and Trust Co',
            'Security Bank Corporation'             => 'SBC',
            'Philippine National Bank'              => 'PNB',
            'United Coconut Planters Bank'          => 'UCPB',
            'Philippine Savings Bank'               => 'PSB',
            'PBC'                                   => 'PBC',
            'BOC'                                   => 'BC',
            'BDO Network Bank'                      => 'BNB',
            'Camalig'                               => 'CB',
            'CTBC'                                  => 'CTBC',
            'DBI'                                   => 'DB',
            'ESB'                                   => 'ESB',
            'GrabPay'                               => 'GP',
            'ING'                                   => 'IB',  
            'ISLA Bank (A Thrift Bank)'             => 'ISLA',
            'Omnipay'                               => 'OP',
            'Partner Rural Bank (Cotabato), Inc.'   => 'PRB',
            'PayMaya Philippines'                   => 'PMP',
            'PBB'                                   => 'PBB',
            'PTC'                                   => 'PTC',
            'PB'                                    => 'PDB',
            'Starpay'                               => 'STP',
            'SBA'                                   => 'SLB',
            'SSB'                                   => 'SSB',
            'UCPB Savings Bank'                     => 'USB',
            'Wealth Development Bank'               => 'WDB',
            'CBS'                                   => 'CBS',
            'CTBC'                                  => 'CTBC',
        ];

        return $banks[$this->bankCode];
    }
}
