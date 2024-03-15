<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases,
    Logic\Recharge\Recharge;


class QEPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new QEPAY();
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
        $pay_type = 1700;
        if (is_numeric($this->rechargeType)) {
            $pay_type = $this->rechargeType;
        }

        $data = array(
            'version' => '1.0',
            'mch_id' => $this->partnerID,
            'notify_url'  => $this->payCallbackDomain . '/pay/callback/qepay',
            'mch_order_no'    => $this->orderID,
            'pay_type' => $pay_type,
            'trade_amount'      => floatval(bcdiv($this->money, 100, 2)),
            'order_date' => date('Y-m-d H:i:s'),
            'goods_name' => 'recharge',
        );

        $data['sign']    = $this->sign($data);
        $data['sign_type'] = 'MD5';
        $this->parameter = $data;
        $this->payUrl   .= '/pay/web';
    }

    public function formGet()
    {
        //初始化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl . '?' . http_build_query($this->parameter));
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //释放curl句柄
        curl_close($ch);
        $this->re = $output;
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
        ]);
        // 请求头，可以传数组
        // curl_setopt($ch, CURLOPT_HEADER, $header);
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
        $code  = isset($result['tradeResult']) ? $result['tradeResult'] : 1;
        $respCode = isset($result['respCode']) ? $result['respCode'] : 'FAIL';
        $message = isset($result['tradeMsg']) ? $result['tradeMsg'] : 'errorMsg:' . (string)$this->re;

        if ($this->http_code  == 200) {
            $pay_no = '';
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($code == '1' && strcmp($respCode, "SUCCESS") == 0) {
                $code = 0;
                $targetUrl = isset($result['payInfo']) ? $result['payInfo'] : '';
                $pay_no = isset($result['orderNo']) ? $result['orderNo'] : '';
            } else {
                $code = 886;
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $pay_no;
        } else {
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
        $config    = Recharge::getThirdConfig('QEPAY');
        $this->key = $config['key'];
        $params = $param;

        if (!isset($params['tradeResult']) || $params['tradeResult'] != "1") {
            throw new \Exception('unpaid');
        }

        $res = [
            'status'        => 0,
            'order_number'  => $params['mchOrderNo'],
            'third_order'   => $params['mchOrderNo'],
            'third_money'   => bcmul($params['amount'], 100),
            'third_fee'     => 0,
            'error'         => '',
        ];

        $self_sigin=$this->sign($params);
        //检验状态
        if (strcmp($param['sign'], $self_sigin) == 0) {
            $res['status'] = 1;
        } else {
            throw new \Exception('sign is wrong:' . $param['sign'] . ":" . $self_sigin.":".$this->key);
        }

        return $res;
    }

    //生成签名
    public function sign($param)
    {
        unset($param['sign']);
        unset($param['sign_type']);
        unset($param['signType']);
        unset($param['merRetMsg']);
        $newParam = $param;
        if (!empty($newParam)) {
            $fields = array_keys($newParam);
            $sortParam = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                if ($newParam[$v] === '') {
                    continue;
                }
                $sortParam[] = $v . '=' . $newParam[$v];
            }
            $originalString = implode('&', $sortParam) . '&key=' . $this->key;
        } else {
            $originalString = $this->key;
        }
        return md5($originalString);
    }


    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('qepay');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'merchantId' => $config['partner_id'], //    是   string  商户号 business number
            'bizNum'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'] . '/pay/order/query/status';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['success']) ? $result['success'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->http_code == 200) {
            if ($code === true) {
                //未支付
                if ($result['data']['status'] != 1) {
                    throw new \Exception($result['data']['status']);
                }
                $res = [
                    'status'       => $result['data']['status'],
                    'order_number' => $result['data']['merchantBizNum'],
                    'third_order'  => $result['data']['sysBizNum'],
                    'third_money'  => $result['data']['money'],
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }
}
