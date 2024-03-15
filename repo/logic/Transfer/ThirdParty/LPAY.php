<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * LPAY代付
 */
class LPAY extends BASES
{
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg()
    {
        return 'OK';
    }

    //请求代付接口
    public function runTransfer()
    {
        $params = array(
            'merchant_ref' => $this->orderID,//是	string	商户订单号 Merchant order number
            'product' => 'PayloroPayout',//	是 产品名称
            'amount' => bcdiv($this->money, 100, 2),//	是	string	金额，单位，保留 2 位小数 Amount, unit, 2 decimal places
            //'extra'           => $extra,//	否	Object	额外参数， 默认为json字符串 {} Extra parameters, the default is json string {}
            //'extend_params'        => '',//	否 扩展字段
        );

        //extra 参数, 可选字段 extra parameter, optional field
        $extra = array(
            'account_name' => $this->bankUserName, //持卡人
            'account_no' => $this->bankCard,//银行卡号
            'bank_code' => $this->getBankName(),//提现银行代码(印度为IFSC码、UPI定值，巴西为(CPF、PHONE、EMAIL、RANDOM_CHARACTER))
        );

        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        if ($extra) {
            $params['extra'] = $extra;
        }
        $this->initParam($params);

        $this->basePostNew('/api/gateway/withdraw');
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 404;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($code == 200) {
            $this->return['code'] = 10500;
            $this->return['balance'] = $this->money;
            $this->return['msg'] = '';
        } else {
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
            $this->return['msg'] = 'LPAY:' . $message ?? '查询失败';
        }
    }


    //查询余额
    public function getThirdBalance()
    {
        $params = array(
            'currency' => 'PHP'
        );
        $this->initParam($params);
        $this->basePostNew('/api/gateway/query/balance');

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 404;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($code == 200) {
            $params = json_decode($result['params'], true);
            $this->return['code'] = 10509;
            $this->return['balance'] = $params['available_balance'] * 100;
            $this->return['msg'] = '';
        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'LPAY:' . $message ?? '查询失败';
            $this->return['balance'] = 'balance';
        }
    }

    //查询代付结果
    public function getTransferResult()
    {
        $params = [
            'merchant_refs' => [$this->orderID]
        ];
        $this->initParam($params);
        $this->basePostNew('/api/gateway/batch-query/order');

        $re = json_decode($this->re, true);
        $code = isset($re['code']) ? $re['code'] : 404;
        $pay_no = '';
        $real_money = $this->money;
        $fee = null;
        $success_time = '';
        if ($code == 200) {
            $params = json_decode($re['params'], true);
            $params = $params[0];
            $pay_no = $params['system_ref'];
            $real_money =  bcmul($params['pay_amount'], 100); //以分为单位
            $fee =  bcmul($params['fee'], 100); //以分为单位
            $success_time = date('Y-m-d H:i:s', $params['success_time']);
            //存款订单状态: 1：Success； 2: Pending；5: Reject
            switch ($params['status']) {
                case '1'://交易成功
                    $status = 'paid';
                    break;
                case '2'://交易成功
                    $status = 'pending';
                    break;
                case '5'://交易失败
                    $status = 'failed';
                    break;
                default:
                    $status = 'pending';
                    break;
            }
        } else {
            $status = 'pending';//支付状态设置的宽泛一些！
        }

        $message = isset($re['message']) ? $re['message'] : 'errorMsg:' . (string)$this->re;
        if ($status == 'paid') {//支付成功
            $message = '代付成功';
            $this->return = ['code' => 10508, 'balance' => $real_money, 'msg' => ''];
        } else {
            $real_money = 0;
            $message = $status == 'pending' ? '代付中-' . $message : '代付失败-' . $message;
            $this->return = ['code' => 886, 'balance' => 0, 'msg' => 'LPAY:' . $message];
        }
        if (in_array($status, ['paid', 'failed'])) {
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

    //组装数组
    public function initParam($params)
    {
        //转换json串 Convert json string
        $params_json = json_encode($params, 320);
        //请求参数 Request parameter
        $data = array(
            'merchant_no' => $this->partnerID,//	是	string	商户号 business number
            'timestamp' => time(),//	是	integer	发送请求的 10 位时间戳 10-bit timestamp of sending request
            'sign_type' => 'MD5',//	是	string	默认为 MD5 Default is MD5
            'params' => $params_json,//	是	string	请求业务参数组成的 JSON String；若接口对应的业务参数不需要字段传输，该字段的值可为空字符串
        );

        $data['sign'] = $this->sign($data);  //校验码
        $this->parameter = $data;
    }


    //生成签名
    public function sign($data)
    {
        $merchant_no = isset($data['merchant_no']) ? $data['merchant_no'] : '';
        $params = isset($data['params']) ? $data['params'] : '';
        $sign_type = isset($data['sign_type']) ? $data['sign_type'] : '';
        $timestamp = isset($data['timestamp']) ? $data['timestamp'] : '';

        $sign_str = $merchant_no . $params . $sign_type . $timestamp . $this->key;
        $sign = md5($sign_str);//MD5签名 不区分大小写  MD5 signature is not case sensitive
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
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);

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

        $data = json_decode($params['params'], true);

        //订单状态付款状态：(1 处理中 2 已打款 3已驳回)
        switch ($data['status']) {
            case '1'://交易成功
                $status = 'paid';
                break;
            case '2'://交易成功
                $status = 'pending';
                break;
            case '5'://交易失败
                $status = 'failed';
                break;
            default:
                $status = 'pending';
                break;
        }


        $this->re = $this->return;
        $realMoney = bcmul($data['pay_amount'], 100);//以分为单位
        $fee = bcmul($data['fee'], 100);//以分为单位

        $this->updateTransferOrder($this->money, $realMoney, $data['system_ref'],//第三方转账编号
            '', $status, $fee);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        $banks = [
            'AUB'  =>  'PH_AUB',
            'UnionBank EON' =>  'PH_UBPEON',
            'Starpay'   =>  'PH_SPY',
            'EB'    =>  'PH_EWB',
            'ESB'   =>  'PH_EQB',
            'MB'    =>  'PH_MSB',
            'ERB'   =>  'PH_EWR',
            'PB'    =>  'PH_PRB',
            'PBC'   =>  'PH_PBC',
            'PBB'   =>  'PH_PBB',
            'PNB'   =>  'PH_PNB',
            'PSB'   =>  'PH_PSB',
            'PTC'   =>  'PH_PTC',
            'PVB'   =>  'PH_PVB',
            'RBG'   =>  'PH_RBG',
            'Rizal Commercial Banking Corporation'  =>  'PH_RCBC',
            'RB'    =>  'PH_ROB',
            'SBC'   =>  'PH_SEC',
            'SBA'   =>  'PH_SBA',
            'SSB'   =>  'PH_SSB',
            'UCPB SAVINGS BANK' =>  'USB',
            'Queen City Development Bank, Inc.' =>  'PH_QCB',
            'United Coconut Planters Bank'  =>  'PH_UCPB',
            'Wealth Development Bank, Inc.' =>  'PH_WDB',
            'Yuanta Savings Bank, Inc.' =>  'PH_YUANSB',
            'GrabPay'   =>  'PH_GRABPAY',
            'Banco De Oro Unibank, Inc.'    =>  'PH_BDO',
            'Bangko Mabuhay (A Rural Bank), Inc.'   =>  'PH_BMB',
            'BOC'   =>  'PH_BOC',
            'CTBC'  =>  'PH_CTBC',
            'Chinabank' =>  'PH_CBC',
            'CBC'   =>  'CBC',
            'ALLBANK (A Thrift Bank), Inc.' =>  'PH_ABP',
            'BDO Network Bank, Inc.'    =>  'PH_ONB',
            'Binangonan Rural Bank Inc' =>  'PH_BRB',
            'Camalig'   =>  'PH_CMG',
            'DBI'   =>  'PH_DBI',
            'Gcash' =>  'PH_GCASH',
            'Cebuana Lhuillier Rural Bank, Inc.'    =>  'PH_CEBRUR',
            'ISLA Bank (A Thrift Bank), Inc.'   =>  'PH_ISLA',
            'Landbank of the Philippines'   =>  'LBOB',
            'Maybank Philippines, Inc.' =>  'PH_MPI',
            'Metropolitan Bank and Trust Co'    =>  'PH_MET',
            'Omnipay'   =>  'PH_OMNI',
            'Partner Rural Bank (Cotabato), Inc.'   =>  'PH_PAR',
            'Paymaya Philippines, Inc.'    =>  'PH_PAYMAYA',
            'Allied Banking Corp'   =>  'Allied Banking Corp',
            'ING'   =>  'PH_ING',
            'CSB'   =>  'PH_CSB',
            'BPI'   =>  'PH_LBP',
            'SCB'   =>  'SCB',
            'UBPHPH'    =>  'PH_UBPEON'
        ];

        if (isset($banks[$this->bankCode])) {
            return trim($banks[$this->bankCode]);
        } else {
            return 'Gcash';
        }

    }

}
