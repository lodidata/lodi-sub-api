<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class OMOPAY extends BASES
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
            'mchid'         => $this->partnerID,
            'money'         => bcdiv($this->money, 100, 2),
            'bankname'      => $this->bankName,
            'currency'      => 'PHP',
            'accountname'   => $this->bankUserName,
            'cardnumber'    => $this->bankCard,
            'out_trade_no'  => $this->orderID,
            'notifyurl'     => $this->payCallbackDomain . '/thirdAdvance/callback/omopay'
        ];

        $data['pay_md5sign'] = $this->sign($data);

        $this->payUrl .= '/payment';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $status = isset($result['status']) ? $result['status'] : 'error';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        $code = 886;
        $this->transferNo = '';
        if ($this->httpCode == 200) {
            if ($status == 'success') {
                $code = 10500;
                $this->transferNo = $result['transaction_id'];//第三方订单号
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

        $this->return['code'] = 10509;
        $this->return['balance'] = 100000 * 100;
        $this->return['msg'] = '';
        return;

        $data = [
            'currency' => 'PHP',
        ];
        $data['params'] = json_encode($data);
        $data['merchant_no'] = $this->partnerID;
        $data['timestamp'] = time();
        $data['sign_type'] = 'MD5';
        $data['sign'] = $this->sign($data);

        $this->parameter = $data;

        $this->payUrl .= "/api/gateway/query/balance";
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 201) {
            if ($code == 200) {
                $resultData = json_decode($result['params'], true);
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($resultData['available_balance'], 100);
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
            'out_trade_no' => $this->orderID,
            'mchid' => $this->partnerID,
        ];

        $data['pay_md5sign'] = $this->sign($data);

        $this->payUrl .= '/query/payment';
        $this->parameter = $data;
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 'error';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if ($this->httpCode == 200) {
            if ($code == 'success') {
                //订单状态 refCode (1： 成功)
                switch ($result['refCode']) {
                    case 1:
                        $status = 'paid';
                        $this->return = ['code' => 1, 'msg' => ''];
                        break;
                    case 2:
                    case 5:
                    case 7:
                        $status = 'failed';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    default:
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                }

                $real_money = bcmul($result['amount'], 100);
                $fee = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['transaction_id'],//第三方转账编号
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
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);
    }

    //生成签名
    public function sign($data)
    {
        unset($data['sign']);
        unset($data['s']);
        if (empty($data)) {
            return false;
        }

        ksort($data);
        $str = urldecode(http_build_query($data));
        $str .= '&key=' . $this->key;
        return strtoupper(md5($str));
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter = $params;

        //检验状态
        $signParams = [
            'memberid'       => $params['memberid'],
            'orderid'        => $params['orderid'],
            'amount'         => $params['amount'],
            'true_amount'    => $params['true_amount'],
            'transaction_id' => $params['transaction_id'],
            'returncode'     => $params['returncode']
        ];
        if ($this->sign($signParams) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }

        //订单状态付款状态：(1 处理中 2 已打款 3已驳回)
        switch ($params['returncode']) {
            case 1:
                    $status = 'pending';
                $this->return = ['code' => 0, 'msg' => '处理中'];
                break;
            case 2:
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => ''];
                break;
            case 3:
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => '已驳回'];
                break;
            default:
                $this->return = ['code' => 0, 'msg' => 'error'];
                return;
        }

        //$amount     = bcmul($params['amount'], 100);//以分为单位
        //$real_money = $amount;//实际到账金额

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $this->money, $params['orderid'],//第三方转账编号
            '', $status);
    }

}
