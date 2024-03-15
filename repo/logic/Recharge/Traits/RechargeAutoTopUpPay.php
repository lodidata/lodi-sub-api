<?php

namespace Logic\Recharge\Traits;

use Logic\Recharge\Pay;
use Model\Bank;
use Model\FundsDeposit;
use Model\FundsVender;
use Logic\Activity\Activity;

trait RechargeAutoTopUpPay
{

    /**
     * 调用支付平台
     *
     * @param int $venderType 支付使用场景 wx 微信 alipay 支付宝 unionpay 网银
     * @param int $money 金额
     * @param int $userId 用户id
     * @param bool $needPre 是否需要优惠
     * @param int $payid 支付ID
     * @param string $pay_no 三方平台ID
     * @param string $paymentType 支付通道类型
     */
    public function autoTopUpPayWebSite(int $money, int $userId, $needPre, int $payid, string $pay_no = null, string $paymentType = '')
    {
        //   充值相关（仅充值）任何数据验证都放verify这里面验证  验证过返回true;
        $gateway = [
            'user_id' => $userId,
            'pay_no' => date("YmdHis") . rand(pow(10, 3), pow(10, 4) - 1),
            'amount' => $money,
            'request_time' => date("Y-m-d H:i:s"),
            'status' => 'pending',
            'inner_status' => 'waiting'
        ];
        // 2. 生成入款单据
        $model = [
            'trade_no' => date("YmdHis") . rand(pow(10, 3), pow(10, 4) - 1),
            'user_id' => $userId,
            'money' => $money,
            'pay_no' => $gateway['pay_no'],
            'over_time' => date("Y-m-d H:i:s", time() + 8 * 60 * 60),
            'ip' => ''
        ];
        // 获取用户信息
        $user = (array)\Model\User::find($userId)->toArray();

        $model['name'] = $user['name'];
        // 添加入款单成功，调用平台支付接口
        $result = array(
            'code' => 886,
            'msg' => [],
            'way' => '',
            'str' => '',
            'money' => $money,
        );
        //获取来源
        $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
        try {

            // 获取支付通道
            $paymentWhere = [
                'pay_config_id' => $payid,
                'status' => 1,
            ];
            if (!empty($paymentType)) {
                $paymentWhere['type'] = $paymentType;
            }
            $paymentInfo = \DB::table('payment_channel')->where($paymentWhere)->first();
            if (empty($paymentInfo)) {
                return [
                    'code' => 886,
                    'msg' => $this->lang->text('The channel code is wrong, please contact the technician')
                ];
            }

            // 判断金额区间
            $checkRes = $this->checkAmountInterval($paymentInfo, $money);
            if ($checkRes['code'] !== 0) {
                return $checkRes;
            }

            $this->db->getConnection()->beginTransaction();

            // 获取支付配置
            $config = \DB::table('pay_config')->where('id', $payid)
                ->where('status','=','enabled')->first();

            $bankInfo = json_encode(['id' => $payid, "pay" => $config->name, "vender" => $config->type], JSON_UNESCAPED_UNICODE);
            $model['deposit_type'] = $payid;
            $model['pay_bank_id'] = $payid;
            $model['pay_no'] = !empty($pay_no) ? $pay_no : '';
            $model['pay_type'] = $payid;
            $model['money'] = $money;
            $model['receive_bank_account_id'] = $payid;
            $model['receive_bank_info'] = $bankInfo;
            $model['payment_id']        = $paymentInfo->id;
            $model['origin'] = isset($origins[$origin]) ? $origins[$origin] : 0;
            //$model['passageway_active']         = is_array($res['active_rule']) ? json_encode($res['active_rule']) : '';
            $re_deposit = FundsDeposit::create($model);

            if ($re_deposit) {
                \DB::table('funds_gateway')->insertGetId($gateway);

                $active = new Activity($this->ci);
                $activeData = $active->rechargeActive($userId, $user, $money, $needPre, 'online', $re_deposit->id);
                $update['state'] = 'online';
                $update['active_apply'] = $activeData['activeApply'];
                $update['withdraw_bet'] = $activeData['withdraw_bet'] ?? 0;
                $update['coupon_withdraw_bet'] = $activeData['coupon_withdraw_bet'] ?? 0;

                if (isset($activeData['state']) && $activeData['state'])
                    $update['state'] .= ',' . $activeData['state'];
                $update['coupon_money'] = $activeData['coupon_money'] ?? 0;
                FundsDeposit::where('id', $re_deposit->id)->update($update);
                $this->db->getConnection()->commit();
                $res['code'] = 0;
                $res['msg'] = 'success';
                $res['str'] = $model['trade_no'];
            } else {
                $this->db->getConnection()->rollback();
                $res['code'] = 886;
                $res['msg'] = $this->lang->text('Busy business, please try again later');
            }
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            $res['code'] = 886;
            $res['msg'] = $this->lang->text('Busy business, please try again later');
        }
        $result = array_merge($result, $res);
        return $result;
    }

    /**
     * @param $payType
     * @param $amount
     * @return array|true
     * 判断金额区间
     */
    public function checkAmountInterval($config, $amount)
    {
        if ($config->min_money > 0 && $amount < $config->min_money) {
            return [
                'code' => 890,
                'msg'  => $this->lang->text(890, [$config->min_money / 100]).'.special_suggestion',
            ];
        }
        if ($config->max_money > 0 && $amount >= $config->max_money) {
            return [
                'code' => 891,
                'msg'  => $this->lang->text(891, [$config->max_money / 100]).'.special_suggestion',
            ];
        }

        if ($config->money_day_stop > 0) {
            $query = \DB::table('funds_deposit')
                ->where('status', '=', 'paid')
                ->where('payment_id', $config->id)
                ->whereRaw('FIND_IN_SET("online",state)');
            $toDayMoney = $query->where('process_time', '>=', date('Y-m-d 00:00:00', time()))->where('process_time', '<=', date('Y-m-d 23:59:59', time()))->sum('money');
            if (bcadd($toDayMoney, $amount) >= $config->money_day_stop) {
                return [
                    'code' => 896,
                    'msg'  => $this->lang->text(896, [$config->money_day_stop / 100])
                ];
            }
        }

        if ($config->money_stop > 0) {
            $stopMoney = \DB::table('funds_deposit')->where('status', '=', 'paid')->where('payment_id', $config['id'])->whereRaw('FIND_IN_SET("online",state)')->sum('money');
            if (bcadd($stopMoney, $amount) >= $config->money_stop) {
                return [
                    'code' => 896,
                    'msg'  => $this->lang->text(896, [$config->money_stop / 100])
                ];
            }
        }

        return [
            'code' => 0,
            'msg'  => 'success'
        ];
    }

}