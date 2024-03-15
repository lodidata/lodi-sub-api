<?php
namespace Logic\Recharge\Traits;
use Logic\Recharge\Pay;
use Model\Bank;
use Model\FundsDeposit;
use Model\FundsVender;
use Logic\Activity\Activity;
trait RechargeOnlinePay {

    /**
     * 调用支付平台
     *
     * @param int $money 金额
     * @param int $userId 用户id
     * @param string $ip ip
     * @param bool $needPre 是否需要优惠
     * @param int $payid 支付id 做限额用
     * @param string $pay_code
     * @return array
     */
    public function onlinePayWebSite(int $money, int $userId, string $ip, $needPre, int $payid, string $pay_code = null, string $pay_type = null,$coin_type =null,$coin_amount)
    {
        //   充值相关（仅充值）任何数据验证都放verify这里面验证  验证过返回true;
        $gateway = [
            'user_id'       => $userId,
            'pay_no'        => date("YmdHis") . rand(pow(10, 3), pow(10, 4) - 1),
            'amount'        => $money,
            'request_time'  => date("Y-m-d H:i:s"),
            'status'        => 'pending',
            'inner_status'  => 'waiting'
        ];
        // 2. 生成入款单据
        $model = [
            'trade_no'      => date("YmdHis") . rand(pow(10, 3), pow(10, 4) - 1),
            'user_id'       => $userId,
            'money'         => $money,
            'pay_no'        => $gateway['pay_no'],
            'over_time'     => date("Y-m-d H:i:s", time() + 8*60 * 60),
            'ip'            => $ip
        ];
        // 获取用户信息
        $user = (array)\Model\User::find($userId)->toArray();

        $model['name'] = $user['name'];
        // 添加入款单成功，调用平台支付接口
        $result = array(
            'code'  => 886,
            'msg'   => [],
            'way'   => '',
            'str'   => '',
            'money' => $money,
        );
        //获取来源
        $origins = ['pc'=>1, 'h5'=>2, 'ios'=>3, 'android'=>4];
        $origin  = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
        //请求支付平台
        $currency = \DB::table('payment_channel as pc')
                       ->leftJoin('currency_exchange_rate as ce','pc.currency_id','=','ce.id')
                       ->where('pc.id', $payid)
                       ->get(['ce.alias','ce.exchange_rate','pc.currency_type'])
                       ->first();
        if($currency->currency_type == 2){
            $money = bcmul(bcdiv($coin_amount ,$currency->exchange_rate,2),100,0);
        }

        $res = $this->runThirdPay($userId, $payid, $money, $model['trade_no'], null, $pay_code, $ip, $origin, null, $pay_type,$coin_type,$coin_amount);
        if(!$res){
            $res['msg']=$this->lang->text("The channel code is wrong, please contact the technician");
        }elseif($res['code'] == 0) {
            try {
                $this->db->getConnection()->beginTransaction();
                $bankInfo = json_encode(['id' => $res['pay_id'], "pay" => $res['payname'], "vender" => $res['scene']],JSON_UNESCAPED_UNICODE);
                $model['deposit_type']              = $res['pay_id'];
                $model['pay_bank_id']               = $res['pay_id'];
                $model['pay_no']                    = !empty($res['pay_no']) ? $res['pay_no'] : '';
                $model['pay_type']                  = $types[$res['scene']] ?? 0;
                $model['money']                     = $res['money'];
                $model['receive_bank_account_id']   = $res['id'];
                $model['payment_id']                = $payid;
                $model['receive_bank_info']         = $bankInfo;
                $model['coin_type']                 = $coin_type;
                $model['currency_name']             = $currency->alias;
                $model['currency_amount']           = $coin_amount;
                $model['rate']                      = $currency->exchange_rate;
                $model['origin']                    = isset($origins[$origin]) ? $origins[$origin] : 0;
                //$model['passageway_active']         = is_array($res['active_rule']) ? json_encode($res['active_rule']) : '';
                $re_deposit = FundsDeposit::create(array_filter($model));

                if($re_deposit) {
                    if(isset($res['fee']) && $res['fee'] > 0){
                        $gateway['fee'] = $res['fee'];
                    }
                    \DB::table('funds_gateway')->insertGetId($gateway);

                    $active                         = new Activity($this->ci);
                    $activeData                     = $active->rechargeActive($userId, $user, $money, $needPre, 'online', $re_deposit->id);
                    $update['state']                = 'online';
                    $update['active_apply']         = $activeData['activeApply'];
                    $update['withdraw_bet']         = $activeData['withdraw_bet'] ?? 0;
                    $update['coupon_withdraw_bet']  = $activeData['coupon_withdraw_bet'] ?? 0;

                    /*if (isset($activeData['state']) && $activeData['state'])
                        $update['state'] .= ',' . $activeData['state'];*/
                    $update['coupon_money'] = $activeData['coupon_money'] ?? 0;
                    FundsDeposit::where('id', $re_deposit->id)->update($update);
                    $this->db->getConnection()->commit();
                }else {
                    $this->db->getConnection()->rollback();
                    $res['code'] = 886;
                    $res['msg'] = $this->lang->text('Busy business, please try again later');
                }
            }catch (\Exception $e){


                $this->logger->error($e->getMessage());
                $this->db->getConnection()->rollback();
                $res['code'] = 886;
                $res['msg'] = $this->lang->text('Busy business, please try again later');
            }
        }else{
            $result['code'] = 886;
            $result['msg'] = [$res['msg']];
        }
        $result = array_merge($result, $res);
        return $result;
    }

    /**
     * 兼容以前，若异步，同步回调数据库没填写的情况
     * @param string $platform
     * @return 返回结果
     */
    public function getReturnUrl() {
        $website = 'https://'.$_SERVER['HTTP_HOST'];
        $weburl = explode('.',$_SERVER['HTTP_HOST']);
        if(isset($weburl[1])&&isset($weburl[2]))
            $weburl = 'https://m.'.$weburl[1].'.'.$weburl[2];
        else
            $weburl = $website;
        return $weburl.'/user';
    }
}