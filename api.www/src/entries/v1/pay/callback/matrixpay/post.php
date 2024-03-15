<?php
use Utils\Www\Action;
return new class extends Action
{
    const TITLE = "matrixpay 支付异步回调";
    const TAGS = '支付';
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    public function run() {
        $pay_type  = 'matrixpay';
        $params    = $this->request->getParams();
        $log       = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        \Logic\Recharge\Recharge::addLogByTxt(['third' => $pay_type, 'date' => date('Y-m-d H:i:s'), 'json' => $log, 'response' => ''], 'pay_callback');

        if ($params) {
            $desc = '';
            $flag = 0;
            $pay = new Logic\Recharge\Recharge($this->ci);
            if (!$pay->existThirdClass($pay_type)) {
                $desc = '未有该第三方:' . $pay_type . '类，请技术核查';
            } else {
                try{
                    $re   = $pay->returnVerify($pay_type, $params);
                    $flag = 1;
                }catch (\Throwable $e){
                    $desc = $e->getMessage();
                }
            }
            //写入回调日志表
            $logs = [
                'order_number' => $re['order_number'] ?? '',
                'desc'         => $desc,
                'pay_type'     => $pay_type,
                'content'      => $log,
                'ip'           => \Utils\Client::getIp(),
            ];

            \Logic\Recharge\Recharge::addLogBySql($logs, 'pay_callback');
            //进入队列失败   说明代码或配置有误 定时器定时跑
            if ($flag == 0) {
                $repeat = [
                    'pay_type'  => $pay_type,
                    'method'    => $this->request->getMethod(),
                    'content'   => $log,
                    'error'     => $desc
                ];
                \Logic\Recharge\Recharge::addLogBySql($repeat, 'pay_callback_failed');
                echo $desc;
            }else{
                echo 'success';
            }

        }else{
            echo 'NO PARAMS';
        }
        die();
    }
};
