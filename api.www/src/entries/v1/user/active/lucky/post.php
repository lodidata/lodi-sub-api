<?php

use Logic\Wallet\Wallet;
use Model\FundsDealLog;
use Utils\Www\Action;
use Model\Active;

/**
 * 点击抽奖
 */
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "幸运轮盘-点击抽奖";
    const TAGS = "优惠活动";
    const QUERY = [];
    const SCHEMAS = [
        "award_id"      => "int(required) #规则ID",
        "award_name"    => "string(required) #第几等奖 八等奖",
        "award_money"   => "float(required) #中奖金额 如：1000",
        "withdraw_val"  => "int(required) #中奖概率值 5000"
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();

//        $userId = 5;
        /* 判断是否为试玩用户*/
        if ($this->auth->getTrialStatus()) {
            return $this->lang->set(-2);
        }

        /* 查询幸运转盘配置*/
        $luckyData = DB::table('active')->select([
            'id',
            'name',
            'status',
            'end_time'
        ])->where('type_id', 6)->first();

        if (!$luckyData || $luckyData->status != 'enabled' || $luckyData->end_time < date('Y-m-d H:i:s')) {
            //不存在该类型活动  或者  该活动已停用
            return $this->lang->set(10811);
        }

        /* 取出规则匹配是否中奖*/
        $ruleData = DB::table('active_rule')->select(['rule', 'limit_times'])->where('active_id', $luckyData->id)->first();
        if (!$ruleData) {
            //不存在该类型活动的rule
            return $this->lang->set(-2);
        }

//        $this->redis->hset(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d', time()), $userId,50);die;

        /* 判断抽奖次数*/
        $count = $this->redis->hget(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d'), $userId);


        /* 不存在记录且设置了每天赠送次数.则给用户设置赠送次数*/
        if ($count === null && (int)$ruleData->limit_times) {
            $count = (int)$ruleData->limit_times;
            $this->redis->hset(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d'), $userId, (int)$ruleData->limit_times);

//            $this->redis->hset(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), $userId, (int)$ruleData->limit_times);
        }

        $draw_count = \Model\UserData::where('user_id',$userId)->value('draw_count');
        if ($draw_count + $count < 1) {
            /* 抽奖次数不够*/
            return $this->lang->set(10812);
        }

        $rule = json_decode($ruleData->rule, true);


        $ratio = [];
        $win   = [];

        foreach ($rule as $key=>&$v) {
            $v->img = showImageUrl($v->img);
            $countUserList = $this->redis->hget(\Logic\Define\CacheKey::$perfix['lockyCountUserList'].$v['award_id'], $userId);

            /* 匹配指定用户*/
            foreach ($v['user_list'] as $keyList=>$item) {
                if (in_array($userId, $item) && (int)$countUserList > 0) {

                    /* 被指定中奖*/
                    $win = ['award_id' => $v['award_id'], 'award_name' => $v['award_name'], 'award_money' => $v['award_money'], 'withdraw_val' => $v['withdraw_val']];
                    $ratio = [];

                    /* 减少可抽奖次数*/
                    if ($countUserList > 0) {
                            $this->redis->hIncrBy(\Logic\Define\CacheKey::$perfix['lockyCountUserList'].$v['award_id'], $userId, -1);
                            $countUserList2                     = $this->redis->hget(\Logic\Define\CacheKey::$perfix['lockyCountUserList'].$v['award_id'], $userId);
                            $v['user_list'][$keyList]['times']  = $countUserList2;
                    }
                    goto end;
                }
            }
            array_push($ratio, $v['award_code']);
        }
        end:


        /* 如果没有指定获奖用户. 需要匹配中奖率*/
        if (!$win) {
            //返回中奖下标
            $awardKey = $this->getRand($ratio);
            $win      = $rule[$awardKey];
        }else{
            $rule = json_encode($rule,JSON_UNESCAPED_UNICODE);
            DB::table('active_rule')->where('active_id', $luckyData->id)->update(['rule'=>$rule]);
//            DB::update("update active_rule set rule={$rule}  where active_id = {$luckyData->id}");
        }

        /* 如果中奖. 操作流水表账户表.中奖记录表*/
        if ($win['award_id'] != 9) {
            $wallet = new \Logic\Wallet\Wallet($this->ci);


            //获取用户信息
            $user = (new \Logic\User\User($this->ci))->getInfo($userId);

            //获取用户主钱包余额
            $balance = \Model\Funds::where('id', $user['wallet_id'])
                ->first()->balance;

            $this->db->getConnection()
                ->beginTransaction();

            ////金额结算，往主钱包里加钱
            $amount = isset($win['award_money'])&&!empty($win['award_money']) ? $win['award_money'] : 0;
            $res = $wallet->crease($user['wallet_id'], $amount);
            if (!$res) {
                $this->db->getConnection()
                    ->rollback();
                return $this->lang->set(-2);
            }

            $this->db->getConnection()
                ->commit();


            //操作打码量
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData($userId, ($win['withdraw_val'] / 100) * ($win['award_money'] / 100), 2);
            $rand = random_int(10000, 99999);
            $dealData = [
                "user_id" => $userId,
                "username" => $user['user_name'],
                "deal_number" => date("YmdHis") . random_int(pow(10, 3), pow(10, 4) - 1),
                "order_number" => date('Ymdhis') . str_pad(random_int(1, $rand), 4, '0', 0),
                "deal_type" => 105,
                "deal_category" => 1,
                "deal_money" => $win['award_money'],
                "balance" => $balance + $win['award_money'],
                "memo" => $this->lang->text("roulette of fortune"),
                "wallet_type" => 1,
                'withdraw_bet' => ($win['withdraw_val'] / 100) * ($win['award_money'] / 100),
                'total_require_bet' => $dmlData->total_require_bet,//应有打码量（提现打码量）
                'total_bet' => $dmlData->total_bet,
                'free_money' => $dmlData->free_money
            ];

            FundsDealLog::create($dealData);


            //添加信息到活动参与列表
            DB::table('active_apply')
                ->insert([
                    'user_id' => $userId,
                    'user_name' => $user['user_name'],
                    'active_id' => $luckyData->id,
                    'apply_time' => date('Y-m-d H:i:s'),
                    'active_name' => $luckyData->name,
                    'coupon_money' => $win['award_money'],
                    'withdraw_require' => ($win['withdraw_val'] / 100) * ($win['award_money'] / 100),
                    'memo' => $this->lang->text("roulette of fortune")
                ]);

        }


        /* 减少可抽奖次数*/
        if ($count > 0) {
            $this->redis->hIncrBy(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d'), $userId, -1);
        }else {
            $re = \Model\UserData::where('user_id',$userId)->where('draw_count','>',0)->update(['draw_count'=>\DB::raw('draw_count-1')]);
            if(!$re) {
                /* 抽奖次数不够*/
                return $this->lang->set(10812);
            }
        }


        /* 返回中奖结果*/
        return $win;

    }


    private function getRand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum  = array_sum($proArr);
        $randNum = random_int(1, $proSum);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $randNum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }
};