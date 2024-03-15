<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases,
    Logic\Recharge\Recharge,
    DB,
    Logic\Set\SystemConfig;


class KPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new KPAY();
    }

    //与第三方交互
    public function start()
    {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam()
    {
        $bank_id = DB::table('bank')->where(['code'=>'Gcash','status'=>'enabled'])->value('id');
        $user_bank = (array)DB::table('bank_user')
            ->where(['user_id' => $this->userId, 'bank_id' => $bank_id,'state' => 'enabled', 'role' => 1])
            ->first(['card']);
        if (count($user_bank) > 0) {
            $user_card = \Utils\Utils::RSADecrypt($user_bank['card']);
        } else {
            $user_card = '';
        }
        $station_host = SystemConfig::getModuleSystemConfig('domain')['h5'] ?? '';
        $host_arr = [];
        if (!empty($station_host)) $host_arr = explode(',', $station_host);
        $data = array(
            'mer_account' => $this->partnerID,
            'user_account' => (string)$user_card,
            "order_no" => $this->orderID,
            "amount" => floatval(bcdiv($this->money, 100, 2)),
            "currency" => "PHP",
            "pay_code" => "GCASH",
            "callback_url" => $this->payCallbackDomain . '/pay/callback/kpay',
            "station_url" => count($host_arr) > 0 ? $host_arr[0] . '/pages/recharge/index' : ""
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl   .= '/api/kpay/recharge';
    }



    public function formPost()
    {
        //        $data_string = json_encode($this->parameter);
        //        var_dump($data_string);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept-Api-Version:v1'
        ]);

        // curl_setopt($ch, CURLOPT_HEADER, 1);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $output = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $output;
    }




    //处理结果
    public function parseRE()
    {
        $result     = json_decode($this->re, true);
        $code  = isset($result['code']) ? $result['code'] : 1;
        //$respCode = isset($result['code']) ? $result['code'] : 'FAIL';
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->http_code  == 200) {

            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($code == 0) {
                $code = 0;
                $targetUrl = isset($result['data']['jump_url']) ? $result['data']['jump_url'] : '';
            } else {
                $code = 886;
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'inner_web';
            $this->return['str']     = $targetUrl;
        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'inner_web';
            $this->return['str'] = $this->re;
        }
    }

    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param)
    {
        $config    = Recharge::getThirdConfig('KPAY');
        $this->pubKey = $config['pub_key'];
        $res = [
            'status'        => 0,
            'order_number'  => $param['order_sn'],
            'third_order'   => $param['mer_no'],
            'third_money'   => bcmul($param['amount'], 100),
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if ($this->signVerify($param)) {
            if ($param['order_status'] === 'success') {
                $res['status'] = 1;
            } elseif ($param['order_status'] === 'canceled'){
                DB::table('funds_deposit')
                    ->where(['trade_no' => $param['order_sn'],'status'=>'pending'])
                    ->update(['status' => 'canceled']);
            } elseif ($param['order_status'] === 'fail'){
                DB::table('funds_deposit')
                    ->where(['trade_no' => $param['order_sn'],'status'=>'pending'])
                    ->update(['status' => 'failed']);
            }else {
                if ($param['status'] == 4) {
                    DB::table('funds_deposit')
                        ->where(['trade_no' => $param['order_sn'],'is_upload_cert'=>0])
                        ->update(['is_upload_cert' => 1]);
                }
            }
        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
    }

    public function signVerify($data)
    {
        if (isset($data['sign']) && !empty($data['sign'])) {
            $sign = base64_decode($data['sign']);
            unset($data['sign']);
            ksort($data);
            reset($data);

            $str = '';
            foreach ($data as $k => $v) {
                if (is_null($v) || $v === '') continue;
                $str .= $k . '=' . $v . '&';
            }
            $str = trim($str, '&');
            $pay_public_key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($this->pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";

            $key = openssl_get_publickey($pay_public_key);
            if (openssl_verify($str, $sign, $key, OPENSSL_ALGO_SHA256) === 1) {
                return true;
            }
        }
        return false;
    }

    //生成签名
    public function sign($data)
    {
        if (isset($data['sign'])) {
            unset($data['sign']);
        }
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $prikey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($this->key, 64, "\n", true) . "\n-----END PRIVATE KEY-----";

        $key = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key, OPENSSL_ALGO_SHA256);

        $sign = base64_encode($sign);
        return $sign;
    }


    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('KPAY');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'mer_account' => $config['partner_id'], //    是   string  商户号 business number
            'order_no'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;

        $this->payUrl    = $config['payurl'] . '/api/kpay/recharge/list';
        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : '1';
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->http_code == 200) {
            if ($code === 0) {
                //未支付
                if ($result['data']['result_status'] != 'success') {
                    throw new \Exception($message);
                }
                $res = [
                    'status'       => 1,
                    'order_number' => $result['data']['order_sn'],
                    'third_order'  => $result['data']['mer_no'],
                    'third_money'  => $result['data']['amount'],
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }

    public function uploadCert($order_number)
    {
        $config     = Recharge::getThirdConfig('KPAY');
        $this->key  = $config['key'];
        $this->payThirdType = $config['type'];
        $this->orderID = $order_number;

        //请求参数 Request parameter
        $data = array(
            'mer_account' => $config['partner_id'], //    是   string  商户号 business number
            'order_no'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;

        $this->payUrl    = $config['payurl'] . '/api/kpay/recharge/upload_cert';
        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : '1';
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            if ($code === 0) {
                $res = [
                    'jump_url' => $result['data']['jump_url'],
                ];
                return $res;
            } else {
                throw new \Exception($message);
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }

    public function showCert($order_number)
    {
        $config     = Recharge::getThirdConfig('KPAY');
        $this->key  = $config['key'];
        $this->payThirdType = $config['type'];
        $this->orderID = $order_number;

        //请求参数 Request parameter
        $data = array(
            'mer_account' => $config['partner_id'], //    是   string  商户号 business number
            'order_no'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;

        $this->payUrl    = $config['payurl'] . '/api/kpay/recharge/show_cert';
        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : '1';
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            if ($code === 0) {
                $res = [
                    'jump_url' => $result['data']['jump_url'],
                ];
                return $res;
            } else {
                throw new \Exception($message);
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }

    public function showMatch($order_number)
    {
        $config     = Recharge::getThirdConfig('KPAY');
        $this->key  = $config['key'];
        $this->payThirdType = $config['type'];
        $this->orderID = $order_number;

        //请求参数 Request parameter
        $data = array(
            'mer_account' => $config['partner_id'], //    是   string  商户号 business number
            'order_no'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;

        $this->payUrl    = $config['payurl'] . '/api/kpay/recharge/show_match';
        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : '1';
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            if ($code === 0) {
                $res = [
                    'jump_url' => $result['data']['jump_url'],
                ];
                return $res;
            } else {
                throw new \Exception($message);
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }

    public function cancelRecharge($order_number)
    {
        $config     = Recharge::getThirdConfig('KPAY');
        $this->key  = $config['key'];
        $this->payThirdType = $config['type'];
        $this->orderID = $order_number;

        //请求参数 Request parameter
        $data = array(
            'mer_account' => $config['partner_id'], //    是   string  商户号 business number
            'order_no'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;

        $this->payUrl    = $config['payurl'] . '/api/kpay/recharge/cancel';
        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : '1';
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            if ($code === 0) {
                return [];
            } else {
                throw new \Exception($message);
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);

    }

}
