<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * SHUNFAPAYMAYA
 * @author
 */
class SHUNFAPAYMAYA extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new SHUNFAPAYMAYA();
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
        $type = '02';
        if (is_numeric($this->rechargeType)) {
            $type = $this->rechargeType;
        }

        $data = array(
            'client_id' => $this->partnerID,
            'bill_number' => $this->orderID,
            'type' => $type,
            'amount' => floatval(bcdiv($this->money, 100, 2)),
            'depositor_name' => '-',
            'notify_url' => $this->payCallbackDomain . '/pay/callback/shunfapaymaya',
            'return_url' => $this->returnUrl ?? 'noreturn'
        );

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/api/v3/deposit';
    }


    public function formPost()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
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
        $result = json_decode($this->re, true);
        //echo json_encode($this->parameter);
        //var_dump($result);exit;
        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->http_code == 200) {
            $pay_no = '';
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ((int)$code === 0) {
                $code = 0;
                $targetUrl = $result['url'];
                $pay_no = $result['bill_number'];
            } else {
                $code = 886;
                $targetUrl = '';
            }

            $this->return['code'] = $code;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $targetUrl;
            $this->return['pay_no'] = $pay_no;

        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code . ' message:' . $message;
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
        $config = Recharge::getThirdConfig('shunfapaymaya');
        $this->key = $config['key'];

        $params = $param;

        if (!isset($params['status']) || $params['status'] != "已完成") {
            throw new \Exception('unpaid');
        }

        $res = [
            'status' => 0,
            'order_number' => $params['bill_number'],
            'third_order' => $params['bill_number'],
            'third_money' => bcmul($params['amount'], 100),
            'third_fee' => 0,
            'error' => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            $res['status'] = 1;
        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
    }

    //生成签名
    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        unset($data['sign']);
        ksort($data);
        $str = urldecode(http_build_query($data)) . '&key=' . $this->key;
        return md5($str);
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
