<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use Utils\Utils;
use function Symfony\Component\Translation\t;

/**
 *
 * sulifupay
 */
class SULIFUPAY extends BASES
{
    public $http_code;
    public $header;

    static function instantiation()
    {
        return new SULIFUPAY();
    }

    //与第三方交互
    public function start()
    {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
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

    //组装数组
    public function initParam()
    {
        //请求参数 Request parameter
        $this->getHeader('/pay/qrorder');
        $data = array(
            'out_trade_no' => $this->orderID,
            'channel'      => '13',
            'amount'       => bcdiv($this->money, 100, 2),
            'currency'     => 'USDT',
            'notify_url'   => $this->payCallbackDomain . '/pay/callback/sulifupay',
            'send_ip'      => $this->clientIp,
            'return_url'   => '',
            'attach'       => '{"usdt_type":"'.$this->rechargeType.'"}'
        );

        $this->header['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/pay/qrorder';
    }

    public function formGet()
    {
//        echo '<pre>';print_r($this->parameter);exit;
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
        //释放curl句柄
        curl_close($ch);
        $this->re = $output;
    }

    public function formPost()
    {
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
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $status = isset($result['code']) ? $result['code'] : 'error';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        $targetUrl = '';
        if ($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($status == 1000) {
                $returnCode = 886;
                if ($result['sign'] == $this->sign($result, false)) {
                    $returnCode = 0;
                    $targetUrl = $result['pay_url'];
                }
            } else {
                $returnCode = 1;
                $message = isset($result['msg']) ? $result['msg'] : 'unknown error';
            }

            $this->return['code'] = $returnCode;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $targetUrl;
            $this->return['pay_no'] = !empty($result['trade_no']) ? $result['trade_no'] : '';
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
        $config = Recharge::getThirdConfig('sulifupay');
        $this->key = $config['key'];

        if (!isset($param['code']) || $param['code'] != 1000) {
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'       => 0,
            'order_number' => $params['out_trade_no'],
            'third_order'  => $params['trade_no'],
            'third_money'  => $params['pay_amount'] * 100,
            'third_fee'    => 0,
            'error'        => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params, false)) {
            if ($params['code'] == 1000) {
                $res['status'] = 1;
            } else {
                throw new \Exception('unpaid');
            }
        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
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


    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {

    }

}
