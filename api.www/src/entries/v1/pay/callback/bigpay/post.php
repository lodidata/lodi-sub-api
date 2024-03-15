<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TITLE = "bigpay支付异步回调";
    const TAGS = '支付';
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    public function run() {
        $pay_type = 'bigpay';
        $pay = new \Logic\Recharge\Pay($this->ci);
        $params = $this->request->getParams();
        $log = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        \Logic\Recharge\Recharge::addLogByTxt(['third' => $pay_type, 'date' => date('Y-m-d H:i:s'), 'data' => $params], 'pay_callback');
        //\Logic\Recharge\Recharge::logger($this, ['third' => $pay_type, 'date' => date('Y-m-d H:i:s'), 'data' => $str], 'pay_callback');
        if ($params) {
            $desc = '';
            $re = ['flag' => 0,'order_number' => ''];
                $pay = new Logic\Recharge\Recharge($this->ci);
                if (!$pay->existThirdClass($pay_type)) {
                    $desc = '未有该第三方:' . $pay_type . '类，请技术核查';
                } else {
                    $re = $pay->returnVerify($pay_type, $params);
                    $desc = $re['flag'] == 2 ? '不在IP白名单内(不允加钱)' : (isset($re['msg']) ? $re['msg'] : '');
                }
            //写入回调日志表
            $logs = [
                'order_number' => $re['order_number'],
                'desc' => $desc,
                'pay_type' => $pay_type,
                'content' => $log,
                'ip' => \Utils\Client::getIp(),
            ];
            \Logic\Recharge\Recharge::addLogBySql($logs, 'pay_callback');
            //进入队列失败   说明代码或配置有误 定时器定时跑
            if ($re['flag'] == 0) {
                $repeat = [
                    'pay_type' => $pay_type,
                    'method' => $this->request->getMethod(),
                    'content' => $log,
                    'error'  => $re['error'] ?: ''
                ];
                \Logic\Recharge\Recharge::addLogBySql($repeat, 'pay_callback_failed');
            }
            if (!isset($re) || empty($re['order_number'])) {
                echo 'fail: 回调参数不完整';
            } elseif ($re['flag'] == 2){
                echo '当前IP未在白名单内,禁止访问:' . \Utils\Client::getIp();
            } elseif ($re['flag'] == 1){
                echo 'SUCCESS';
            } else {
                echo $re['error']?: 'ERROR';
            }
        }else{
            echo 'NO PARAMS';
        }
        die();
    }
};
