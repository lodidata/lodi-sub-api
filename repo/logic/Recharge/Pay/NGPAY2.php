<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * NGPAY2
 * @author
 */
class NGPAY2 extends BASES {
    public $http_code;

    static function instantiation() {
        return new NGPAY2();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();   // 发送请求
        $this->parseRE();    // 处理结果
    }

    //组装数组
    public function initParam() {
        $data = array(
            'uid'           => $this->partnerID,
            'userid'        => $this->userId,
            'amount'        => floatval(bcdiv($this->money, 100,2)),     //单位：元  单笔最小限额:50,单笔最大限额:50000
            'orderid'       => $this->orderID,
            'cate'          => 'GCASH',
            'userip'        => \utils\Client::getIp(),
            'from_bankflag' => 'GCASH',
            'from_username' => \Model\User::where('id', $this->userId)->value('name'),
            'notify'        => $this->payCallbackDomain . '/pay/callback/ngpay2'
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl   .= '/Api/collection';
    }

    public function formPost() {
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }

    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code  = isset($result['success']) ? $result['success'] : true;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            $pay_no = '';
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code === true){
                $code = 0;
                $targetUrl = $result['info']['qrurl'];
                $pay_no = $this->orderID;
            }else{
                $code = 886;
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $pay_no;

        } else{
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }
    }

    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = [])
    {
        $config    = Recharge::getThirdConfig('ngpay2');
        $this->key = $config['key'];

        $params = $param;

        if(!isset($params['status']) || $params['status'] != "verified"){
            throw new \Exception('unpaid');
        }

        $res = [
            'status'        => 0,
            'order_number'  => $params['orderid'],
            'third_order'   => $params['oid'],
            'third_money'   => $params['amount'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];

        unset($params['verified_time']);
        unset($params['extend']);
        unset($params['transactionAmount']);
        unset($params['type']);
        unset($params['USDTTransactionAmount']);
        unset($params['USDTPrice']);

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            $res['status'] = 1;
        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
    }

    //生成签名
    public function sign($param) {
        unset($param['sign']);
        unset($param['from_username']);

        ksort($param);

        $str = '';
        foreach ($param as $k => $v) {
            if(is_null($v) || $v === '') continue;     //值为 null 则不加入签名
            $str .= $k . '=' . $v . '&';
        }

        $sign_str = $str . 'key=' . $this->key;

        return md5($sign_str);
    }


    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('ngpay2');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'uid'       => $config['partner_id'],           //商户号 business number
            'orderid'   => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/Api/collection/query';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['success']) ? $result['success'] : true;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code === true){
                //未支付
                if($result['order']['status'] != 'verified'){
                    throw new \Exception($result['data']['status']);
                }
                $res = [
                    'status'       => $result['order']['status'],
                    'order_number' => $result['order']['orderid'],
                    'third_order'  => $result['order']['oid'],
                    'third_money'  => $result['order']['amount'],
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }
}