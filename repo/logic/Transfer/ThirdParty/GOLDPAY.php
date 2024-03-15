<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * GOLDPAY代付
 */
class GOLDPAY extends BASES
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
            'amount' => bcdiv($this->money, 100),
            'callback_url' => $this->payCallbackDomain . '/thirdAdvance/callback/goldpay',
            'merchant_code' => $this->partnerID,//  是   string  商户号 business number
            'merchant_order_no' => $this->orderID,
            'mobile_no' => $this->bankCard,
            'platform' => 'PC',
            'risk_level' => 1,
            'service_type' => 998
        );

        //请求参数 Request parameter
        $params['sign'] = $this->sign($params);  //校验码
        $this->parameter = $params;

        $this->basePostNew('/sha256/withdraw');
        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 0;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ((int)$code === 1) {
            $this->return['code'] = 10500;
            $this->return['balance'] = 0;
            $this->return['msg'] = $message;
            $this->transferNo = $result['trans_id'];
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
            $this->return['msg'] = 'GOLDPAY:' . $message ?? '代付失败';
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

        $str = urldecode(http_build_query($data)) . '&key=' . $this->key;
        return hash('sha256', $str);
    }


    //查询余额
    public function getThirdBalance()
    {
        $params = [
            'merchant_order_no' => '',
            'merchant_code' => $this->partnerID
        ];
        $params['sign'] = $this->sign($params);  //校验码
        $this->parameter = $params;
        $this->basePostNew('/sha256/balance');

        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 0;
        $message = isset($result['error_msg']) ? $result['error_msg'] : 'errorMsg:' . (string)$this->re;

        if ((int)$code === 1) {
            $this->return['code'] = 10509;
            $this->return['balance'] = $result['current_balance'] * 100;
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
            'merchant_order_no' => $this->orderID,
            'merchant_code' => $this->partnerID
        ];

        $params['sign'] = $this->sign($params);  //校验码
        $this->parameter = $params;
        $this->basePostNew('/sha256/query-order');

        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 0;
        $message = isset($result['error_msg']) ? $result['error_msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ((int)$code == 1) {
                if ($result['trans_status'] != 'Y') {
                    $result['amount'] = $this->money;
                }
                //订单状态
                switch ($result['trans_status']) {
                    case 'Y':
                        $status = 'paid';
                        $this->return = ['code' => 1, 'msg' => $message];
                        break;
                    case 'F':
                        $status = 'failed';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    case 'P':
                    case 'I':
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    default:
                        $this->return = ['code' => 0, 'msg' => $result['status']];
                        break;
                }

                $real_money = bcmul($result['amount'], 100);//以分为单位
                $fee = bcsub($real_money, $this->money, 2);
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $result['trans_id'],
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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

        //检验状态
        if ($this->sign($params) != $params['sign']) {
            throw new \Exception('{"status": 0, "error_msg":"sign is wrong"}');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }
        $params['status'] = (int)$params['status'];

        //订单状态付款状态：(//订单状态：Failed 失败Payed 成功 OverTime超时 Canceled 订单取消)v
        if ($params['status'] === 1 || $params['status'] === 3) {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif ($params['status'] === 0) {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $amount = bcmul($params['amount'], 100);//以分为单位
        $fee = bcsub($amount, $this->money, 2);


        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $amount, $this->parameter['trans_id'],//第三方转账编号
            '', $status, $fee);
    }

}
