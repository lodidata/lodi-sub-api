<?php
namespace Logic\Recharge\Traits;

use Logic\Define\CallBack;
use Utils\Client;
use Utils\Utils;
use Model\PayConfig;

trait RechargeCallback{
    /*
     * 金额统一为分操作
     * @return array flag => 1 OK,0 进入回调 ,2 不在IP白名单内  order_number =>订单号
     */
    public function returnVerify($pay_type, $data){

        $res = null;
        $obj = $this->getThirdClass($pay_type);   //初始化类
        //第三方验签返回数组[status=1 通过  0不通过,order_number = '订单','third_order'=第三方订单,'third_money'='金额','error'='未有该订单/订单未支付/未有该订单']
        $res = $obj->returnVerify($data);

        if($res) {
            //微信和支付宝源生的排除
            $online_pay_type = PayConfig::getOnlinePayType();
            if (!in_array($pay_type, $online_pay_type)) {
                throw new \Exception($res['order_number'].' 没有'.$pay_type.'类型');
            }

            //验签通过IP不在白名单中   返回数据的特殊性
            if ($this->isIPBlack($pay_type)) {
                $ip = \Utils\Client::getIp();
                throw new \Exception("类型:{$pay_type} ip:{$ip} 不在ip白名单内}");
            }

            if ($res['status']) {
                $order_number = $res['order_number'];
                $money        = $res['third_money'];

                $pay     = new \Logic\Recharge\Pay($this->ci);
                $deposit = $pay->getDepositByOrderId($order_number);
                if(!$deposit){
                    throw new \Exception('订单不存在');
                }

                //第三方金额与本地金额不一致
                $payment_info = \DB::table('payment_channel')->select('currency_type')->where('id',$deposit->payment_id)->first();
                if(!empty($payment_info) && $payment_info->currency_type == 2 && is_numeric($deposit->currency_amount) && $deposit->currency_amount > 0){
                    if($money != $deposit->currency_amount){
                        throw new \Exception('两边金额不一致 本地金额：'.$deposit->currency_amount.' 第三方金额: '.$money);
                    }
                }else{
                    if(abs(bcsub($deposit->money, $money,0))>100){
                        throw new \Exception('两边金额不一致 本地金额：'.$deposit->money.' 第三方金额: '.$money);
                    }
                }
                //回调时间不能超过20分钟 且不能跨天
                $this->isValidCallBackDate($deposit->created);

                \DB::table('funds_deposit')->where('trade_no', '=', $order_number)->where('money','>',0)->update(['pay_no' => $res['third_order']]);
                \DB::table('funds_gateway')->where('pay_no', '=', $order_number)->update(['fee' => $res['third_fee']]);
                if($deposit->status == 'paid'){
                    return true;
                }
                if ($deposit->status != 'pending') {
                    throw new \Exception('订单状态不为pending');
                }

                $recharge = new \Logic\Recharge\Recharge($this->ci);
                $result   = $recharge->onlineCallBack($deposit, 0,$res['memo']??'');

                if ($result) {
                    //直推充值奖励发放
                    if (strpos($deposit->state, 'online') !== false) {
                        $obj = new \Logic\Recharge\Recharge($this->ci);
                        $obj->directRechargeAward($deposit->user_id,$deposit->money);
                    }

                    $result['order_no']   = $order_number;
                    $result['trade_no']   = $order_number;
                    $result['trade_time'] = date('Y-m-d H:i:s', time());
                    $recharge->onlinePaySuccessMsg($result, null);
                }

            }
            return $res;
        }
    }

    public static function getThirdConfig($pay_type){
        global $app;
        $pay = new \Logic\Recharge\Pay($app->getContainer());
        $config = $pay->getOnlinePassageway($pay_type);
        if($config) {
            return $config;
        }else
            return false;
    }


    public function insertQueue(string $order_number,string $third_order=null,float $third_money=null){
        self::logger($this,['queue:'.$order_number],'log_callback');
        \DB::table('order')->where('order_number',$order_number)->update(['third_order'=>$third_order,'third_money'=>$third_money]);
        \DB::table('success_tmp')->insert(['order_number' => $order_number, 'money' => $third_money]);  //防止进程卡或者挂
        try {
            \Utils\MQServer::send('recharge_callback', ['order_number' => $order_number, 'money' => $third_money]);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * 支付IP白名单
     * @param string $type 支付类型
     * @return bool
     */
    public function isIPBlack($type)
    {
        $config = $this->getThirdConfig($type);
        if($config['ip']){
            $ips = explode(',', $config['ip']);
            $cur_ip = \Utils\Client::getIp();
            if(!in_array($cur_ip, $ips)){
                return true;
            }
        }
        return false;
    }

    /**
     * 判断回调时间是否有效
     * 不能超过20分钟 不能跨天
     * @param $depositCreateTime
     * @throws \Exception
     */
    public function isValidCallBackDate($depositCreateTime){
        $time = time();
        $date = date('Y-m-d',$time);
        $deposit_time = strtotime($depositCreateTime);
        $rechargeCallbackTime = (int)\Logic\Set\SystemConfig::getModuleSystemConfig('recharge')['rechargeCallbackTime'];
        //回调时间大于20分钟,0不限制
        if($rechargeCallbackTime != 0 && $time - $deposit_time > $rechargeCallbackTime*60){
            throw new \Exception('回调时间超过20分钟：创建时间 '.$depositCreateTime.' 回调时间: '.date('Y-m-d H:i:s',$time));
        }
        //跨天了
        if($date != substr($depositCreateTime,0,10)){
            throw new \Exception('回调时间跨天：创建时间 '.$depositCreateTime.' 回调时间: '.date('Y-m-d H:i:s',$time));
        }
    }

}