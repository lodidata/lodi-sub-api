<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;
use Utils\Utils;

/**
 *
 * sulifupay代付
 */
class SULIFUPAY extends BASES
{
    private $httpCode = '';
    public  $header;


    //回调，不处理逻辑
    public function callbackMsg()
    {
        return 'success';
    }

    public function getHeader($url = '')
    {
        $this->header = [
            'sid'       => $this->partnerID,
            'timestamp' => time() * 1000,
            'nonce'     => Utils::randStr(),
            'url'       => $url
        ];
    }

    //请求代付接口
    public function runTransfer()
    {
        $this->getHeader('/payfor/trans');
        $params = [
            'out_trade_no'  => $this->orderID,
            'bank_account'  => $this->bankUserName,
            'card_no'       => $this->bankCard,
            'bank_name'     => '400',
            'bank_province' => '',
            'bank_city'     => '',
            'sub_bank'      => '',
            "amount"        => bcdiv($this->money, 100, 2),   //支付金额
            'notify_url'    => $this->payCallbackDomain . '/thirdAdvance/callback/sulifupay',
            'currency'      => 'USDT',
            'send_ip'       => '',
            'attach'        => '{"usdt_type":"ERC20"}'
        ];

        $this->header['sign'] = $this->sign($params);  //校验码
        $this->parameter = $params;
        $this->payUrl .= '/payfor/trans';
        $this->basePostNew();
        $result = isset($this->re) ? json_decode($this->re, true) : '';
        $code = isset($result['code']) ? $result['code'] : 0;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if ($this->httpCode == 200) {
            //true成功,false失败
            if ($code == 1000) {
                $returnCode = 886;
                if ($result['sign'] == $this->sign($result, false)) {
                    $returnCode = 10500;
                    $this->transferNo = $result['trade_no'];   //第三方订单号
                }

                $this->return['code'] = $returnCode;
                $this->return['balance'] = $this->money;
                $this->return['msg'] = $message;
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code'] = 886;
                $this->return['balance'] = 0;
                $this->return['msg'] = 'SULIFUPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code'] = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg'] = $message;
        $this->transferNo = '';   //第三方订单号
    }

    //查询余额
    public function getThirdBalance()
    {
        $this->getHeader('/merchant/balance');

        $this->payUrl .= "/merchant/balance";
        $this->header['sign'] = $this->sign($this->header, false);  //校验码
        $this->parameter = [];

        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code == 1000) {
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($result['balance'], 100);
                $this->return['msg'] = 'success';
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult()
    {
        $this->getHeader('/payfor/orderquery');

        $params = [
            "amount"       => bcdiv($this->money, 100, 2),   //支付金额
            'out_trade_no' => $this->orderID
        ];
        $this->payUrl .= "/payfor/orderquery";
        $this->header['sign'] = $this->sign($params);  //校验码
        $this->parameter = $params;
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 0;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200 && $code == 1000) {
            //订单状态：0 处理中 1 成功 2 失败
            switch ($result['status']) {
                case 'WAIT':
                    $status = 'pending';
                    $this->return = ['code' => 0, 'msg' => $message];
                    break;
                case 'SUCCESS':
                    $status = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                    break;
                case 'FAILURE':
                case 'CLOSE':
                case 'ERROR':
                    $status = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                    break;
            }

            $real_money = bcmul($result['amount'], 100);
            $fee = bcmul($result['charge_fee'], 100);
            $this->updateTransferOrder($this->money, $real_money, $result['out_trade_no'], '', $status, $fee, $message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
    }


    //生成签名
    public function sign($body, $needHeader = true)
    {
        unset($body['sign']);
        $str = '';
        if ($needHeader) {
            ksort($this->header);
            $str = $this->arrToStr($this->header);
        }
        ksort($body);
        $str .= $this->arrToStr($body) . $this->key;
        return strtoupper(md5($str));
    }

    public function arrToStr($arr)
    {
        $str = '';
        foreach ($arr as $key => $val) {
            $str .= $key . $val;
        }
        return $str;
    }

    public function transHeader($header)
    {
        $arr = [];
        foreach ($header as $key => $val) {
            $arr[] = $key . ":" . $val;
        }
        return $arr;
    }

    public function basePostNew()
    {
        $this->payRequestUrl = $this->payUrl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);// 0不带头文件，1带头文件（返回值中带有头文件）
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(http_build_query($this->parameter)));
        if (!empty($this->header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->transHeader($this->header));
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); //设置等待时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
        if ($this->sign($params, false) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }
        $amount = bcmul($params['amount'], 100);//以分为单位
        $fee = bcmul($params['charge_fee'], 100);//以分为单位

        //订单状态付款状态：(//订单状态：1000 处理中 )
        if ($this->parameter['code'] == 1000) {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            $status = 'failed';
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $amount, $params['trade_no'],//第三方转账编号
            '', $status, $fee);
    }

}
