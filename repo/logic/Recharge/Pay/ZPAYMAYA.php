<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * zpaymaya
 */
class ZPAYMAYA extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new ZPAYMAYA();
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
        //请求参数 Request parameter
        $rechargeType = 'PMP';
        if (!empty($this->rechargeType)) {
            $rechargeType = $this->rechargeType;
        }

        $data = array(
            'merchant' => $this->partnerID,
            'payment_type' => 3,
            'amount' => bcdiv($this->money, 100, 2),
            'order_id' => $this->orderID,
            'bank_code' => $rechargeType,
            'callback_url' => $this->payCallbackDomain . '/pay/callback/zpaymaya',
            'return_url' => $this->returnUrl ?? 'noreturn',
        );

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/api/transfer';
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
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        // 请求头，可以传数组
        // curl_setopt($ch, CURLOPT_HEADER, $header);
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $output = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $output;
    }

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);

        $status = isset($result['status']) ? $result['status'] : '';
        //message返回数组，这里做特殊处理
        $message = 'errorMsg:' . (string)$this->re;
        if (isset($result['message'])) {
            if (is_array($result['message'])) {
                $message = json_encode($result['message']);
            } else {
                $message = $result['message'];
            }
        }
//        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;
        if ($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($status == 1) {
                $code = 0;
                $targetUrl = $result['redirect_url'];
            } else {
                $code = 1;
                $targetUrl = '';
            }

            $this->return['code'] = $code;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $targetUrl;
            $this->return['pay_no'] = $this->orderID;

        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code . ' msg:' . $message;
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
        $config = Recharge::getThirdConfig('zpaymaya');
        $this->key = $config['key'];

        if (!isset($param['status']) || $param['status'] != 5) {
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status' => 0,
            'order_number' => $params['order_id'],
            'third_order' => $params['order_id'],
            'third_money' => $params['amount'] * 100,
            'third_fee' => 0,
            'error' => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            if ($params['status'] == 5) {
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
    public function sign($param)
    {
        unset($param['sign']);

        $newParam = array_filter($param);
        if (!empty($newParam)) {
            $fields = array_keys($newParam);
            $sortParam = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                if (empty($newParam[$v])) {
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

    }

}
