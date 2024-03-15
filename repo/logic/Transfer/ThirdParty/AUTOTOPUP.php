<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

/**
 *
 * AutoTopup代付
 */
class AUTOTOPUP extends BASES
{
    private $httpCode = '';

    //回调
    public function callbackMsg(){
        return 'OK';
    }

    /**
     * 注册会员 101 已存在用户
     * @param array $info
     * @return bool
     */
    public function register()
    {
        // return true;
        $fields = [
            'username' => $this->order['user_name'],
            'phone_number' => $this->order['user_mobile'],
            'password' => 'Aa123456',
            'bank_number' => $this->bankCard,
            'bank_code' => $this->bankCode,
        ];

        $this->parameter = $fields;
        $this->basePostNew('/auto/register');
        $res = $this->re;
        if ($res['status']['code'] == 200 && ($res['message']['code'] == 0) || $res['message']['code'] == 101) {
            return true;
        } else {
            $this->return['code'] = 886;
            $this->return['balance'] = 0;
            $this->return['msg'] = 'AutoTopup:' . $res['message']['message'];
            return false;
        }
    }

    //请求代付接口
    public function runTransfer()
    {
        $res = $this->register();
        if(!$res){
            $this->updateTransferOrder(
                $this->money,
                0,
                '',
                '',
                'failed',
                null,
                'auto 注册失败'
            );
            return false;
        }
        $fields = [
            'username' => $this->order['user_name'],
            'current_credit' => bcdiv($this->money, 100, 2),
            'amount' => bcdiv($this->money, 100, 2),
            'bank_number' => $this->bankCard
        ];

        $this->parameter = $fields;
        $this->basePostNew('/auto/withdraw');
        $res = $this->re;
        //$res = json_decode($res, true);
        if ($res['status']['code'] == 200 && $res['message']['code'] == 0) {
            $this->return['code'] = 10500;
            $this->return['balance'] = $this->money;
            $this->return['msg'] = '';
            $this->transferNo = $res['data']['id'];//交易ID号
        } else {
            $message = isset($res['message']) ?$res['message']['message'] : 'errorMsg:'.(string)json_encode($res, JSON_UNESCAPED_UNICODE);
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
            $this->return['msg'] = 'AutoTopup:' . $message ?? '代付失败';
        }
    }


    //查询余额
    public function getThirdBalance()
    {
        $this->return['code'] = 10509;
        $this->return['balance'] = 100000*100;
        $this->return['msg'] = '';
    }

    //查询代付结果
    public function getTransferResult()
    {
        $this->return['code'] = 886;
        $this->return['balance'] = 0;
        $this->return['msg'] = '无此功能';
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'apikey:' . $this->key
        ]);
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $this->re = json_decode($response, true);
        curl_close($ch);

        $date = [
            'order_number' => $this->orderID,
            'pay_url' => $this->payUrl . $str,
            'json' => json_encode($this->parameter, JSON_UNESCAPED_UNICODE),
            'response' => $response,
            'date' => date('Y-m-d H:i:s')
        ];
        Recharge::addLog($date, 'pay_request_third_log');
    }

    /**
     * 代付异步回调
     * @param array $params
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter = $params;
        $change = 0;
        $pay_no = $params['id'];
        $real_money = $params['amount'] * 100;//以分为单位
        $fee = 0;
        $success_time = date('Y-m-d H:i:s', strtotime($params['datetime']));

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid' || ($this->order['status'] == $params['status'])){
            $this->return = ['code' => 0, 'msg' => 'success'];
        }
        //金额不一致
       /* elseif( $this->order['money'] != $real_money) {
            $this->return = ['code' => 886, 'msg' => 'money error'];
        }*/

        //订单状态
        elseif($params['status'] == 1){
            $change = 1;
            $status = 'paid';
            $message =  '代付成功'. json_encode($params, JSON_UNESCAPED_UNICODE);
            $this->return = ['code' => 0,  'msg' => ''];
        }else{
            $change = 1;
            $status = 'failed';
            $message = '代付失败'. json_encode($params, JSON_UNESCAPED_UNICODE);
            $this->return = ['code' => 0, 'msg' => 'AutoTopup:代付失败'];
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
}
