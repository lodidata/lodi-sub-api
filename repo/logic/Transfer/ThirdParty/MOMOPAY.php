<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * MOMOPAY代付
 */
class MOMOPAY extends BASES
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
        $params = array(
            'platformId' => $this->partnerID,//  是   string  商户号 business number
            'proposalId' => $this->orderID,
            'amount' => bcdiv($this->money, 100),
            'accountNo' => $this->bankCard,//  是   string  用户id business number
            'accountType' => 'gcash',
            'realName' => $this->bankUserName,
            'bankCode' => '',
            'callbackUrl' => $this->payCallbackDomain . '/thirdAdvance/callback/momopay',
            'createTime' => time(),
        );

        //请求参数 Request parameter
        $params['sign'] = $this->sign($params);  //校验码
        $this->parameter = $params;

        $this->basePostNew('/api/withdrawal');
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($code == 200) {
            $this->return['code'] = 10500;
            $this->return['balance'] = 0;
            $this->return['msg'] = $message;
            $this->transferNo = '';
        } else {
            //代付失败，改成失败状态
            $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);

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
            $this->return['msg'] = 'MOMOPAY:' . $message ?? '代付失败';
        }

    }

    /**
     * 生成签名
     * @param $data
     * @return false|string
     */
    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        unset($data['sign']);

        ksort($data);
        $data = array_filter($data, function ($val) {
            return ($val !== "") && ($val !== 0) && ($val !== 'undefined');
        });

        $str = urldecode(http_build_query($data));
        return hash_hmac("sha256", $str, $this->key);
    }


    //查询余额
    public function getThirdBalance()
    {
        $params = [
            'timestamp' => time(),
        ];
        $params['sign'] = $this->sign($params);  //校验码
        $this->parameter = $params;
        $this->basePostNew('/api/balance');

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($code == 200) {

            $this->return['code'] = 10509;
            $this->return['balance'] = $result['balance'] * 100;
            $this->return['msg'] = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult()
    {
        $params = [
            'proposalId' => $this->orderID,
        ];

        $params['sign'] = $this->sign($params);  //校验码
        $this->parameter = $params;
        $this->basePostNew('/api/withdrawal/info');

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['text']) ? $result['text'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code == '200') {
                $data = $result['content'];
                //订单状态
                if ($data['orderStatus'] == '1') {
                    $status = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];

                } elseif ($data['orderStatus'] == '2') {
                    $status = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $data['orderStatus']];
                    return;
                }

                $real_money = $data['amount'] * 100;
                $fee = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $data['proposalId'],
                    '',
                    $status,
                    $fee,
                    $message
                );
                return;
            }

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];

    }

    public function basePostNew($str, $referer = null)
    {
        $this->payUrl .= $str;
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
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'platform:' . $this->partnerID,
        ]);
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
        $this->parameter = $params['content'];

        //检验状态
        if ($this->sign($params['content']) != $params['sign']) {
            throw new \Exception('{"code": 400, "message":"sign is wrong"}');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            throw new \Exception('{"code": 200, "message":"success"}');
        }

        //订单状态付款状态：(//订单状态：Failed 失败Payed 成功 OverTime超时 Canceled 订单取消)v
        if ($this->parameter['orderStatus'] == '1') {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif ($this->parameter['orderStatus'] == '2') {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $this->money, $this->parameter['billNo'],//第三方转账编号
            '', $status);
    }

}
