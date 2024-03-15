<?php

namespace Logic\Transfer\ThirdParty;

class COPOPAY extends BASES
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
            'accessType'   => 1,
            'merchantId'   => $this->partnerID,
            'orderAmount'  => bcdiv($this->money, 100, 2),
            'orderNo'      => $this->orderID,
            'bankId'       => $this->getBankName(), // 开户行行号
            'bankName'     => $this->bankName, // 开户行行名
            'bankProvince' => 'trans', // 开户省名
            'bankCity'     => 'trans', // 开户省名
            'bankNo'       => $this->bankCard,
            'defrayName'   => $this->bankUserName, // 开户人姓名
            'currency'     => 'PHP', // 币别代码
            'language'     => 'zh-CN',
            'remark'       => 'trans',
            'notifyUrl'    => $this->payCallbackDomain . '/thirdAdvance/callback/copopay',
        ];
        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/dior/merchant-api/proxy-order';
        $this->parameter = $data;
        $this->basePostNew();
//        print_r($data);

        $result = json_decode($this->re, true);
//        print_r($result);

        $message = isset($result['respMsg']) ? $result['respMsg'] : 'errorMsg:' . (string)$this->re;
        if ($this->httpCode == 200) {
            if (!empty($result['respCode']) && $result['respCode'] == '000') {
                $this->return['code'] = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg'] = $message;
                $this->transferNo = $result['payOrderNo'];//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code'] = 886;
                $this->return['balance'] = 0;
                $this->return['msg'] = 'COPOPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code'] = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg'] = $message;
        $this->transferNo = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance()
    {
        $params = [
            'accessType' => 1,
            'currency'   => 'PHP',
            'merchantId' => $this->partnerID,
        ];
        $params['sign'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/dior/merchant-api/pay-query-balance";
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['respCode']) ? $result['respCode'] : 1;
        $message = isset($result['respMsg']) ? $result['respMsg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200 && !empty($result['respCode']) && $result['respCode'] == '000') {

            $this->return['code'] = 10509;
            $this->return['balance'] = bcmul($result['availableAmount'], 100);
            $this->return['msg'] = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult()
    {
        $data = [
            'accessType' => 1,
            'currency'   => 'PHP',
            'merchantId' => $this->partnerID,
            'orderNo'    => $this->orderID,
            'language'   => 'zh-CN',
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl .= '/dior/merchant-api/proxy-query';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['respMsg']) ? $result['respMsg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200 && !empty($result['respCode']) && $result['respCode'] == '000') {
            //订单状态 status (1 创建订单成功 2 代收/代付成功  3 失败)
            if ($result['orderStatus'] == '1') {
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif ($result['status'] == '2') {
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else {
                $this->return = ['code' => 0, 'msg' => $message];
                return;
            }

            $real_money = bcmul($result['orderAmount'], 100);
            $fee = bcmul($result['fee'], 100);
            $this->updateTransferOrder($this->money, $real_money, $result['payOrderNo'],//第三方转账编号
                '', $status, $fee, $message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew()
    {
        $ch = curl_init();
        $postData = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8'
        ]);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;

        curl_close($ch);
    }

    //生成签名
    public function sign($param)
    {
        unset($param['sign']);
        ksort($param);

        $originalString = '';

        foreach ($param as $key => $val) {
            if (!empty($val)) {
                $originalString = $originalString . $key . "=" . $val . "&";
            }
        }
        $originalString .= "Key=" . $this->key;

        return strtolower(md5($originalString));
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
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
        $amount = bcmul($params['orderAmount'], 100);//以分为单位
        $fee = bcmul($params['fee'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(：2 - 成功 : 3 – 失敗。)
        if ($this->parameter['orderStatus'] == '1') {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif ($this->parameter['status'] == '2') {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['payOrderNo'],//第三方转账编号
            '', $status, $fee);
    }

    /**
     * 银行名称
     * @return string
     */
    private function getBankName()
    {

        $banks = [
            'Gcash'   => '001',
            'PayMaya' => '002',
        ];
        return $banks[$this->bankCode];
    }
}