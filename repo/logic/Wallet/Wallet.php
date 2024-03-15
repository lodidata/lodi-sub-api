<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:19
 */

namespace Logic\Wallet;

use Logic\Define\CacheKey;
use Logic\Funds\DealLog;
use Logic\GameApi\Common;
use Logic\Set\SystemConfig;
use Model\Admin\GameMenu;
use Model\Funds;
use Model\FundsChild;
use Model\Profile;
use Model\UserData;
use Model\TrialUser;
use Model\User;
use Model\TrialFunds;
use Model\FundsDealLog;

/**
 * 钱包模块
 */
class Wallet extends \Logic\Logic
{

    /**
     * 查询钱包ID
     *
     * @param $uid
     *
     * @return mixed
     */
    public function getWalletIdByUid($uid)
    {
        return User::where('id', '=', $uid)->value('wallet_id');
    }

    /**
     * 查询钱包
     *
     * @param $userId
     *
     * @return mixed
     */
    public function getWallet($userId)
    {
        //$wallet_id = User::where('id', $userId)->value('wallet_id');
        $wallet_id = (new Common($this->ci))->getUserInfo($userId)['wallet_id'];

        $primary = Funds::where('id', $wallet_id)
            ->where('status', '=', 'enabled')
            ->selectRaw('id, name, balance,share_balance, balance_before, freeze_withdraw, freeze_append,currency,freeze_money')
            ->first($wallet_id);

        $rs = $primary->toArray();

        $secondary = FundsChild::where('pid', '=', $primary['id'])
            ->where('status', '=', 'enabled')
            ->selectRaw('id, uuid, name, game_type,balance')
            ->get()
            ->toArray();

        $rs['children'] = array_merge([$primary], $secondary);
        return $rs;
    }

    /**
     * 查询试玩钱包
     *
     * @param $userId 用户id
     * @return mixed
     */
    public function getTrialWallet($userId)
    {
        $wallet_id = TrialUser::where('id', $userId)->value('wallet_id');
        $primary = TrialFunds::where('id', $wallet_id)
            ->selectRaw('id, balance, balance_before')
            ->first($wallet_id);

        $rs = $primary->toArray();

        $children = [];
        $rs['children'] = array_merge([
            [
                'uuid' => $primary['id'],
                'name' => $this->lang->text("Master Wallet"),
                'type' => '0',
                'game_type' => $this->lang->text("Master Wallet"),
                'status' => 'enabled',
                'balance' => $primary['balance'],
                'isexist' => 1,
            ],
        ], $children);
        return $rs;
    }

    /**
     * 获取钱包信息
     *
     * @param $userId
     *
     * @return mixed
     */
    public function getInfo($userId)
    {
        $wallet = $this->getWallet($userId);
        // 额度转换开关
        $wallet['transfer'] = 1;
        $wallet["all_balance"] = $wallet['balance'];
        $dml = new \Logic\Wallet\Dml($this->ci);
        $dmlData = $dml->getUserDmlData($userId);
        $wallet["take_balance"] = $dmlData->free_money;
        $wallet["total_bet"] = $dmlData->total_bet;
        $wallet["require_bet"] = $dmlData->total_require_bet;
        return $wallet;
    }

    /**
     * 玩家获取钱包信息（require_bet = total_require_bet - total_bet）
     *
     * @param $userId
     *
     * @return mixed
     */
    public function getWalletInfo($userId)
    {
        $wallet = $this->getWallet($userId);
        // 额度转换开关
        $wallet['transfer'] = 1;
        $wallet["all_balance"] = $wallet['balance'] + FundsChild::where('pid',$wallet['id'])->sum('balance');
        $dml = new \Logic\Wallet\Dml($this->ci);
        $dmlData = $dml->getUserDmlData($userId);
        $wallet["total_bet"] = $dmlData->total_bet;
        $wallet["take_balance"] = $dmlData->free_money > $wallet["all_balance"] ? $wallet["all_balance"] : $dmlData->free_money;
        $wallet["require_bet"] = $dmlData->total_require_bet > $dmlData->total_bet ? $dmlData->total_require_bet - $dmlData->total_bet : $dmlData->total_require_bet;
        //今日盈亏
        $wallet['today_profit'] = $this->getUserTodayProfit($userId);
        return $wallet;
    }

    public function getUserTodayProfit($uid) {
        //好像没用  又影响速度
        return 0;
        $today_profit = $this->redis->get(\Logic\Define\CacheKey::$perfix['userTodayProfit'].$uid);
        if(!$today_profit) {
            $day = date('Y-m-d');
            $types = [
                \Model\FundsDealLog::TYPE_ACTIVITY,
                \Model\FundsDealLog::TYPE_REBET,
                \Model\FundsDealLog::TYPE_AGENT_CHARGES,
                \Model\FundsDealLog::TYPE_REBET_MANUAL,
                \Model\FundsDealLog::TYPE_ACTIVITY_MANUAL,
                \Model\FundsDealLog::TYPE_LEVEL_MANUAL1,
                \Model\FundsDealLog::TYPE_LEVEL_MANUAL2,
                \Model\FundsDealLog::TYPE_LEVEL_MONTHLY,
            ];
            $profit = \DB::table('order_game_user_middle')->where('user_id',$uid)->where('date',$day)->sum('profit');
            $send_money = \Model\FundsDealLog::where('user_id',$uid)->whereIn('deal_type',$types)->where('created','>=',$day)->sum('deal_money');
            $today_profit = intval($profit) + intval($send_money);
            $this->redis->setex(\Logic\Define\CacheKey::$perfix['userTodayProfit'].$uid,30,$today_profit);
        }
        return $today_profit;
    }

    /**
     * 试玩玩家获取钱包信息（require_bet = total_require_bet - total_bet）
     *
     * @param $userId
     *
     * @return mixed
     */
    public function getTrialWalletInfo($userId)
    {
        $wallet = $this->getTrialWallet($userId);
        $wallet["all_balance"] = $wallet['balance'];
        return $wallet;
    }

    /**
     * 中奖后添加余额，资金流水，结算状态
     *
     * @param $user
     * @param string $orderNumber 订单号
     * @param int|array $money 金额
     * @param int $dealType 交易类型
     * @param null $memo
     * @param int $dealDMLMoney
     * @param bool $isAddDML
     */
    public function addMoney($user, $orderNumber, $money, $dealType, $memo = null, $dealDMLMoney = 0, $isAddDML = false)
    {
        /*if ($this->db->getConnection()
                ->transactionLevel() == 0) {
            throw new \Exception("addMoney " . $this->lang->text("Transaction support is required"), 1);
        }*/
        if ($dealType == 2) {
            $dealType = \Model\FundsDealLog::TYPE_PAYOUT_LOTTERY; //派彩
        } else if ($dealType == 3) {
            $dealType = \Model\FundsDealLog::TYPE_SALES; //销售返点
        } else if ($dealType == 4) {
            $dealType = \Model\FundsDealLog::TYPE_REBET; //反水优惠
        } else {
            $dealType = $dealType;
        }
        //主钱包加钱
        if ($money) {
            $this->crease($user['wallet_id'], $money);
        }

        $balance = Funds::where('id', $user['wallet_id'])->value('balance');
        DealLog::addDealLog($user['id'], $user['name'], $balance, $orderNumber, $money, $dealType,$memo,$dealDMLMoney, 'CP', $isAddDML);
        if (in_array($dealType, [101, 102, 106])) {
            $this->luckycode($user['id']);
        }
    }

    /**
     * 修改钱包金额
     *
     * @param  [type]  $wid  钱包ID
     * @param  [type]  $amount  金额分
     * @param  integer $type 1:主钱包(默认) 2:股东分红钱包
     *
     * @return [type]        [description]
     */
    public function crease($wid, $amount, $type = 1)
    {
        $db = $this->db->getConnection();
        /*if ($this->db->getConnection()
                ->transactionLevel() == 0) {
            throw new \Exception("crease " . $this->lang->text("Transaction support is required"), 1);
        }*/

        if($type == 1){
            $field = 'balance';
            $where = "{$field} + $amount >= 0";
        }elseif($type == 2){
            $field = "share_balance";
            $where = "{$field} + $amount >= 0";
        }elseif($type == 3){
            //只有盈亏返佣 系统发放佣金的时候 才能把 share_balance设为负数
            $field = "share_balance";
            $where = "";
        }elseif($type == 4){
            //直推余额
            $field = "direct_balance";
            $where = "{$field} + $amount >= 0";
        }

        $res = \Model\Funds::where('id', $wid);
        $where && $res->whereRaw($where);
        $result = $res->update([
            'balance_before' => $db->raw('balance'),
            "{$field}"       => $db->raw("{$field} + $amount"),
        ]);
        return $result;
    }

    /**
     * 中奖后添加余额，资金流水，结算状态
     *
     * @param int $id ID
     * @param string $name 用户name
     * @param string $orderNumber 订单号
     * @param int $money 金额
     * @param int $dealType 交易类型
     */
    public function addTrialMoney($user, $orderNumber, $money, $dealType, $memo = null)
    {
        /*if ($this->db->getConnection()->transactionLevel() == 0) {
            throw new \Exception("addMoney " . $this->lang->text("Transaction support is required"), 1);
        }*/

        $dealCategory = \Model\FundsTrialDealLog::CATEGORY_INCOME;
        if ($dealType == 2) {
            $dealType = \Model\FundsTrialDealLog::TYPE_PAYOUT_LOTTERY; //派彩
        } else {
            $dealType = $dealType;
        }

        if ($dealType) {
            if ($money) {
                $this->Trialcrease($user['wallet_id'], $money);
            }
            $funds = \Model\TrialFunds::where('id', $user['wallet_id'])->first();
            \Model\FundsTrialDealLog::create([
                "user_id" => $user['id'],
                "username" => $user['name'],
                "order_number" => $orderNumber,
                "deal_type" => $dealType,
                "deal_category" => $dealCategory,
                "deal_money" => $money,
                "balance" => intval($funds['balance']),
                "memo" => $memo,
            ]);
        }
    }

    /**
     * 修改试玩钱包金额
     *
     * @param  [type]  $wid  钱包ID
     * @param  [type]  $amount  金额分
     * @param  integer $type 1 为主钱包 2 为子钱包
     *
     * @return [type]        [description]
     */
    public function Trialcrease($wid, $amount, $type = 1)
    {
        $db = $this->db->getConnection();
        /*if ($this->db->getConnection()
                ->transactionLevel() == 0) {
            throw new \Exception("crease " . $this->lang->text("Transaction support is required"), 1);
        }*/

        $where = "balance + $amount >= 0";
        $res = \Model\TrialFunds::where('id', $wid)
            ->whereRaw($where)
            ->update([
                'balance_before' => $db->raw('balance'),
                'balance' => $db->raw("balance + $amount"),
            ]);

        return $res;
    }


    /**
     * 充值获得免费抽奖次数
     */
    public function luckycode($userId)
    {
        $count = \DB::table('user')
            ->where('id', '=', $userId)
            ->get()
            ->count();
        if ($count < 1) {
            return;
        }


        $lucky_setting = \DB::table('active_rule')
            ->select('status', 'luckydraw_condition','give_time', 'limit_times')
            ->leftJoin('active', 'active_id', '=', 'active.id')
            ->where('template_id', '=', 6)
            ->get()
            ->first();
        if (!$lucky_setting || $lucky_setting->status != 'enabled') {
            return;
        }

        $profile_model = new UserData();
        $profile_where = $profile_model::where('user_id', $userId);

        //获取当前用户当天已充值赠送的免费次数
        $moneyCount = (int)$this->redis->hget(\Logic\Define\CacheKey::$perfix['luckyMoneyCount'] . date('Y-m-d'), $userId);

        //获取当天的充值金额
        $payMoney = FundsDealLog::where('user_id', $userId)
            ->where('created','>=',date('Y-m-d'))
            ->whereIn('deal_type', [101, 102, 106])
            ->sum('deal_money');
        $dealMoney = $payMoney ? $payMoney : 0;
        //将当前的充值金额和当天的充值金额累加

        $sumDealMoney = $dealMoney;
        //根据充值赠送标准来判断当前累计金额赠送次数是否已经达标
        $ruleMoney = json_decode($lucky_setting->luckydraw_condition,true);
        $numSum = 0;//应该添加的总的抽奖次数
        $giveTime = 0;//当前累计金额应该添加的抽奖次数
        $tmp = 0;
        foreach ($ruleMoney as $key => $item) {
            if ($sumDealMoney >= $key) {
                $tmp += $item;
                $numSum +=$item;
            }
        }
        if ($moneyCount < $tmp) {
            $giveTime = $tmp - $moneyCount;
        }


//        if ($money >= $lucky_setting->luckydraw_condition && $lucky_setting->give_time > 0) {
        //如果没有达标，则将此次应赠送的抽奖次数添加进去
        if ($giveTime > 0) {
            $this->redis->hset(\Logic\Define\CacheKey::$perfix['luckyMoneyCount'] . date('Y-m-d'), $userId, $giveTime+$moneyCount);


            $countEveryday = $this->redis->hget(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d'), $userId);
            $count_record = $profile_where->select('draw_count')->first()->draw_count;

            $profile_model = new UserData();
            $profile_where = $profile_model::where('user_id', $userId);

            //每日登陆赠送的免费抽奖次数
            $limit_times = (int)$lucky_setting->limit_times;
            if ($countEveryday === null && $limit_times) {
                $this->redis->hset(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d'), $userId, $limit_times);

                $this->redis->hset(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), $userId, $limit_times+$count_record);
            }
            $res = $profile_where->update(['draw_count' => $giveTime + $count_record]);
            if (!$res) {
                $this->redis->expire(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), 86400);
                $this->redis->expire(\Logic\Define\CacheKey::$perfix['luckyMoneyCount'] . date('Y-m-d'), 86400);
                $this->redis->expire(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d'), 86400);
                return;
            }
            $this->redis->hIncrBy(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), $userId,$giveTime);


            $this->redis->expire(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), 86400);
            $this->redis->expire(\Logic\Define\CacheKey::$perfix['luckyMoneyCount'] . date('Y-m-d'), 86400);
            $this->redis->expire(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d'), 86400);
        }

    }

    //每日重置抽奖次数
    public function resetLuck(){
        $lucky_setting = \DB::table('active_rule')
                            ->select('status', 'luckydraw_condition','give_time', 'limit_times')
                            ->leftJoin('active', 'active_id', '=', 'active.id')
                            ->where('template_id', '=', 6)
                            ->get()
                            ->first();
        if (!$lucky_setting || $lucky_setting->status != 'enabled') {
            $this->logger->debug('抽奖活动暂未开始!');
            return;
        }

        $date=date('Y-m-d');
        $lock_key = $this->redis->setnx(\Logic\Define\CacheKey::$perfix['resetLuck'] . $date, 1);
        $this->redis->expire(\Logic\Define\CacheKey::$perfix['resetLuck'] . $date, strtotime('23:59:59') - time());
        if(!$lock_key) {
            $this->logger->debug('已经重置抽奖次数 ' . $date);
            return false;
        }
        $startTime=microtime(true);
        $this->logger->debug('抽奖次数清零开始'.$startTime);
        $userCnt=\DB::table("user_data")->where('draw_count','>',0)->count();
        $endTime=microtime(true) - $startTime;
        \DB::table("user_data")->where('draw_count','>',0)->update(['draw_count'=>0]);
        $this->logger->debug("共".$userCnt."名用户重置成功".$endTime);
         return 'success';
    }

}