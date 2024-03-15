<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;


class MANBOPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new MANBOPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        //请求参数 Request parameter
        if(empty($this->rechargeType)){
            $this->rechargeType = 'gcash';
        }
        $data = array(
            'userid'      => $this->partnerID,
            'orderno'     => $this->orderID,
            'desc'        => 'deposit',
            'amount'      => bcdiv($this->money, 100, 2),
        );

        $data['notifyurl'] = $this->payCallbackDomain . '/pay/callback/manbopay';
        $data['backurl']   = $this->payCallbackDomain;
        $data['paytype']   = $this->rechargeType;
        $data['userip']    = '127.0.0.2';
        $data['currency']  = 'PHP';
        $data['sign']      = md5($data['userid'].$data['orderno'].$data['amount'].$data['notifyurl'].$this->key);

        $this->parameter = $data;

    }



    public function formPost() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->arrayToURL($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }




    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code       = isset($result['status']) ? $result['status'] : 0;
        $message    = isset($result['error']) ? $result['error'] : 'errorMsg:'.(string)$this->re;


        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code === 1){
                $targetUrl = $result['payurl'];
            }else{
                $targetUrl = '';
            }

            $this->return['code']    = $code ? 0 : 1;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            if(!empty($result['data']['tradeNo']))
            {
                $this->return['pay_no'] = $result['data']['tradeNo'];
            }else{
                $this->return['pay_no']  = $result['data']['orderNo'] ?? '';
            }
            
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
        $config    = Recharge::getThirdConfig('manbopay');
        $this->key = $config['key'];
        $res = [
            'status'        => 0,
            'order_number'  => $param['orderno'],
            'third_order'   => $param['outorder'],
            'third_money'   => $param['realamount']*100,
            'third_fee'     => 0,
            'error'         => '',
        ];
        $sign = md5($param['currency'].$param['status'].$param['userid'].$param['orderno'].$param['amount'].$this->key);
        //检验状态
        if ($param['sign'] == $sign) {
            if($param['status'] === '1')
            {
                $res['status'] = 1;
            }else{
                throw new \Exception('unpaid');
            }
        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('MANBOPAY');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'userid'    => $config['partner_id'],//    是   string  商户号 business number
            'orderno'   => $order_number,
            'action'    => 'orderquery',
        );

        $data['sign']    = md5($data['userid'].$data['orderno'].$data['action'].$this->key);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'];

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['status']) ? $result['status'] : 0;
        $message = isset($result['error']) ? $result['error'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code === '1'){
                $res = [
                    'status'       => $result['status'],
                    'order_number' => $result['orderno'],
                    'third_order'  => $result['outorder'],
                    'third_money'  => $result['realamount'] * 100,
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }



}
