<?php

namespace Logic\Lottery;

use Logic\Logic;
use \Logic\Wallet\Wallet;
use Model\Admin\Active as ActiveModel;
use Model\User;
use Utils\Telegram;
use Logic\Set\SystemConfig;
use Utils\Utils;

/**
 * 回水统计模块
 *
 * 回水条件名称：
 *  组合（大双、小单、大单、小双）投注额>=投注总额：group_gt
 *  混合（大双、小单）投注额<=投注总额：blend_lt
 *  猜和值的期数>= ：guess_gt
 *  组合（大双、小单、大单、小双）投注额<= ： group_lt
 *  当天投注期数>= ：betting_gt
 *  当天总投注额>=当天最大单投注的：betting_all_gt
 *
 * rebot_way字段存
 *  {
 *      "group_gt":"20,0",
 *      "blend_lt":"50.84,0",
 *      "guess_gt":"2,1",
 *      "group_lt":"20,1",
 *      "betting_gt":"2,1",
 *      "betting_gt":"3,1"
 *  }
 */
class Rebet extends \Logic\Logic {

    public $rebot_way = [
        'loss',     //按当日亏损额回水
        'betting',  //按当日投注额回水
    ];

    public $rebot_way_type = [
        'percentage',   //返现百分比
        'fixed',        //返现固定金额
    ];

    public $rebet_condition_default = [
        'group_gt'       => 0,  // 组合（大双、小单、大单、小双）投注额 >= 投注总额 N%
        'blend_lt'       => 0,  // 混合（大双、小单）投注额<=投注总额 N%
        'guess_gt'       => 0,  // 猜特码的期数>= N
        'group_lt'       => 0,  // 混合（大、小、单、双）的投注额 <= N%
        'betting_gt'     => 0,  // 当天投注期数 >= N期
        'betting_all_gt' => 0,  //当天总投注额>=当天最大单投注的 N倍
    ];

    public $rebet_condition_index = [
        0 => 'group_gt',
        1 => 'blend_lt',
        2 => 'guess_gt',
        3 => 'group_lt',
        4 => 'betting_gt',
        5 => 'betting_all_gt',
    ];

    public $game_type_hall_map = [
        'pc28' => '幸运28类',
        'ssc'  => '时时彩类',
        'sc'   => '赛车类',
        'k3'   => '快3类',
        '11x5' => '11选5类',
        'lhc'  => '六合彩',
        'bjc'  => '百家彩',
    ];

    public function getCurrentBetUser($date) {
        $end_date = date('Y-m-d', strtotime($date) + 86400);

        $userList = \Model\LotteryOrder::where('created', '>=', $date)->where('created', '<', $end_date)
            //                                        ->whereIn('user_name', ['eee444'])
            //                                        ->whereIn('hall_id', [32])
                                       ->select([\DB::raw('DISTINCT user_id')])->orderBy('created', 'asc')->get()->toArray();

        return \DB::resultToArray($userList);
    }

    public function getTypeName($t, $isThird = false) {
        $types = ['pc28', 'ssc', 'k3', '11x5', 'sc', 'lhc', 'bjc'];

        $typesName = ['28类', '时时彩类', '快3类', '11选5类', '赛车类', '六合彩', '百家彩'];

        $type = array_combine($types, $typesName);

        if($isThird) {
            return $isThird;
        }

        return $type[$t];
    }

    /**
     * 读取厅配置
     *
     * @param $date
     * @param $levels
     * @param $types
     *
     * @return array
     */
    public function getHallInfo($date, $levels, $types) {
        $hall_levels = [];
        foreach($levels as $level) {
            if(is_array($level)) {
                $hall_levels = array_merge($hall_levels, $level);
            } else {
                $hall_levels[] = $level;
            }
        }

        $date = date('Y-m-d', strtotime($date));
        // $cacheConfig = \Model\RebetCacheConfig::where('count_date', '=', $date)->first();
        // if (empty($cacheConfig)) {
        $rs   = [];
        $hall = \Model\Hall::whereIn('hall_level', $hall_levels)->whereIn('type', $types)->get()->toArray();

        foreach($hall as $v) {
            $v            = (array)$v;
            $rs[$v['id']] = $v;
        }
        //
        //            \Model\RebetCacheConfig::create([
        //                'count_date' => $date,
        //                'content' => json_encode($rs),
        //            ]);
        //        } else {
        //            $rs = json_decode($cacheConfig['content'], true);
        //        }

        return $rs;
    }

    /**
     * 取某类hall配置
     *
     * @param $hallInfo
     * @param $type
     * @param $level
     *
     * @return array
     */
    protected function getHallInfoByType($hallInfo, $type, $level) {
        $rs    = [];
        $level = (array)$level;
        foreach($hallInfo as $id => $v) {
            if($v['type'] == $type && in_array($v['hall_level'], $level)) {
                $rs[] = $v;
            }
        }
        return $rs;
    }

    public function getHallId($hallInfoTypeData, $hall_level) {
        $hallId = 0;
        foreach($hallInfoTypeData as $v) {
            $v = (array)$v;
            if(is_array($hall_level)) {
                if(5 == $v['hall_level']) {
                    $hallId = $v['id'];
                    break;
                }
            } else {
                $hallId = $v['id'];
                break;
            }
        }
        $hallIds = array_values(array_column($hallInfoTypeData, 'id'));
        $this->logger->debug("【返水】获取的大厅Id --- hall.id:" . $hallId . " --- hall_level：" . json_encode($hall_level) . '---' . json_encode($hallIds));
        return $hallId;
    }

    /**
     * 取房间配置
     *
     * @param $hallInfoTypeData
     *
     * @return array
     */
    protected function getRoomByHall($hallInfoTypeData) {
        if(empty($hallInfoTypeData)) {
            return [];
        }

        $ids = array_values(array_column($hallInfoTypeData, 'id'));

        return \Model\Room::whereIn('hall_id', $ids)->get()->toArray();
    }

    /*
     * 获取某个厅的投注记录
     */
    protected function getUserHallBetList($hallIds, $dateStart, $dateEnd, $userId) {
        $db   = $this->db->getConnection();
        $data = $db->table('lottery_order')->whereIn('lottery_order.hall_id', $hallIds)->where('lottery_order.updated', '>=', $dateStart)->where('lottery_order.updated', '<', $dateEnd)->where('lottery_order.user_id', $userId)->whereRaw("find_in_set('open', state)")->select([
            'lottery_order.hall_id',
            'lottery_order.user_id',
            'lottery_order.pay_money',
            'lottery_order.lose_earn',
            'lottery_order.lottery_id',
            'lottery_order.order_number',
            'lottery_order.user_name',
            'lottery_order.play_id',
            'lottery_order.lottery_number',
            'lottery_order.play_number',
            'lottery_order.one_money',
        ])->orderBy('lottery_order.created', 'asc')->get()->toArray();
        //追号
        $chase  = \DB::table('lottery_chase_order_sub AS sub')
                     ->leftjoin('lottery_chase_order AS main', 'main.chase_number', '=', 'sub.chase_number')
                     ->whereIn('main.hall_id', $hallIds)
                     ->where('sub.updated', '>=', $dateStart)
                     ->where('sub.updated', '<', $dateEnd)
                     ->where('main.user_id', $userId)
                     ->whereRaw("find_in_set(sub.state,'winning,lose')")
                     ->orderBy('sub.updated', 'asc')
                     ->get([
                         'main.hall_id',
                         'main.user_id',
                         'main.lottery_id',
                         'main.user_name',
                         'main.play_id',
                         'sub.pay_money',
                         'sub.lose_earn',
                         'sub.order_number',
                         'sub.lottery_number',
                         'sub.play_number',
                         'sub.one_money',
                     ])
                     ->toArray();
        $result = array_merge($chase, $data);
        return $result;
    }

    /**
     * 统计28类
     *
     * @param $orderData
     *
     * @return array
     */
    public function getRebet28Count($orderData) {
        //        $this->logger->info("订单统计的数据：" ."\r\n". var_export($orderData, true));
        $betNumber    = [];
        $allBetAmount = 0; //获取当日总投注
        $dayWin       = 0; //获取当日盈亏
        $amount       = [
            'group_gt'       => 0, // 组合（大双、小单、大单、小双）投注额>=投注总额 N% （投注额）
            'blend_lt'       => 0, // 混合（大双、小单）投注额<=投注总额 N% （投注额）
            'guess_gt'       => [], // 猜特码的期数>= N  （特码彩期列表）
            'group_lt'       => 0, // 混合（大、小、单、双）的投注额 <= N% （投注额）
            'betting_gt'     => [], // 当天投注期数 >= N期 （当天投注彩期列表）
            'betting_all_gt' => 0 // 当天总投注额 >=当天最大单投注的 N倍   （最大值）
        ];

        foreach($orderData as $data) {
            $v1           = (array)$data;
            $dayWin       += $v1['lose_earn'];
            $allBetAmount += $v1['pay_money'];
            $lotteryId    = $v1['lottery_id'];

            if($v1['pay_money'] > $amount['betting_all_gt']) {
                $amount['betting_all_gt'] = $v1['pay_money'];
            }
            $amount['betting_gt'][$lotteryId][] = $v1['lottery_number'];

            if($v1['play_id'] == '151') {//（大双、小单、大单、小双）
                $playNumbers        = array_unique(preg_split("/[|,]+/", $v1['play_number'], -1, PREG_SPLIT_NO_EMPTY));
                $amount['group_gt'] += $v1['one_money'] * count(array_intersect($playNumbers, ['大双', '小单', '大单', '小双']));
            }

            if($v1['play_id'] == '151') {//（大双、小单）
                $playNumbers        = array_unique(preg_split("/[|,]+/", $v1['play_number'], -1, PREG_SPLIT_NO_EMPTY));
                $amount['blend_lt'] += $v1['one_money'] * count(array_intersect($playNumbers, ['大双', '小单']));
            }

            if($v1['play_id'] == '156') { //特码
                $amount['guess_gt'][$lotteryId]   = $amount['guess_gt'][$lotteryId] ?? [];
                $amount['guess_gt'][$lotteryId][] = $v1['lottery_number'];
            }

            if($v1['play_id'] == '151') {//（大、小、单、双）
                $playNumbers        = array_unique(preg_split("/[|,]+/", $v1['play_number'], -1, PREG_SPLIT_NO_EMPTY));
                $amount['group_lt'] += $v1['one_money'] * count(array_intersect($playNumbers, ['大', '小', '单', '双']));
            }
        }

        // 特码期数 清除重复彩期
        $count = 0;
        foreach($amount['guess_gt'] as $k => $v) {
            $amount['guess_gt'][$k] = array_unique($v);
            $count                  += count($amount['guess_gt'][$k]);
        }
        // 重置成计算需要的值
        $amount['guess_gt'] = $count;

        // 总期数 清除重复彩期
        $count = 0;
        foreach($amount['betting_gt'] as $k => $v) {
            $amount['betting_gt'][$k] = array_unique($v);
            $betNumber[]              = "$k" . ',' . count($amount['betting_gt'][$k]);
            $count                    += count($amount['betting_gt'][$k]);
        }
        // 重置成计算需要的值
        $amount['betting_gt'] = $count;
        $betNumberStr         = join(';', $betNumber);
        $this->logger->info("当日总投注: " . var_export($allBetAmount, true));
        $this->logger->info("当日总盈亏: " . var_export($dayWin, true));
        $this->logger->info("当日投注彩种期数lottery_number次数 [lottery_id,count]: " . var_export($betNumberStr, true));

        return [$amount, $allBetAmount, $dayWin, $betNumberStr];
    }

    /**
     * 统计非28类
     *
     * @param $orderData
     *
     * @return array
     */
    public function getRebetElseCount($orderData) {
        $betNumber    = [];
        $allBetAmount = 0; //获取当日总投注
        $dayWin       = 0; //获取当日盈亏
        $amount       = [
            'betting_gt' => [], // 当天投注期数 >= N期 （当天投注彩期列表）
        ];

        foreach($orderData as $v1) {
            $v1           = (array)$v1;
            $dayWin       += $v1['lose_earn'];
            $allBetAmount += $v1['pay_money'];
            $lotteryId    = $v1['lottery_id'];

            //            if ($v1['pay_money'] > $amount['betting_all_gt']) {
            //                $amount['betting_all_gt'] = $v1['pay_money'];
            //            }

            $amount['betting_gt'][$lotteryId][] = $v1['lottery_number'];
        }

        // 总期数 清除重复彩期
        $count = 0;
        foreach($amount['betting_gt'] as $k => $v) {
            $amount['betting_gt'][$k] = array_unique($v);
            $betNumber[]              = "$k" . ',' . count($amount['betting_gt'][$k]);
            $count                    += count($amount['betting_gt'][$k]);
        }
        // 重置成计算需要的值
        $amount['betting_gt'] = $count;
        $betNumberStr         = join(';', $betNumber);

        $this->logger->info("当日总投注: " . var_export($allBetAmount, true));
        $this->logger->info("当日总盈亏: " . var_export($dayWin, true));
        $this->logger->info("当日投注彩种期数lottery_number次数 [lottery_id,count]: " . var_export($betNumberStr, true));
        return [$amount, $allBetAmount, $dayWin, $betNumberStr];
    }

    /**
     * 统计第三方订单数据，如开元棋牌
     * orders里面的bet和profit，如果profit为0，bet表示投注金额和亏损金额
     * 如果profit为正，则为净利润，除去本金的。
     * @param $orderData
     * @return array
     */
    public function getThirdRebetElseCount($orderData) {
        $allBetAmount = 0; //获取当日总投注
        $dayWin       = 0; //获取当日盈亏

        foreach($orderData as $v1) {
            $v1           = (array)$v1;
            $dayWin       += $v1['profit'];//当日赢的钱
            $allBetAmount += $v1['bet'];//支付金额，即投注金额
        }

        $this->logger->info("当日第三方总投注: " . var_export($allBetAmount, true));
        $this->logger->info("当日第三方总盈亏: " . var_export($dayWin, true));
        return [$allBetAmount, $dayWin];
    }

    /**
     * 获取返水统计规则
     * loss：按当日亏损额回水
     * betting:按当日投注额回水
     * percentage：按流水百分比%
     * fixed：按固定金额
     *
     * @param $hallInfo
     * @param $gameId
     *
     * @return bool
     */
    public function getRebetConfig($hallInfo, $gameId) {
        if(empty($hallInfo[$gameId]['rebot_way'])) {
            return false;
        }
        $data = json_decode($hallInfo[$gameId]['rebot_way'], true);
        $way  = $data['type'];
        if(!in_array($way, $this->rebot_way)) {
            return false;
        }
        $rebet_ceiling = intval($hallInfo[$gameId]['rebet_ceiling']);

        $userRebetCfg['type'][] = $way;
        foreach($data['data'] as $key => $value) {
            if('status' == $key) {
                if(!in_array($value, $this->rebot_way_type)) {
                    continue;
                }
                $userRebetCfg['type'][] = $value;
            } else {
                $userRebetCfg['rebet_ceiling'] = $rebet_ceiling;
                foreach($value as $config) {
                    $v            = explode(';', $config);
                    $v1           = explode(',', $v[0]);
                    $min_rebet[]  = $v1[0];
                    $set['range'] = $v1;

                    $set['value']           = $v[1];
                    $set['factor']          = $v[2];
                    $userRebetCfg['data'][] = $set;
                }
            }
        }
        sort($min_rebet);
        $userRebetCfg['rebet_multiple'] = $hallInfo[$gameId]['rebet_multiple'] ?? 0;
        $userRebetCfg['rebet_gt_zero_switch'] = $hallInfo[$gameId]['rebet_gt_zero_switch'] ?? false;
        $userRebetCfg['rebet_min'] = $min_rebet[0];
        $this->logger->info("【返水】统计规则: gameId: {$gameId}" . "\r\n" . var_export($userRebetCfg, true));
        return $userRebetCfg;
    }

    /**
     * 获取回水开关条件
     *
     * @param $type
     * @param $hallInfo
     * @param $hallId
     *
     * @return array
     */
    public function getRebetCondition($type, $hallInfo, $hallId) {
        // 条件转换成数组
        $data = json_decode($hallInfo[$hallId]['rebet_condition'], true);
        // $this->logger->debug('【返水】开关条件配置:' ."\r\n". var_export($data, true));

        $conditions = [];
        if($data) {
            foreach($data as $item) {
                $conditions[$item['type']] = [$item['value'], intval($item['checked'])];
            }
        }
        if($type == 'pc28') {
            // 回水开关
            $opens = [
                'group_gt'       => 0,    // 组合（大双、小单、大单、小双）投注额>=投注总额 N%
                'blend_lt'       => 0,    // 混合（大双、小单）投注额<=投注总额 N%
                'guess_gt'       => 0,    // 猜特码的期数>= N
                'group_lt'       => 0,    // 混合（大、小、单、双）的投注额 <= N%
                'betting_gt'     => 0,  // 当天投注期数 >= N期
                'betting_all_gt' => 0, // 当天总投注额 >=当天最大单投注的 N倍
            ];
        } else {
            // 回水开关
            $opens = [
                'betting_gt' => 0,  // 当天投注期数 >= N期
            ];
        }

        // 条件额度值 = N
        $values = [];
        foreach($opens as $k => $v) {
            if(!empty($conditions[$k]) && $conditions[$k][1]) {
                $values[$k] = (int)$conditions[$k][0];
                $opens[$k]  = (int)$conditions[$k][1];
            } else {
                $values[$k] = 0;
            }
        }
        $this->logger->debug("【返水】开关条件配置: hallId: {$hallId}" . "\r\n" . var_export($values, true));
        return [$opens, $values];
    }

    /**
     * 计算玩家回水金额
     * 满足回水规则区间,则返回对应回水金额
     *
     * @param     $rebetConfigs
     * @param     $dayWin
     * @param int $allBetAmount
     *
     * @return array
     */
    public function userRebetMoney($rebetConfigs, $dayWin, $allBetAmount = 0, $userAmount = null, $runMode = 'rebet', $menuAllBetAmount = null) {
        $this->logger->debug("【返水】回水规则: " . "\r\n" . var_export($rebetConfigs, true));
        $dayWin           = -1 * $dayWin / 100;
        $allBetAmount     = $allBetAmount / 100;
        $rebet_min        = $rebetConfigs['rebet_min'];
        $rebet_way        = $rebetConfigs['type'][0];
        $rebet_money_type = $rebetConfigs['type'][1];

        //回水方式处理
        $value = 0;
        switch($rebet_way) {
            case 'betting': //单日投注额
                if($allBetAmount < $rebet_min) {
                    return false;
                }
                $value = $allBetAmount;
                break;
            case 'loss':  //日亏损
                if($dayWin < $rebet_min) {
                    return false;
                }
                $value = $dayWin;
                break;
            default:
                break;
        }

        //计算回水金额(统一换算为分)
        $userRebet = [];
        $calcRebetMultipleFlag = $userAmount && $rebet_way != 'loss'
            && !empty($userAmount['deposit_user_amount'])
            && !empty($rebetConfigs['rebet_multiple'])
            && $runMode == 'rebet'
            && !empty($menuAllBetAmount);
        if ($calcRebetMultipleFlag) {
            $menuAllBetAmount = $menuAllBetAmount / 100;
            // 所有子类相加≥5倍就扣除,按这个占比扣：子类的流水=子类真实流水*(1-扣掉总金额/真实总流水)
            //扣掉的总金额就是被彩金+充值， 真实总流水是游戏类型下的总流水      然后子类的反水等于流水乘以比例。
            $tmpAmount = ($userAmount['deposit_user_amount'] + $userAmount['coupon_user_amount']);
            if (($menuAllBetAmount / $tmpAmount) >= $rebetConfigs['rebet_multiple']) {
                $value = $value * (1 - sprintf('%.2f', $tmpAmount / $menuAllBetAmount));
                $value = max(0, $value); // 避免出现负数的情况
                $userRebet['tmpAllBetAmount'] = $value * 100;
            }
        }
        foreach($rebetConfigs['data'] as $data) {
            if($value >= $data['range'][0] && $value < $data['range'][1]) {
                switch($rebet_money_type) {
                    case 'fixed': //固定元
                        //                        $money = (int)$data['value']; //元
                        $money              = $data['value']; //元
                        $userRebet['money'] = $money;
                        //应有打码量
                        $userRebet['total_require_bet'] = $userRebet['money'] * $data['factor'] / 100;
                        break;
                    case 'percentage':
                        $money = sprintf('%.2f', $value * $data['value'] / 100); //元
                        if($rebetConfigs['rebet_ceiling']) {
                            $money = ($money >= $rebetConfigs['rebet_ceiling']) ? $rebetConfigs['rebet_ceiling'] : $money;
                        }
                        $userRebet['money']             = $money;
                        $userRebet['value']             = $data['value'];
                        $userRebet['total_require_bet'] = $userRebet['money'] * $data['factor'] / 100;
                        break;
                    default:
                        break;
                }
            }
        }
        return empty($userRebet) ? false : $userRebet;
    }

    /**
     * 计算直推回水金额
     *
     * @param  int   $money                #返水金额
     * @param  int   $user_id              #用户id
     * @param  int   $dateType             #日返day周返week月返month
     *
     * @return array 返水金额
     */
    public function userDirectMoney($money,$total_require_bet,$user_id,$dateType) {
        $return_data = [];

        //获取对应月周日返水规则
        if (SystemConfig::getModuleSystemConfig('direct')['direct_switch'] != 1){
            $return_data['rate']               = 0;
            $return_data['money']              = $money;     //分
            $return_data['total_require_bet']  = sprintf('%.2f', $total_require_bet); //元

            return $return_data;
        }

        switch ($dateType) {
            case 'day':
                $field = ['direct_deposit','direct_register','direct_bkge_increase as bkge_increase'];
                break;
            case 'week':
                $field = ['direct_deposit','direct_register','direct_bkge_increase_week as bkge_increase'];
                break;
            case 'month':
                $field = ['direct_deposit','direct_register','direct_bkge_increase_month as bkge_increase'];
                break;
            default:
                $field = ['direct_deposit','direct_register','direct_bkge_increase as bkge_increase'];
        }

        //获取用户当前直推注册人数 和 充值人数
        $direct_bkge   = \DB::table('user_data')->where('user_id','=',$user_id)->first($field);

        //计算直推奖励（返水金额*（1+直推比例））
        $return_data['rate']               = $direct_bkge->bkge_increase;
        $return_data['money']              = sprintf('%.2f', $money * (1 + $direct_bkge->bkge_increase / 100)); //元
        $return_data['total_require_bet']  = sprintf('%.2f', $total_require_bet * (1 + $direct_bkge->bkge_increase / 100)); //元

        //返水金额 + 直推金额
//        $count_money         = bcadd($money, $direct_money, 2);

        return $return_data;
    }

    /**
     *  计算玩家回水金额
     * 满足回水规则区间,则返回对应回水金额,超过则返回最后一个回水金额
     * @param     $rebetConfigs
     * @param     $dayWin
     * @param int $allBetAmount
     * @return array|false
     */
    public function userRebetWeekMoney($rebetConfigs, $dayWin, $allBetAmount = 0, $userAmount = null, $runMode = 'rebet') {
        $this->logger->debug("【返水】回水规则: " . "\r\n" . var_export($rebetConfigs, true));
        $dayWin           = -1 * $dayWin / 100;
        $allBetAmount     = $allBetAmount / 100;
        $rebet_min        = $rebetConfigs['rebet_min'];
        $rebet_way        = $rebetConfigs['type'][0];
        $rebet_money_type = $rebetConfigs['type'][1];

        //回水方式处理
        $value = 0;
        switch($rebet_way) {
            case 'betting': //单日投注额
                if($allBetAmount < $rebet_min) {
                    return false;
                }
                $value = $allBetAmount;
                break;
            case 'loss':  //日亏损
                if($dayWin < $rebet_min) {
                    return false;
                }
                $value = $dayWin;
                break;
            default:
                break;
        }

        //计算回水金额(统一换算为分)
        $userRebet = [];
        if ($userAmount && $rebet_way != 'loss' && !empty($userAmount['deposit_user_amount']) && !empty($rebetConfigs['rebet_multiple']) && $runMode == 'rebet') {
            // 用户当日流水倍数≥5倍时（具体倍数在后台可配置），计算返水时，流水需要扣除掉本金和彩金。
            $tmpAmount = ($userAmount['deposit_user_amount'] + $userAmount['coupon_user_amount']);
            if (($value / $tmpAmount) >= $rebetConfigs['rebet_multiple']) {
                $value -= $tmpAmount;
                $value = max(0, $value);// 避免出现负数的情况
                $userRebet['tmpAllBetAmount'] = $value * 100;
            }
        }
        $last     = array_pop($rebetConfigs['data']);
        foreach($rebetConfigs['data'] as $data) {
            if($value >= $data['range'][0] && $value < $data['range'][1]) {
                switch($rebet_money_type) {
                    case 'fixed': //固定元
                        //                        $money = (int)$data['value']; //元
                        $money              = $data['value']; //元
                        $userRebet['money'] = $money;
                        //应有打码量
                        $userRebet['total_require_bet'] = $userRebet['money'] * $data['factor'] / 100;
                        break;
                    case 'percentage':
                        $money = sprintf('%.2f', $value * $data['value'] / 100); //元
                        if($rebetConfigs['rebet_ceiling']) {
                            $money = ($money >= $rebetConfigs['rebet_ceiling']) ? $rebetConfigs['rebet_ceiling'] : $money;
                        }
                        $userRebet['money']             = $money;
                        $userRebet['total_require_bet'] = $userRebet['money'] * $data['factor'] / 100;
                        $userRebet['rate']              = $data['value'];
                        break;
                    default:
                        break;
                }
            }
        }
        if($last['range'][0] < $value){
            switch($rebet_money_type) {
                case 'fixed': //固定元
                    //                        $money = (int)$data['value']; //元
                    $money              = $last['value']; //元
                    $userRebet['money'] = $money;
                    //应有打码量
                    $userRebet['total_require_bet'] = $userRebet['money'] * $last['factor'] / 100;
                    break;
                case 'percentage':
                    $money = sprintf('%.2f', $value * $last['value'] / 100); //元
                    if($rebetConfigs['rebet_ceiling']) {
                        $money = ($money >= $rebetConfigs['rebet_ceiling']) ? $rebetConfigs['rebet_ceiling'] : $money;
                    }
                    $userRebet['money']             = $money;
                    $userRebet['total_require_bet'] = $userRebet['money'] * $last['factor'] / 100;
                    $userRebet['rate']              = $last['value'];
                    break;
                default:
                    break;
            }
        }
        return empty($userRebet) ? false : $userRebet;
    }

    /**
     * 28类
     * 发放反水 插入反水记录
     *
     * @param $date
     * @param $wallet   \Logic\Wallet\Wallet
     * @param $hallInfo
     * @param $order
     * @param $hallId
     * @param $level
     * @param $type
     * @param $runMode
     * @param $userId
     *
     * @return array|bool
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function rebet28($date, $wallet, $hallInfo, $order, $hallId, $level, $type, $runMode, $userId, $isThird = false) {
        $level = is_array($level) ? 5 : $level;
        //返水开关条件
        [$conditionOpens, $conditionValues] = $this->getRebetCondition('pc28', $hallInfo, $hallId);
        //返水统计规则
        $rebetConfigs = $this->getRebetConfig($hallInfo, $hallId);

        $money       = 0;
        $loggerTitle = '【返水不通过】';
        [$amount, $allBetAmount, $dayWin, $betNumberStr] = $this->getRebet28Count($order);
        // 通过条件次数
        $succCount = $this->rebet_condition_default;
        $countLog  = [];

        // 判断是否需要判断回水条件
        foreach($conditionOpens as $tp => $isOpen) {
            // 判断开关
            if(!$isOpen) {
                continue;
            }
            // 组合（大双、小单、大单、小双）投注额>=投注总额 N%
            if($tp == 'group_gt') {
                $succCount[$tp] = $amount[$tp] >= $allBetAmount * ($conditionValues[$tp] / 100) ? $succCount[$tp] + 1 : $succCount[$tp];
                $countLog[$tp]  = [$amount[$tp], '>=', $allBetAmount * ($conditionValues[$tp] / 100), '组合（大双、小单、大单、小双）投注额>=投注总额 N%', $succCount[$tp]];
            }
            // 混合（大双、小单）投注额<=投注总额 N%
            if($tp == 'blend_lt') {
                $succCount[$tp] = $amount[$tp] <= $allBetAmount * ($conditionValues[$tp] / 100) ? $succCount[$tp] + 1 : $succCount[$tp];
                $countLog[$tp]  = [$amount[$tp], '<=', $allBetAmount * ($conditionValues[$tp] / 100), '混合（大双、小单）投注额<=投注总额 N%', $succCount[$tp]];
            }
            // 猜特码的期数>= N
            if($tp == 'guess_gt') {
                $succCount[$tp] = $amount[$tp] >= $conditionValues[$tp] ? $succCount[$tp] + 1 : $succCount[$tp];
                $countLog[$tp]  = [$amount[$tp], '>=', $conditionValues[$tp], '猜和值的期数>= N', $succCount[$tp]];
            }
            // 混合（大、小、单、双）的投注额 <= N%
            if($tp == 'group_lt') {
                $succCount[$tp] = $amount[$tp] <= $allBetAmount * ($conditionValues[$tp] / 100) ? $succCount[$tp] + 1 : $succCount[$tp];
                $countLog[$tp]  = [$amount[$tp], '<=', $allBetAmount * ($conditionValues[$tp] / 100), '混合（大、小、单、双）的投注额 <= N%', $succCount[$tp]];
            }
            // 当天投注期数 >= N期
            if($tp == 'betting_gt') {
                $succCount[$tp] = $amount[$tp] >= $conditionValues[$tp] ? $succCount[$tp] + 1 : $succCount[$tp];
                $countLog[$tp]  = [$amount[$tp], '>=', $conditionValues[$tp], '当天投注期数 >= N期', $succCount[$tp]];
            }
            // 当天总投注额 >=当天最大单投注的 N倍
            if($tp == 'betting_all_gt') {
                $succCount[$tp] = $allBetAmount >= $amount[$tp] * $conditionValues[$tp] ? $succCount[$tp] + 1 : $succCount[$tp];
                $countLog[$tp]  = [$allBetAmount, '>=', $amount[$tp] * $conditionValues[$tp], '当天总投注额 >= 当天最大单投注的 N倍', $succCount[$tp]];
            }
        }

        $user     = \Model\User::where('id', $userId)->first();
        $userName = $user['name'];
        $this->logger->info("核对返水条件：hallId: {$hallId} " . "\r\n" . var_export($succCount, true));

        // 判断是否通过回水条件
        if(array_sum($conditionOpens) && array_diff_assoc($succCount, $conditionOpens)) {
            $this->logger->error("返水条件判断不通过");
            if($runMode == 'rebet') {
                //不需反水（反水条件未到达）
                \Model\Rebet::create([
                    'user_id'    => $userId,
                    'user_name'  => $userName,
                    'rebet'      => 0,
                    'win_money'  => $dayWin / 100,
                    'hall_level' => $level,
                    'bet_amount' => $allBetAmount / 100,
                    'bet_number' => $betNumberStr,
                    'a_percent'  => (int)$amount['guess_gt'],
                    'b_percent'  => $allBetAmount == 0 ? 0 : round(floatval($amount['group_lt'] / $allBetAmount), 4),
                    'c_percent'  => $allBetAmount == 0 ? 0 : round(floatval($amount['group_gt'] / $allBetAmount), 4),
                    'd_percent'  => $allBetAmount == 0 ? 0 : round(floatval($amount['blend_lt'] / $allBetAmount), 4),
                    'type'       => $type,
                    'day'        => date('Y-m-d', strtotime($date) + 86400),
                    'status'     => 0,
                    'plat_id'    => $isThird ? $hallId : 0,
                ]);
            }
        } else {
            //计算玩家回水金额
            $userRebet = $this->userRebetMoney($rebetConfigs, $dayWin, $allBetAmount);
            // 判断是否返水
            if(!$userRebet) {
                $this->logger->error("统计回水金额不通过");
                if($runMode == 'rebet') {
                    //记录反水记录（返水金额为0，暂时设置为反水不成功）
                    \Model\Rebet::create([
                        'user_id'    => $userId,
                        'user_name'  => $userName,
                        'rebet'      => 0,
                        'win_money'  => $dayWin / 100,
                        'hall_level' => $level,
                        'bet_amount' => intval($allBetAmount / 100),
                        'bet_number' => $betNumberStr,
                        'a_percent'  => (int)$amount[$this->rebet_condition_index[2]],
                        'b_percent'  => $allBetAmount == 0 ? 0 : round(floatval($amount[$this->rebet_condition_index[3]] / $allBetAmount), 4),
                        'c_percent'  => $allBetAmount == 0 ? 0 : round(floatval($amount[$this->rebet_condition_index[0]] / $allBetAmount), 4),
                        'd_percent'  => $allBetAmount == 0 ? 0 : round(floatval($amount[$this->rebet_condition_index[1]] / $allBetAmount), 4),
                        'type'       => $type,
                        'day'        => date('Y-m-d', strtotime($date) + 86400),
                        'status'     => 0,
                        'plat_id'    => $isThird ? $hallId : 0,
                    ]);
                }
            } else {
                $this->logger->info("【返水】统计回水金额与应用打码量[元]：" . var_export($userRebet, true));
                $money             = $userRebet['money'] * 100; //回水金额
                $total_require_bet = $userRebet['total_require_bet'] * 100; //应有打码量
                $website           = $this->ci->get('settings')['website'];
                try {
                    $this->db->getConnection()->beginTransaction();
                    if($this->db->getConnection()->transactionLevel()) {
                        if (in_array('refuse_rebate', explode(',', $user['auth_status'])) || in_array($user['tags'], $website['notInTags'])) {
                            $this->db->getConnection()->rollback();
                            $loggerTitle = '【返水不通过】';
                        } else {
                            $this->logger->debug('【返水】' . $user['name'] . $type . "   " . $level . '反水开始 ' . $date);
                            //反水开始
                            $rand        = rand(10000, 99999);
                            $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);

                            if($runMode == 'rebet') {
                                \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
                                $hall_type = $hallInfo[$hallId]['type'];
                                $memo      = $this->game_type_hall_map[$hall_type] . '--' . $hallInfo[$hallId]['hall_name'];
//                                $wallet->addMoney($user, $orderNumber, $money, 4, $memo, $total_require_bet);
                                $wallet->addMoney($user, $orderNumber, $money, 701, $memo, $total_require_bet);
                                \Model\Rebet::create([
                                    'user_id'    => $userId,
                                    'user_name'  => $userName,
                                    'rebet'      => sprintf('%.2f', $money / 100),
                                    'win_money'  => $dayWin / 100,
                                    'hall_level' => $level,
                                    'bet_amount' => $allBetAmount / 100,
                                    'bet_number' => $betNumberStr,
                                    'a_percent'  => (int)$amount[$this->rebet_condition_index[2]],
                                    'b_percent'  => $allBetAmount == 0 ? 0 : round(floatval($amount[$this->rebet_condition_index[3]] / $allBetAmount), 4),
                                    'c_percent'  => $allBetAmount == 0 ? 0 : round(floatval($amount[$this->rebet_condition_index[0]] / $allBetAmount), 4),
                                    'd_percent'  => $allBetAmount == 0 ? 0 : round(floatval($amount[$this->rebet_condition_index[1]] / $allBetAmount), 4),
                                    'type'       => $type,
                                    'day'        => date('Y-m-d', strtotime($date) + 86400),
                                    'status'     => 1,
                                    'plat_id'    => $isThird ? $hallId : 0,
                                ]);
                                \Model\UserData::where('user_id', $userId)->increment('rebet_amount', $money);
                            }
                            $loggerTitle = '【返水通过】';
                            $this->db->getConnection()->commit();
                        }
                    }
                } catch(\Exception $e) {
                    $this->logger->error('【返水异常】执行反水失败 --- 彩票类型:' . $type . ", 厅等级：" . json_encode($level) . "hallId: {$hallId}" . "  " . $date . "\r\n" . $e->getMessage());
                    $this->db->getConnection()->rollback();
                }
            }
        }

        //记录log日志
        $data = [
            'title'           => $loggerTitle,
            'type'            => $type,
            'typeName'        => $this->getTypeName($type, $isThird),
            'hallName'        => $hallInfo[$hallId]['hall_name'],
            'hallId'          => $hallId,
            'level'           => $level,
            'userId'          => $userId,
            'succCount'       => $succCount,
            'conditionOpens'  => $conditionOpens,
            'conditionValues' => $conditionValues,
            'amount'          => $amount,
            'countLog'        => $countLog,
            'dayWin'          => $dayWin,
            'allBetAmount'    => $allBetAmount,
            'money'           => (int)$money,
            'rebetConfigs'    => $rebetConfigs,
            'date'            => $date,
            'betNumberStr'    => $betNumberStr,
        ];
        $this->logger->debug($loggerTitle, $data);
        return $data;
    }

    /**
     * 非28类
     * 其他反水
     */
    public function rebetElse($date, $wallet, $hallInfo, $order, $hallId, $level, $type, $runMode, $userId, $isThird = false) {
        $level = is_array($level) ? 5 : $level;
        [$conditionOpens, $conditionValues] = $this->getRebetCondition('else', $hallInfo, $hallId);
        $rebetConfigs = $this->getRebetConfig($hallInfo, $hallId);

        $money       = 0;
        $loggerTitle = '【返水不通过】';
        [$amount, $allBetAmount, $dayWin, $betNumberStr] = $this->getRebetElseCount($order);

        // 通过条件次数
        $succCount = [
            'betting_gt' => 0,
        ];
        $countLog  = [];
        // 判断是否需要判断回水条件
        foreach($conditionOpens as $tp => $isOpen) {
            // 判断开关
            if(!$isOpen) {
                continue;
            }
            // 当天投注期数 >= N期
            if($tp == 'betting_gt') {
                $succCount[$tp] = $amount[$tp] >= $conditionValues[$tp] ? $succCount[$tp] + 1 : $succCount[$tp];
                $countLog[$tp]  = [$amount[$tp], '>=', $conditionValues[$tp], '当天投注期数 >= N期', $succCount[$tp]];
            }
        }

        $user     = \Model\User::where('id', $userId)->first();
        $userName = $user['name'];
        $this->logger->info("核对返水条件：hallId: {$hallId} " . "\r\n" . var_export($succCount, true));

        // 判断是否通过回水条件
        if(array_sum($conditionOpens) && array_diff_assoc($succCount, $conditionOpens)) {
            if($runMode == 'rebet') {
                //不需反水（反水条件未到达）
                \Model\Rebet::create([
                    'user_id'    => $userId,
                    'user_name'  => $userName,
                    'rebet'      => 0,
                    'win_money'  => $dayWin / 100,
                    'hall_level' => $level,
                    'bet_amount' => $allBetAmount / 100,
                    'bet_number' => $betNumberStr,
                    'a_percent'  => 0,
                    'b_percent'  => 0,
                    'c_percent'  => 0,
                    'd_percent'  => 0,
                    'type'       => $type,
                    'day'        => date('Y-m-d', strtotime($date) + 86400),
                    'status'     => 0,
                    'plat_id'    => $isThird ? $hallId : 0,
                ]);
            }
        } else {
            //计算玩家回水金额
            $userRebet = $this->userRebetMoney($rebetConfigs, $dayWin, $allBetAmount);
            $this->logger->info("【返水】统计回水金额与应用打码量[元]：" . var_export($userRebet, true));
            // 判断是否返水
            if(!$userRebet) {
                if($runMode == 'rebet') {
                    //记录反水记录（返水金额为0，暂时设置为反水不成功）
                    \Model\Rebet::create([
                        'user_id'    => $userId,
                        'user_name'  => $userName,
                        'rebet'      => 0,
                        'win_money'  => $dayWin / 100,
                        'hall_level' => $level,
                        'bet_amount' => intval($allBetAmount / 100),
                        'bet_number' => $betNumberStr,
                        'a_percent'  => 0,
                        'b_percent'  => 0,
                        'c_percent'  => 0,
                        'd_percent'  => 0,
                        'type'       => $type,
                        'day'        => date('Y-m-d', strtotime($date) + 86400),
                        'status'     => 0,
                        'plat_id'    => $isThird ? $hallId : 0,
                    ]);
                }
            } else {
                $money             = $userRebet['money'] * 100;//回水金额转为分
                $total_require_bet = $userRebet['total_require_bet'] * 100; //应有打码量
                $website           = $this->ci->get('settings')['website'];
                try {
                    $this->db->getConnection()->beginTransaction();
                    if($this->db->getConnection()->transactionLevel()) {
                        $user = \Model\User::where('id', $userId)->first();
                        if (in_array('refuse_rebate', explode(',', $user['auth_status'])) || in_array($user['tags'], $website['notInTags'])) {
                            $this->db->getConnection()->rollback();
                            $loggerTitle = '【返水不通过】';
                        } else {
                            //反水开始
                            $rand        = rand(10000, 99999);
                            $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);
                            $userName    = $user['name'];

                            if($runMode == 'rebet') {
                                \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
                                $hall_type = $hallInfo[$hallId]['type'];
                                $memo      = $this->game_type_hall_map[$hall_type] . '--' . $hallInfo[$hallId]['hall_name'];
//                                $wallet->addMoney($user, $orderNumber, $money, 4, $memo, $total_require_bet);
                                $wallet->addMoney($user, $orderNumber, $money, 701, $memo, $total_require_bet);
                                \Model\Rebet::create([
                                    'user_id'    => $userId,
                                    'user_name'  => $userName,
                                    'rebet'      => sprintf('%.2f', $money / 100),//回水金额转为元存储
                                    'win_money'  => $dayWin / 100,//赢的钱，转为元
                                    'hall_level' => $level,
                                    'bet_amount' => intval($allBetAmount / 100),//投注额转为元
                                    'bet_number' => $betNumberStr,
                                    'a_percent'  => 0,
                                    'b_percent'  => 0,
                                    'c_percent'  => 0,
                                    'd_percent'  => 0,
                                    'type'       => $type,
                                    'day'        => date('Y-m-d', strtotime($date) + 86400),
                                    'status'     => 1,
                                    'plat_id'    => $isThird ? $hallId : 0,
                                ]);
                                \Model\UserData::where('user_id', $userId)->increment('rebet_amount', $money);//添加到回水总额里面
                            }


                            $loggerTitle = '【返水通过】';
                            $this->db->getConnection()->commit();
                        }
                    }
                } catch(\Exception $e) {
                    $this->logger->debug('【返水异常】执行反水失败 --- 彩票类型:' . $type . ", 厅等级：" . json_encode($level) . "hallId: {$hallId}" . "  " . $date . "\r\n" . $e->getMessage());
                    $this->db->getConnection()->rollback();
                }
            }
        }

        $data = [
            'title'           => $loggerTitle,
            'type'            => $type,
            'typeName'        => $this->getTypeName($type, $isThird),
            'hallName'        => $hallInfo[$hallId]['hall_name'],
            'hallId'          => $hallId,
            'level'           => $level,
            'userId'          => $userId,
            'succCount'       => $succCount,
            'conditionOpens'  => $conditionOpens,
            'conditionValues' => $conditionValues,
            'amount'          => $amount,
            'countLog'        => $countLog,
            'dayWin'          => $dayWin,
            'allBetAmount'    => $allBetAmount,
            'money'           => (int)$money,
            'rebetConfigs'    => $rebetConfigs,
            'date'            => date('Y-m-d', strtotime($date) + 86400),
            'betNumberStr'    => $betNumberStr,
        ];
        $this->logger->debug($loggerTitle, $data);
        return $data;
    }

    /**
     * 第三方订单回水计算
     * $date, $wallet, [$game_id=>$rebet_config], $orderData, $game_id, $rebet_config['type'], $runMode, $user['user_id'], true
     */
    public function threbetElse($date, $wallet, $hallInfo, $order, $type, $runMode, $isThird = false, $batch_no=null, $menuData = null) {
        $game_id    = $order['game_id'];
        [$conditionOpens, $conditionValues] = $this->getRebetCondition('else', $hallInfo, $game_id);//获取回水条件
        $rebetConfigs = $this->getRebetConfig($hallInfo, $game_id);

        $money          = 0;
        $loggerTitle    = '【返水不通过】';
        $allBetAmount   = $order['bet'];
        $dayWin         = $order['profit'];
        $user_id        = $order['user_id'];
        $countLog       = [];
        $bet_money      = 0;
        $coupon_money   = 0;
        $dml_money      = 0;
        $direct_rate    = 0;
        $rptAmount['deposit_user_amount'] = $this->getDepositUserAmount($user_id, 'day', 'deposit_user_amount', $rebetConfigs['rebet_gt_zero_switch']);
        $rptAmount['coupon_user_amount'] = $this->getDepositUserAmount($user_id, 'day', 'coupon_user_amount', $rebetConfigs['rebet_gt_zero_switch']);
        //计算玩家回水金额
        $menuAllBetAmount = $this->menuDataByGameIdAndUserId($menuData, $game_id, $user_id);
        $deductRebet = $this->userRebetMoney($rebetConfigs, $dayWin, $allBetAmount, null, $runMode, 0);
        $userRebet = $this->userRebetMoney($rebetConfigs, $dayWin, $allBetAmount, $rptAmount, $runMode, $menuAllBetAmount);
        if (!empty($userRebet['tmpAllBetAmount']) && $runMode == 'rebet') {
            $allBetAmount = $userRebet['tmpAllBetAmount'];
        }
        //$this->logger->info("【返水】统计回水金额与应用打码量[元]：" . var_export($userRebet, true));
        // 判断是否返水
        if($userRebet) {
            $money             = $userRebet['money'] * 100;//回水金额(分)
            $deductMoney             = $deductRebet['money'] * 100;//回水金额(分)
            $total_require_bet = $userRebet['total_require_bet'] * 100;//应有打码量

            $direct_list            = $this->userDirectMoney($money,$total_require_bet,$user_id,'day');
            $deductDirectList       = $this->userDirectMoney($deductMoney,$total_require_bet,$user_id,'day');
            $total_require_bet      = $direct_list['total_require_bet'] ?? $total_require_bet;
            $money                  = $direct_list['money'] ?? $money;
            $deductMoney            = $deductDirectList['money'] ?? $deductMoney;
            $direct_rate            = $direct_list['rate'];
            $rate                   = $userRebet['value'] *  ($direct_list['rate'] / 100);
            $userRebet['value']     = bcadd($rate,$userRebet['value'],2);

            try {
                    //反水开始
//                    $rand        = rand(10000, 99999);
//                    $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);

                    if($runMode == 'rebet') {
                        /*$user = [
                            'id'        => $user_id,
                            'wallet_id' => $order['wallet_id'],
                            'name'      => $order['name'],
                        ];
                        $wallet->addMoney($user, $orderNumber, $money, 701, $order['type_name'], $total_require_bet);*/
                        \Model\Rebet::create([
                            'user_id'    => $user_id,
                            'user_name'  => $order['name'],
                            'rebet'      => sprintf('%.2f', $money / 100),//回水金额
                            'win_money'  => $dayWin / 100,//利润
                            'hall_level' => 0,
                            'bet_amount' => bcdiv($allBetAmount,100,2),//投注金额
                            'bet_number' => '',
                            'a_percent'  => 0,
                            'b_percent'  => 0,
                            'c_percent'  => 0,
                            'd_percent'  => 0,
                            'type'       => $type,//游戏类别
                            'day'        => date('Y-m-d', strtotime($date) + 86400),
                            'status'     => 1,
                            'plat_id'    => $game_id,//game_id
                            'dml_amount' => sprintf('%.2f', $userRebet['total_require_bet']),
                            'proportion_value' => $userRebet['value'] ?? 0,
                            'batch_no'   => $batch_no
                        ]);

                        \DB::table('rebet_deduct')->insert([
                            'user_id' => $user_id,
                            'batch_no' => $batch_no,
                            'rebet' => $deductMoney,
                            'deduct_rebet' => max(0, $deductMoney - $money),
                            'type' => 701,
                            'deposit_amount' => $rptAmount['deposit_user_amount'],
                            'coupon_amount' => $rptAmount['coupon_user_amount'],
                        ]);

//                        \Model\UserData::where('user_id', $user_id)->increment('rebet_amount', $money);
                    }
                    $loggerTitle = '【返水通过】';
                $bet_money = $allBetAmount;
                $coupon_money = $money;
                $dml_money = $total_require_bet;

            } catch(\Exception $e) {
                $this->logger->error('【返水异常】' . $e->getMessage());
            }
        }

        $data = [
            'title'           => $loggerTitle,
            'type'            => $type,
            'hallId'          => $game_id,
            'level'           => $order['ranting'],
            'userId'          => $user_id,
            'succCount'       => 0,
            'conditionOpens'  => $conditionOpens,
            'conditionValues' => $conditionValues,
            'amount'          => 0,
            'countLog'        => $countLog,
            'dayWin'          => $dayWin,
            'allBetAmount'    => $allBetAmount,
            'money'           => (int)$money,
            'rebetConfigs'    => $rebetConfigs,
            'date'            => date('Y-m-d', strtotime($date) + 86400),
            'betNumberStr'    => '',
            'bet_money'       => $bet_money,
            'coupon_money'    => $coupon_money,
            'dml_money'       => $dml_money,
            'value'           => $userRebet['value'] ?? 0,
            'direct_rate'     => $direct_rate,
        ];
        $this->logger->debug($loggerTitle, $data);
        return $data;
    }

    /**
     * 获取游戏类型的总流水
     * @param $menuData
     * @param $game_id
     * @param $user_id
     * @return int|mixed
     */
    public function menuDataByGameIdAndUserId($menuData, $game_id, $user_id)
    {
        if (isset($menuData[$user_id])) {
            foreach ($menuData[$user_id] as $value) {
                if (isset($value['game_ids'][$game_id])) {
                    return $value['bet'];
                }
            }
        }
        return 0;
    }

    /**
     * 获取用户前一个时间段的充值金额
     * @param string|int $userId 用户id
     * @param string $type 类型
     * @return mixed
     */
    public function getDepositUserAmount($userId, string $type, $field = 'deposit_user_amount', $gtZeroSwitch = false)
    {
        switch ($type) {
            case 'week':
                $tmpEndDate = date('Y-m-d', strtotime('last sunday'));
                $tmpBeginDate = date("Y-m-d", strtotime('-6 days', strtotime($tmpEndDate)));
                break;
            case 'month':
                $tmpEndDate = date('Y-m-27', date('d') > 27 ? time() : strtotime('-1 month'));
                $tmpBeginDate = date("Y-m-28", strtotime('-1 month', strtotime($tmpEndDate)));
                break;
            default:
                $tmpBeginDate = $tmpEndDate = date('Y-m-d', strtotime('-1 day'));
                break;
        }
        $query = \DB::table('rpt_user')
            ->where('user_id', $userId)
            ->where('count_date', '<=', $tmpEndDate)
            ->addSelect('count_date')
            ->where($field, '>', 0)
            ->orderBy('id', 'desc')
            ->limit(1);
        if ($gtZeroSwitch) {
            $query->where('count_date', '>=', $tmpBeginDate);
            return $query->sum($field);
        }
        $lastAmountDate = $query->first();
        if (empty($lastAmountDate)) {
            return 0;
        }
        $tmpUnix = strtotime($lastAmountDate->count_date);
        switch ($type) {
            case 'week':
                $beginDate = date("Y-m-d", strtotime('sunday -6 days', $tmpUnix));
                $endDate = date("Y-m-d", strtotime('sunday', $tmpUnix));
                break;
            case 'month':
                $beginDate = date("Y-m-28", strtotime('-1 month', $tmpUnix));
                $endDate = date('Y-m-27', $tmpUnix);
                break;
            default: // prev day
                $beginDate = $endDate = $lastAmountDate->count_date;
                break;
        }
        $query = \DB::table('rpt_user')
            ->where([
                ['count_date', '>=', $beginDate],
                ['count_date', '<=', $endDate],
            ])
            ->where('user_id', $userId)
            ->groupBy(['user_id']);
//        print_r($query->getBindings());
//        die($query->toSql());
        return $query->sum($field);
    }

    /**
     * 依据用户层级拿 整理数据以层级为下标，其它的返设置
     * @return array|bool
     */
    public function getRebetByUserLevel($hall_level, $type) {
        $arr        = [
            'hall.id',
            'hall.type',
            'hall.hall_level',
            'hall.lottery_id',
            'hall.hall_name',
            'rebet.user_level_id',
            'rebet.rebet_desc',
            'rebet.rebet_condition',
            'rebet.rebet_ceiling',
            'rebet.rebot_way'
        ];
        $data       = \DB::table('hall')->leftJoin('rebet_config AS rebet', 'hall.id', '=', 'rebet.hall_id')
            ->whereIn('hall.hall_level', $hall_level)->whereIn('hall.type', $type)
            ->where('rebet.status_switch', 1)->get($arr)->toArray();
        $user_level = \DB::table('user_level')->get(['id', 'level'])->toArray();

        if(!$user_level || !$data) {
            return false;
        }
        $user_level = array_column($user_level, null, 'id');//以user_level.id为索引
        $res        = [];
        foreach($data as $val) {
            if($val->rebet_condition && isset($user_level[$val->user_level_id])) {//有设置回水条件和用户层级
                $l                 = $user_level[$val->user_level_id]->level;
                $res[$l][$val->id] = (array)$val;//以等级为一级索引，hall.id为二级索引
            }
        }
        return $res;
    }

    //得到有参与派奖的用户ID和用户层级
    public function getRebetUserId($date, array $user_level) {
        $end_date = date('Y-m-d', strtotime($date) + 86400);//今天

        $userList = \Model\SendPrize::where('created', '>=', $date)->where('created', '<', $end_date)->select([\DB::raw('DISTINCT user_id')])->orderBy('created', 'asc')->pluck('user_id')->toArray();
        if(!$userList)
            return false;
        //查询有开启回水设置的等级列表对应的用户列表
        $user = User::whereIn('id', $userList)->whereIn('ranting', $user_level)->get(['id AS user_id', 'ranting'])->toArray();
        foreach($user as &$v) {
            $v = (array)$v;
        }
        return $user;
    }

    /**
     * 执行返水运算
     *
     * @param string $date 投注日期，默认前一天
     * @param string $runMode 运行模式，test不插入数据库
     * @param int    $userId 是否指定跑单个用户，是则传入用户ID，默认0跑所有用户
     *
     * @return array|bool
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function runByUserLevelRebet($date = '', $runMode = 'rebet', $userId = 0) {
        $userId = $runMode == 'rebet' ? 0 : $userId;
        $date   = date('Y-m-d', strtotime(empty($date) ? '-1 day' : $date));//默认前一天

        //加锁
        $lock_key = $this->redis->setnx(\Logic\Define\CacheKey::$perfix['runRebet'] . $date, 1);
        $this->redis->expire(\Logic\Define\CacheKey::$perfix['runRebet'] . $date, 20 * 60 * 60);
        if($runMode == 'rebet' && !$lock_key) {
            $this->logger->debug('【返水】已经计算过返水数据 ' . $date);
            return false;
        }
        $startTime = $date;//前一天
        $endTime   = date('Y-m-d', strtotime($date) + 86400);//今天
        //彩票类型：pc28 幸运28类；k3 快三类；ssc 时时彩；11x5 11选5类；sc 赛车类；lhc 六合彩类
        //$types = ['pc28', 'ssc', 'k3', '11x5', 'sc', 'lhc'];
        $types = ['ssc'];
        //房间(玩法)类型：4,5为pc房[传统模式] => 统一归为5
        /*$levels = [1, 2, 3, [4, 5], 6];//1 回水厅，2 保本厅，3 高赔率厅，4 PC房，5 传统，6 直播
        $hallInfo = $this->getRebetByUserLevel([1, 2, 3, 4, 5, 6], $types);*/
        $levels   = [5];//1 回水厅，2 保本厅，3 高赔率厅，4 PC房，5 传统，6 直播
        $hallInfo = $this->getRebetByUserLevel([5], $types);
        if(!$hallInfo) {
            $this->logger->debug('【返水】执行反水开始 --- 彩票类型:无层级设置返水' . $date);
            return;
        }
        $wallet   = new Wallet($this->ci);
        $countLog = [];
        if($userId) {
            $ranting  = \DB::table('user')->where('id', $userId)->value('ranting');
            $userList = [['user_id' => $userId, 'ranting' => $ranting]];
        } else {
            $userList = $this->getRebetUserId($date, (array)array_keys($hallInfo));
        }

        if(empty($userList)){
            return false;
        }
        foreach($userList as $user) {
            if(!isset($hallInfo[$user['ranting']]))
                continue;
            $levelHallInfo = $hallInfo[$user['ranting']];//等级对应的厅信息
            foreach($types as $type) {//['pc28', 'ssc', 'k3', '11x5', 'sc', 'lhc']
                foreach($levels as $level) {
                    $hallInfoTypeData = $this->getHallInfoByType($levelHallInfo, $type, $level);//查询类型和等级对应的厅列表
                    $hallIds          = array_values(array_column($hallInfoTypeData, 'id'));//得到hall_id
                    //获取大厅配置ID
                    $hall_level = is_array($level) ? 5 : $level;
                    $hallId     = $this->getHallId($hallInfoTypeData, $hall_level);
                    //以type  厅ID判断访用户是否已经返水了
                    $rebetYes = \DB::table('rebet')->where('user_id', $user['user_id'])->where('hall_level', $hall_level)->where('type', $type)->where('day', date('Y-m-d', strtotime($date) + 86400))->value('id');
                    if($runMode == 'rebet' && $rebetYes) {
                        $this->logger->debug('【返水】 彩票类型:' . $type . ",厅等级：" . json_encode($level) . " 该用户{$user['user_id']}投注数据，已反水 " . $date);
                        continue;
                    }
                    $orderData = $this->getUserHallBetList($hallIds, $startTime, $endTime, $user['user_id']);
                    if(empty($orderData)) {
                        $this->logger->debug('【返水】 彩票类型:' . $type . ",厅等级：" . json_encode($level) . " 没有用户{$user['user_id']}投注数据，不需反水 " . $date);
                        continue;
                    }
                    $this->logger->debug('【返水】执行反水开始 --- 彩票类型:' . $type . ", 厅等级：" . json_encode($level) . $date);
                    if('pc28' == $type) {
                        $temp = $this->rebet28($date, $wallet, $levelHallInfo, $orderData, $hallId, $level, $type, $runMode, $user['user_id']);
                    } else {
                        $temp = $this->rebetElse($date, $wallet, $levelHallInfo, $orderData, $hallId, $level, $type, $runMode, $user['user_id']);
                    }
                    if(!empty($temp)) {
                        if($temp['money'] > 0) {
                            $title = $this->ci->get('settings')['website']['name'];//标题
                            //发送回水信息
                            $content  = ["Dear user, congratulations on your daily rebate amount of %s, The more games, the more rebates. If you have any questions, please consult our 24-hour online customer service", $temp['money'] / 100];
                            $exchange = 'user_message_send';
                            \Utils\MQServer::send($exchange, [
                                'user_id' => $user['user_id'],
                                'title'   => json_encode("Backwater news"),
                                //'content' => vsprintf($content, [$temp['allBetAmount'] / 100, $temp['money'] / 100]),
                                'content' => json_encode($content),
                            ]);
                        }
                        //插入日志数据
                        $rebetLog = [
                            'user_id'            => $user['user_id'],
                            'rebet_user_ranting' => $user['ranting'],
                            'desc'               => json_encode($temp),
                        ];
                        \DB::table('rebet_log')->insert($rebetLog);
                        $countLog = $temp;
                    }
                    $this->logger->debug('【返水】执行反水成功 --- 彩票类型:' . $type . ", 厅等级：" . json_encode($level) . $date);
                }
            }
        }

        if($runMode == 'rebet') {
            //回水消息通知
            $exchange = 'user_rebet_message';
            \Utils\MQServer::send($exchange, ['rebet_date' => date('Y-m-d', strtotime($date) + 86400)]);
        }

        return $countLog;
    }

    /**
     * @param string $dateType 活动周期
     * @return string
     */
    public function runByWeekActivity($dateType, $startTime=null, $endTime=null) {
        switch($dateType) {
            case "month":
                $deal_type = 703;    //701-日回水，702-周回水，703-月回水
                //上个月28号
                !$startTime && $startTime = date("Y-m-28", strtotime(date('Y-m-01')) - 86400);
                !$endTime && $endTime   = date('Y-m-27');
                $activityType=9;
                $dateStr='月';
                $type=3;
                $batchTime=$startTime.'~'.$endTime;
                break;
            case "week":
            default :
                //传入开始时间  为了手动补发返水
                if(!$startTime){
                    //获取周一的时间
                    if(date('w') == 1){
                        $startTime = date("Y-m-d", strtotime('last monday'));
                    }else{
                        $startTime = date("Y-m-d", strtotime('-1 week last monday'));
                    }
                }
                $deal_type = 702;    //701-日回水，702-周回水，703-月回水
                //传入结束时间
                !$endTime && $endTime   = date('Y-m-d', strtotime("-1 sunday",time()));
                $activityType=8;
                $dateStr='周';
                $type=2;
                $batchTime=date('Y-m-d',strtotime($startTime)).'~'.date('Y-m-d',strtotime($endTime));
                break;
        }
        $date     = date('Y-m-d H:i:s', time());
        $activity = \DB::table("active")
                       ->where('type_id', '=', $activityType)
                       ->where('status', '=', "enabled")
                       ->where('begin_time', '<', $date)
                       ->where('end_time', '>', $date)
                       ->first(['id', 'name', 'type_id']);
        if(empty($activity)) {
            $this->logger->error("暂无{$dateType}返水活动");
            return false;
        }
        $rule = \DB::table("active_rule")->where("template_id", '=', $activity->type_id)->where("active_id", '=', $activity->id)->first(['id', 'issue_time', 'issue_cycle', 'issue_mode', 'rule']);
        if(empty($rule) || empty($rule->rule)) {
            $this->logger->error("{$activity->name}活动暂未配置规则");
            return false;
        }
        $lastEndTime = date('Y-m-d',strtotime($endTime)+3600*24);
        $activityCnt=\DB::table('active_apply')
                        ->where('active_id','=',$activity->id)
                        ->where('apply_time','>=',$lastEndTime)
                        ->count();
        if($activityCnt > 0){
            $this->logger->error('【返水】已经计算过返水数据 ' . $date);
            return false;
        }

        /**
         * 取规则的值
         * type:betting 按照当日投注额回水；type:loss按照当日亏损额回水
         * status :fixed 按固定金额,percentage 按百分比
         */
        $ruleData = json_decode($rule->rule, true);
        if(empty($ruleData)) {
            $this->logger->error("{$activity->name}活动暂未配置规则");
            return false;
        }
        $sTime=microtime(true);
        $this->logger->error('游戏返水开始时间'.$sTime);
        $wallet = new Wallet($this->ci);
        $userList = $this->getActivityOrderUserIds($startTime, $endTime, $activityType==9?'day':'week');

        //已参数活动的用户ID集
       /* $activityUsers=(array)\DB::table('active_apply')
            ->where('active_id','=',$activity->id)
            ->where('apply_time','>=',$applyStartTime)
            ->where('apply_time','<=',$applyEndTime)
            ->pluck('user_id')->toArray();*/
        $batchNo=time();

        foreach($userList as $u){
            /*if(!empty($activityUsers) && in_array($u->user_id, $activityUsers)){
                continue;
            }*/
            $this->logger->debug("游戏返水 userId:".$u->user_id);
            $userRebet = $this->getUserActivityBkgeMoney($u->user_id, $startTime, $endTime, $ruleData, $activityType==9?'month':'week');
            // 判断是否返水
            if($userRebet['money'] > 0) {
                $this->logger->info("【活动返水】统计回水金额与应用打码量[元]：" . var_export($userRebet, true));

                $deductMoney = bcmul($userRebet['deduct_money'],100,2);
                $money             = bcmul($userRebet['money'],100,2); //回水金额
                $total_require_bet = bcmul($userRebet['total_require_bet'],100,2); //应有打码量
                $user = (array)\DB::table('user')->where('id', $u->user_id)->first(['id','name','wallet_id','ranting']);

                //直推返水
                $deductDirect            = $this->userDirectMoney($deductMoney,$total_require_bet,$u->user_id,$dateType);
                $direct_list            = $this->userDirectMoney($money,$total_require_bet,$u->user_id,$dateType);
                $total_require_bet      = $direct_list['total_require_bet'] ?? $total_require_bet;
                $deductMoney            = $deductDirect['money'] ?? $deductMoney;
                $money                  = $direct_list['money'] ?? $money;
//                $userRebet['value']     = bcadd($direct_list['rate'],$userRebet['rate'],2);

                try {
                    $this->db->getConnection()->beginTransaction();
                    if($this->db->getConnection()->transactionLevel()) {
                        //反水开始
                        /*$rand        = rand(10000, 99999);
                        $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);
                        \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
                        $memo = "{$dateStr}返水活动: 用户:" . $user['name'] . ",金额:" . $money /100;
//                        $wallet->addMoney($user, $orderNumber, $money, 4, $memo, $total_require_bet);
                        $wallet->addMoney($user, $orderNumber, $money, $deal_type, $memo, $total_require_bet);
                        \Model\UserData::where('user_id', $user['id'])->increment('rebet_amount', $money);*/

                        $loggerTitle = '【返水通过】';

                        $model_active = [
                            'user_id'     => $user['id'],
                            'user_name'   => $user['name'],
                            'active_id'   => $activity->id,
                            'active_name' => $activity->name,
                            'status'      => 'undetermined',
                            'state'       => 'manual',
                            'memo'        => $activity->name,
                            'deposit_money'=> $userRebet['allBetAmount'],
                            'coupon_money'=> $money,
                            'withdraw_require'=> (int)$total_require_bet,
                            'apply_time'  => date('Y-m-d H:i:s'),
                            'batch_no'    => $batchNo
                        ];
                        $apply_id = \DB::table('active_apply')->insertGetId($model_active);
                        \DB::table('rebet_deduct')->insert([
                            'user_id' => $user['id'],
                            'batch_no' => $batchNo,
                            'deposit_amount' => $userRebet['deposit_user_amount'],
                            'coupon_amount' => $userRebet['coupon_user_amount'],
                            'type' => $dateType == 'week' ? '702' : '703',
                            'rebet' => $deductMoney,
                            'deduct_rebet' => max(0, $deductMoney - $money),
                        ]);

                        //插入日志数据
                        $rebetLog = [
                            'user_id'            => $user['id'],
                            'rebet_user_ranting' => $user['ranting'],
                            'game_id'            => $userRebet['game_id'] ?? 0,
                            'active_apply_id'    => $apply_id,
                            'direct_rate'        => $direct_list['rate'] ?? 0,
                            'desc'               => json_encode($userRebet['rebetLog']),
                        ];
                        \DB::table('rebet_log')->insert($rebetLog);
                        $this->logger->error('【返水】执行反水成功 --- 用户:' . $user['name'] . ",返水成功金额:" . $money);

                        $this->db->getConnection()->commit();
                    }
                } catch(\Exception $e) {
                    $this->logger->error("【返水异常】执行反水失败 ---. userId: {$user['id']}" . "  " . $date . "\r\n" . $e->getMessage());
                    $this->db->getConnection()->rollback();
                }

                /*if($money > 0) {
                    $title = $this->ci->get('settings')['website']['name'] ?? '';//标题
                    //发送回水信息
                    $content  = $this->lang->text("Dear user, you bought %s color amount of %s yuan yesterday. The system will return the return amount of %s to you. Please check and check. If you have any questions about the amount of return water, please consult online customer service in time.", [$activity->name, $userRebet['allBetAmount']/100, $money/100, $title]);
                    $exchange = 'user_message_send';
                    \Utils\MQServer::send($exchange, [
                        'user_id' => $user['id'],
                        'title'   => $this->lang->text("Backwater news"),
                        //'content' => vsprintf($content, [$temp['allBetAmount'] / 100, $temp['money'] / 100]),
                        'content' => $content,
                    ]);
                }*/
                unset($model_active,$rebetLog,$title,$content,$user);
            }else{
                $this->logger->error("游戏返水 userId:".$u->user_id . ' 无返水');
            }
        }
        $activeData=\DB::table('active_apply')
                       ->selectRaw('count(1) as cnt,sum(coupon_money) as back_amount')
                       ->where('batch_no',$batchNo)
                       ->first();
        $backData=array(
            'active_id'=>$activity->id,
            'batch_no'  =>$batchNo,
            'type'      =>$type,
            'batch_time'=>$batchTime,
            'back_cnt'  => $activeData->cnt,
            'back_amount'=>$activeData->back_amount ?? 0,
        );
        if($activeData->cnt ==0){
            $backData['status'] = 2;
            $backData['send_time']=date('Y-m-d H:i:s',time());
        }
        \DB::table('active_backwater')->insert($backData);
        $eTime=microtime(true) - $sTime;
        $this->logger->error('游戏返水结束时间'.$eTime);
        return 'success';
    }

    /**
     * 获取返水统计规则
     * loss：按当日亏损额回水
     * betting:按当日投注额回水
     * percentage：按流水百分比%
     * fixed：按固定金额
     *
     * @param $hallInfo
     * @param $hallId
     *
     * @return bool
     */
    public function getRebetActConfig($data, $hallId) {
        if(empty($data)) {
            return false;
        }
        $way = $data['type'];
        if(!in_array($way, $this->rebot_way)) {
            return false;
        }

        $userRebetCfg['type'][] = $way;
        foreach($data['data'] as $key => $value) {
            if('status' == $key) {
                if(!in_array($value, $this->rebot_way_type)) {
                    continue;
                }
                $userRebetCfg['type'][] = $value;
            } else {
                foreach($value as $config) {
                    $v            = explode(';', $config);
                    $v1           = explode(',', $v[0]);
                    $min_rebet[]  = $v1[0];
                    $set['range'] = $v1;

                    $set['value']           = $v[1];
                    $set['factor']          = $v[2];
                    $userRebetCfg['data'][] = $set;
                }
            }
        }
        sort($min_rebet);
        $userRebetCfg['rebet_min'] = $min_rebet[0];
        $this->logger->info("【返水】统计规则: hallId: {$hallId}" . "\r\n" . var_export($userRebetCfg, true));
        return $userRebetCfg;
    }

    /**
     * 活动返佣获取所有用户
     * @param $startTime
     * @param $endTime
     * @param string $dataType 数据类型周、月
     * @return array
     */
    public function getActivityOrderUserIds( $startTime, $endTime, $dataType='day', $userId = 0)
    {
        if($dataType == 'month'){
            $table= 'order_game_user_month';
        }elseif($dataType == 'week'){
            $table= 'order_game_user_week';
        }else{
            $table = 'order_game_user_day';
        }
        $result=\DB::connection('slave')->table($table)
                          ->where('date','>=',$startTime)
                          ->where('date','<=',$endTime)
                          ->where(function($query)use($userId){
                                if ($userId > 0) {
                                    $query->where('user_id', $userId);
                                }
                            })
                          ->distinct()
                          ->get(['user_id'])->toArray();

        return $result;
    }

    /**
     * 计算用户活动返佣金额
     * @param $user_id
     * @param $startTime
     * @param $endTime
     * @param $ruleData
     * @param string $dataType 数据类型周、月
     * @return array
     */
    public function getUserActivityBkgeMoney($user_id, $startTime, $endTime, $ruleData, $dataType='week', $runMode = 'rebet')
    {
        if($dataType=='month'){
            $table = 'order_game_user_day';
        }else{
            $table = 'order_game_user_week';
        }

        $menuList = \Model\GameMenu::getParentMenuIdType();
        $userMenus = [];
        foreach($ruleData as $rule){
            if(!isset($menuList[$rule['game_menu_id']])){
                continue;
            }
            $userMenus[$rule['game_menu_id']] = $menuList[$rule['game_menu_id']];
        }
        $games = array_values($userMenus);
        $list= (array)\DB::connection('slave')->table($table)
                    ->selectRaw('game,sum(bet) as bet,sum(profit) as profit')
                    ->where('date','>=',$startTime)
                    ->where('date','<=',$endTime)
                    ->where('user_id','=',$user_id)
                    ->whereIn('game', $games)
                    ->groupBy('game')->get()->toArray();
        $userData = [];
        foreach($list as $val){
            $userData[$val->game] = (array)$val;
        }
        unset($list,$val);
        $userAllRebet = ['money' => 0, 'total_require_bet' => 0, 'dayWin' => 0, 'allBetAmount' => 0, 'rate'=>0, 'deduct_money' => 0];
        if(empty($userData)){
            return $userAllRebet;
        }

        //计算返水金额
        $sysRebetConfig = \Logic\Set\SystemConfig::getModuleSystemConfig('rebet_config');
        $rebetZeroSwitch = boolval($sysRebetConfig[$dataType.'_gt_zero'] ?? false);
        $rptAmount['deposit_user_amount'] = $this->getDepositUserAmount($user_id, $dataType, 'deposit_user_amount', $rebetZeroSwitch);
        $rptAmount['coupon_user_amount'] = $this->getDepositUserAmount($user_id, $dataType, 'coupon_user_amount', $rebetZeroSwitch);
        foreach($ruleData as $rule){
            if(!isset($userMenus[$rule['game_menu_id']]) || !isset($userData[$userMenus[$rule['game_menu_id']]])){
                continue;
            }
            //今日盈亏
            $dayWin = $userData[$userMenus[$rule['game_menu_id']]]['profit'];
            //今日投注金额
            $allBetAmount = $userData[$userMenus[$rule['game_menu_id']]]['bet'];
            if(bccomp($allBetAmount, 0) <=0){
                continue;
            }
            $rebetConfigs                  = $this->getRebetActConfig($rule, $rule['game_menu_id']);
            $rebetConfigs['rebet_multiple'] = intval($sysRebetConfig[$dataType] ?? 0);
            $rebetConfigs['rebet_gt_zero_switch'] = $rebetZeroSwitch;
            $rebetConfigs['rebet_ceiling'] = '';

            //玩家返回金额
            $deductRebet = $this->userRebetWeekMoney($rebetConfigs, $dayWin, $allBetAmount, null, $runMode);
            $userRebet = $this->userRebetWeekMoney($rebetConfigs, $dayWin, $allBetAmount, $rptAmount, $runMode);
            if (!empty($userRebet['tmpAllBetAmount']) && $runMode == 'rebet') {
                $allBetAmount = $userRebet['tmpAllBetAmount'];
            }
            $this->logger->debug("用户id:" . $user_id . ",游戏ID：".$rule['game_menu_id'].",今日盈亏金额:" . $dayWin . ",今日投注金额:" . $allBetAmount. ",返水金额：" . json_encode($userRebet, true));
            if($userRebet){
                $userAllRebet['money'] = bcadd($userAllRebet['money'], $userRebet['money'], 2);
                $userAllRebet['deduct_money'] = bcadd($userAllRebet['deduct_money'], $deductRebet['money'], 2);
                $userAllRebet['deposit_user_amount'] = $rptAmount['deposit_user_amount'];
                $userAllRebet['coupon_user_amount'] = $rptAmount['coupon_user_amount'];
                $userAllRebet['total_require_bet'] = bcadd($userAllRebet['total_require_bet'], $userRebet['total_require_bet'], 2);
                $userAllRebet['dayWin'] = bcadd($userAllRebet['dayWin'], $dayWin, 0);
                $userAllRebet['allBetAmount'] = bcadd($userAllRebet['allBetAmount'], $allBetAmount, 0);
                $userAllRebet['rate'] = $userRebet['rate'] ?? 0;
                $userAllRebet['rebetLog'][] = [
                    'game_id'         => $rule['game_menu_id'],
                    'userId'          => $user_id,
                    'dayWin'          => $dayWin,
                    'allBetAmount'    => $allBetAmount,
                    'require_bet'     => $userRebet['total_require_bet'],
                    'money'           => bcmul($userRebet['money'],100),
                    'rebetConfigs'    => $rebetConfigs,
                    'rate'            => $userRebet['rate'] ?? 0,
                ];
            }
            unset($rebetConfigs,$userRebet, $dayWin, $allBetAmount);
        }
        return $userAllRebet;
    }

    public function middleOrder($date=null){
        $date=!empty($date) ? $date : date('Y-m-d',strtotime('-1 day'));
        $key=\Logic\Define\CacheKey::$perfix['middleOrder'].$date;
        $ret=$this->redis->get($key);
        if($ret != null){
            return (int)$ret;
        }

        $website_name = $this->ci->get('settings')['website']['name'];
        $orderBet=\DB::connection('slave')
            ->table('orders')
            ->where('order_time','>=',$date.' 00:00:00')
            ->where('order_time','<=',$date.' 23:59:59')
            ->sum('bet');

        $middleBet=\DB::connection('slave')->table('order_game_user_middle')
            ->where('date',$date)
            ->sum('bet');
        //于小中间表或者大于10W 告警
        $diff = bcsub($orderBet, $middleBet);
        $content = "【".$website_name."】middleOrder数据不一致：".PHP_EOL;
        $content .= '相差：' . $diff . PHP_EOL;
        $content .= 'orders：' . $orderBet . PHP_EOL;
        $content .= 'order_game_user_middle：' . $middleBet . PHP_EOL;
        $content .= '警告时间：' . date('Y-m-d H:i:s');
        Telegram::sendMiddleOrdersMsg($content);
        if($orderBet < $middleBet || bcmul($orderBet/$middleBet,100) > 110){
            return false;
        }else{
            $ret=1;
        }

        $this->redis->setex($key,strtotime('23:59:59') - time(),$ret);
        return $ret;
    }


    /**
     * 游戏分类活动-个人用户返佣汇总
     * @param $user_id
     * @param $startTime
     * @param $endTime
     * @param $ruleGameMenu
     * @param $condi
     * @param string $dataType
     * @return int|mixed
     */
    public function getGameActivityUserMoney($user_id,$startTime,$endTime,$ruleGameMenu,$condi, $dataType = 'day')
    {
        if($dataType == 'month'){
            $table= 'order_game_user_month';
        }elseif($dataType == 'week'){
            $table= 'order_game_user_week';
        }else{
            $table = 'order_game_user_day';
        }

        if(in_array($condi, [1, 3])){
            $unit = 'bet';
        }else{
            $unit='lose';
        }
        $query = \DB::connection('slave')->table($table)
                 ->selectRaw('SUM(bet) as bet_amount,SUM(profit) as lose_amount')
                 ->where('date','>=',$startTime)
                 ->where('date','<=',$endTime)
                 ->where('user_id','=',$user_id);
        //未设置分类，默认为全部
        if(!empty($ruleGameMenu)) {
            $query->whereIn('game', explode(',',$ruleGameMenu));
        }
        $userData = (array)$query->first();

        $user_counts= $userData ? $userData[$unit.'_amount'] : 0;
        $this->logger->debug('用户id:'.$user_id.',总金额:'.$user_counts);
        unset($query, $userData);
        //兼容
        if(empty($user_counts)){
            $user_counts = 0;
        }
        return $user_counts;
    }

    /**
    *
    *游戏分类统计发放
    *
    **/
    public function sendGameTypeDataTstat($dates=null)
    {

        $time = date("Y-m-d H:i:s");

        $date=date('Y-m-d');
        $lock_key = $this->redis->setnx(\Logic\Define\CacheKey::$perfix['gameActivity'] . $date, 1);
        $this->redis->expire(\Logic\Define\CacheKey::$perfix['gameActivity'] . $date, strtotime('23:59:59') - time());
        if(!$lock_key) {
            $this->logger->debug('数据已跑 ' . $date);
            return false;
        }

        //游戏分类活动
        $act_list = \DB::table('active as a')
                ->join('active_rule as r', 'a.id', '=','r.active_id')
                ->where('a.begin_time', '<=', $time)
                ->where('a.end_time', '>=', $time)
                ->where('a.status', 'enabled')
                ->where('r.template_id', 10)
                ->get(['a.id', 'a.name','a.begin_time','a.end_time', 'r.template_id','r.rule','r.luckydraw_condition','r.issue_mode', 'r.send_type', 'r.send_max','r.give_condition','r.give_date'])
                ->toArray();
        if(empty($act_list)){
            $this->logger->info('暂无活动');
            return true;
        }
        $this->logger->info("游戏分类：发放开始");
        $wallet = new Wallet($this->ci);
        $dataType='day'; //数据类型定义用那个表
        foreach ($act_list ?? [] as $value)
        {
            $value = (array)$value;
            switch($value['give_condition']){

                case 2:
                    //单日累计
                    $startTime=!empty($dates) ?date('Y-m-d 00:00:00',strtotime($dates)) :date('Y-m-d 00:00:00',strtotime('-1 day'));
                    $endTime=!empty($dates) ? date('Y-m-d 23:59:59',strtotime($dates)) :date('Y-m-d 23:59:59',strtotime('-1 day'));

                    $applyStartTime=!empty($dates) ?date('Y-m-d 00:00:00',strtotime("$dates +1 day")) :date('Y-m-d 00:00:00',time());
                    $applyEndTime=!empty($dates) ? date('Y-m-d 23:59:59',strtotime("$dates +1 day")) : date('Y-m-d 23:59:59',time());
                    $type=1;
                    $batchTime=date('Y-m-d',strtotime($startTime));
                    break;
                case 3:
                    $dataType = 'week';
                    //周累计
                    if(date('w') == 1){
                        $startTime = date("Y-m-d 00:00:00", strtotime('last monday'));
                    }else{
                        $startTime = date("Y-m-d 00:00:00", strtotime('-1 week last monday'));
                    }

                    $endTime   = date('Y-m-d 23:59:59', strtotime("-1 sunday",time()));
                    $dateStr='周';
                    $applyStartTime=date('Y-m-d 00:00:00', (time() - ((date('w') == 0  ? 7 : date('w')) - 1) * 24 * 3600));
                    $applyEndTime=date('Y-m-d 23:59:59', (time() + (7 - (date('w') == 0 ?  7 : date('w'))) * 24 * 3600));
                    $type=2;
                    $batchTime=date('Y-m-d',strtotime($startTime)).'~'.date('Y-m-d',strtotime($endTime));
                    break;
                case  4:
                    $dataType = 'month';
                    //月累计
                    $startTime = date("Y-m-01 00:00:00", strtotime(date('Y-m-01') . " - 1 month"));
                    $endTime   = date('Y-m-d 23:59:59', strtotime("$startTime +1 month -1 day"));
                    $dateStr='月';

                    $applyStartTime=date('Y-m-d 00:00:00', strtotime(date('Y-m', time()) . '-01 00:00:00'));
                    $applyEndTime=date('Y-m-d 23:59:59', strtotime(date('Y-m', time()) . '-' . date('t', time()) . ' 00:00:00'));
                    $type=3;
                    $batchTime=date('Y-m',strtotime($startTime));
                    break;
                case  5:
                    //自定义
                    $time=explode(',',$value['give_date']);
                    $startTime=date('Y-m-d 00:00:00',strtotime($time[0]));
                    $endTime=date('Y-m-d 23:59:59',strtotime($time[1]));

                    $applyStartTime=date('Y-m-d 00:00:00',time());
                    $applyEndTime=date('Y-m-d 23:59:59',time());
                    break;
                default:
                    $startTime = $applyStartTime=date('Y-m-d 00:00:00',time());
                    $endTime  = $applyEndTime =date('Y-m-d 23:59:59',time());
                    break;
            }
            
            //判断时间
            if(time() <= strtotime($endTime)){
                $this->logger->error("派奖时间未到达，active_id:".$value['id']);
                continue;
            }
            //日累计判断昨天数据是否统一
            if($value['give_condition'] == 2){
                if(!$this->middleOrder()){
                    $this->logger->error('日累计活动数据不统一');
                    continue;
                }
            }
            //今天已统计过
            $active_counts = \DB::table('active_apply')
                                ->where('active_id','=',$value['id']);
            if(in_array($value['give_condition'], [2,3,4])){
                $active_counts = $active_counts->where('apply_time','>=',$applyStartTime)
                    ->where('apply_time','<=',$applyEndTime);
            }
            $active_counts = $active_counts->count();
            if($active_counts > 0)
            {
                $this->logger->info("重复执行发奖励，active_id:".$value['id']);
                continue;
            }

            $rule_value = json_decode($value['rule'], true) ?? [];
            $condition_value = json_decode($value['luckydraw_condition'], true) ?? [];

            //找出范围规则
            $rule_scope = [];
            $rule = explode(';', $rule_value['rule']);
            foreach ($rule ?? [] as $v){
                $rule_scope[] = explode(',', $v);
            }

            if(empty($rule_scope)){
                $this->logger->error("发放规则为空，不执行，active_id:".$value['id']);
                continue;
            }
            //找出该活动符合条件的用户ID
            /*if($rule_value['game_id'] == '')
            {*/
                $num=0;
                $last                   = array_pop($rule_scope);
                $orderStartTime=date('Y-m-d',strtotime($startTime));
                $orderEndTime=date('Y-m-d',strtotime($endTime));
                $userList=$this->getActivityOrderUserIds($orderStartTime,$orderEndTime, $dataType);
                if(empty($userList)){
                    $this->logger->error("没有满足条件的用户，active_id:".$value['id']);
                    continue;
                }
//                $batchNo=time();
                foreach($userList as $u){
                    $user_id=$u->user_id;
                    $this->logger->debug("游戏返水 userId:".$u->user_id);
                    $user_counts=$this->getGameActivityUserMoney($u->user_id,$orderStartTime,$orderEndTime,$rule_value['game_menu_name'],$condition_value['condi'], $dataType);

                    //盈亏返水
                    if(!in_array($condition_value['condi'], [1, 3])){
                        //计算盈亏金额 用户赢钱就跳过
                        if($user_counts >= 0) continue;
                        $user_counts = -1 * $user_counts;
                    }

                    $basePrize              = 0;

                    $send_money = 0;
                    //循环范围，奖励条件
                    foreach ($rule_scope as $scope_val)
                    {

                        if($condition_value['condi']  == 3){
                            if($scope_val[0] == $user_counts){
                                $basePrize=$scope_val[2];
                                $dmlPrize =$scope_val[3];
                            }
                        }else{
                            if($scope_val[0] < $user_counts && $user_counts <= $scope_val[1])
                            {
                                $basePrize=$scope_val[2];
                                $dmlPrize =$scope_val[3];
                            }
                        }
                    }

                    if ($last[0] < $user_counts) {
                        $basePrize = $last[2];
                        $dmlPrize =$last[3];
                    }
                    $dmlPrize      = !empty($dmlPrize) ? $dmlPrize : 100;

                    if($condition_value['money'] == 3)
                    {
                        //固定
                        $send_money = bcmul($basePrize,100);
                    }else{
                        //百分比:积累金额 * 百分比 / 100;
                        $send_money = bcdiv($user_counts * $basePrize , 100, 2);
                    }

                    //无奖励
                    if($send_money == 0){continue;}

                    //超过最大奖励金额
                    if($send_money > $value['send_max']){$send_money = $value['send_max'];}

                    $user = \Model\User::where('id',$user_id)->select(['wallet_id', 'id', 'name'])->first();
                    //直接开始发放
                    try{
                        $this->db->getConnection()->beginTransaction();
                        //打码量
                        $dml=bcmul($send_money,bcdiv($dmlPrize,100,4));

                        // 锁定钱包
                        \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
                        $rand        = rand(10000, 99999);
                        $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);
                        $memo = "游戏活动,用户:" . $user['name'] . ",金额:" . ($send_money /100).',打码量:'.$dml /100;
                        $wallet->addMoney($user, $orderNumber, $send_money, 4, $memo,$dml);
                        \Model\UserData::where('user_id', $user_id)->increment('rebet_amount', $send_money);


                        $model_active = [
                            'user_id'     => $user_id,
                            'user_name'   => $user['name'],
                            'active_id'   => $value['id'],
                            'active_name' => $value['name'],
                            'status'      => 'pass',
                            'state'       => $value['issue_mode'],
                            'memo'        => $value['name'],
                            'deposit_money'=> $user_counts,
                            'coupon_money'=> $send_money,
                            'withdraw_require'=> $dml,
                            'apply_time'  => !empty($dates) ?date('Y-m-d',strtotime("$dates +1 day")).' '.date('H:i:s') :date('Y-m-d H:i:s'),
//                            'batch_no'    => $batchNo
                        ];
                        \DB::table('active_apply')->insert($model_active);

                        $this->db->getConnection()->commit();
                        $num ++;
                        $this->logger->info('游戏活动'.$value['id'].':用户'.$user['name'].'派送金额:'.($send_money /100).'发放完毕');

                    }catch(\Exception $e){
                        $this->logger->error('游戏分类统计发放：异常，active_id：' . $value['id'] . "userId: {$user_id}" . "  " . $time . "\r\n" . $e->getMessage());
                        $this->db->getConnection()->rollBack();
                    }
                }

                //新版领取
                /*$activeData=\DB::table('active_apply')
                               ->selectRaw('count(1) as cnt,sum(coupon_money) as back_amount')
                               ->where('batch_no',$batchNo)
                                ->first();
                $backData=array(
                    'batch_no'  =>$batchNo,
                    'type'      =>$type,
                    'batch_time'=>$batchTime,
                    'back_cnt'  => $activeData->cnt,
                    'back_amount'=>$activeData->back_amount ?? 0,
                );
                if($activeData->cnt ==0){
                    $backData['status'] = 2;
                    $backData['send_time']=date('Y-m-d H:i:s',time());
                }
                \DB::table('active_backwater')->insert($backData);*/

                $this->logger->info("完成，active_id:".$value['id'].'共'.count($userList).'名用户,成功派送:'.$num.'名用户');
            /*}else{

                //找出渠道表中，对应游戏ID的游戏数据订单
                $game_id_arr = explode(',', $rule_value['game_id']);
                $num=0;
                foreach ($game_id_arr as $game_value)
                {
                    //找出游戏渠道
                    $game_info = \DB::table('game_3th AS g')
                                    ->join('game_menu AS gm', 'g.game_id', '=', 'gm.id')
                                    ->where('g.id', $game_value)
                                    ->selectRaw("g.id, g.kind_id, g.game_name, gm.type")
                                    ->first();

                    $game_info = (array)$game_info;
                    $type_super_name = strtoupper($game_info['type']);

                    //找出游戏订单数据
                    $game_class = "\Logic\GameApi\Order\\" . $type_super_name;
                    if(!class_exists($game_class)){
                        $this->logger->error('游戏:'.$game_class.'类不存在!');
                        continue;
                    }
                    $game_obj = new $game_class($this->ci);


                    $ck_game = $game_info['kind_id'];
                    //每个渠道下，查找对应的游戏字段都不同
                    if(in_array($type_super_name, ['JDB', 'JDBBY','JDBJJ','JDBQP','KMQM', 'TF']))
                    {
                        $ck_game = $game_info['game_name'];
                    }
                    if(!method_exists($game_obj,'queryUserSumOrder')){
                        $this->logger->error('游戏类:'.$game_class.',游戏:'.$game_info['game_name'].'找不到该游戏!');
                        continue;
                    }

                    $this->logger->info("活动id:".$value['id']."时间区间:".$startTime.'/'.$endTime);

                    //找出昨日该游戏用户下单数,统计发放奖励
                    $user_list = $game_obj->queryUserSumOrder($startTime, $endTime, $ck_game);
                    $this->logger->info('全部用户集合数据:'.json_encode($user_list));
                    $user_list = (array)$user_list;

                    if(empty($user_list)){
                        $this->logger->info('游戏:'.$game_info['game_name'].'下没有游戏用户');
                        continue;
                    }
                    $last                   = array_pop($rule_scope);
                    //操作发放奖励
                    foreach ($user_list as $k => $user_info)
                    {

                        $unit = 'win_loss';//盈亏
                        if(in_array($condition_value['condi'], [1, 3])){$unit = 'bet';}//流水

                        $user_info=(array)$user_info;
                        $user_id = $user_info['user_id'];
                        $user_counts = $user_info[$unit];

                        if($user_counts < 0){
                            $user_counts = -1 * $user_counts;
                        }

                        $basePrize = 0;
                        $send_money = 0;
                        //循环范围，奖励条件
                        foreach ($rule_scope as $scope_val)
                        {

                            if($condition_value['condi']  == 3){
                                if($scope_val[0] == $user_counts){
                                    $basePrize=$scope_val[2];
                                }
                            }else{
                                if($scope_val[0] < $user_counts && $user_counts <= $scope_val[1])
                                {
                                    $basePrize=$scope_val[2];
                                }
                            }

                        }
                        if ($last[0] < $user_counts) {
                            $basePrize = $last[2];
                        }
                        if($condition_value['money'] == 3)
                        {
                            //固定
                            $send_money = bcmul($basePrize,100);
                        }else{
                            //百分比:积累金额 * 百分比 / 100;
                            $send_money = bcdiv($user_counts * $basePrize , 100, 2);
                        }
                        //无奖励
                        if($send_money == 0){continue;}

                        //超过最大奖励金额
                        if($send_money > $value['send_max']){$send_money = $value['send_max'];}

                        $user = \Model\User::where('id',$user_id)->first();
                        //直接开始发放
                        try{
                            $this->db->getConnection()->beginTransaction();

                            // 锁定钱包
                            \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
                            $rand        = rand(10000, 99999);
                            $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);

                            $memo = "游戏活动,用户:" . $user['name'] . ",金额:" . $send_money /100;
                            $wallet->addMoney($user, $orderNumber, $send_money, 4, $memo);
                            \Model\UserData::where('user_id', $user_id)->increment('rebet_amount', $send_money);

                            $model_active = [
                                'user_id'     => $user_id,
                                'user_name'   => $user['name'],
                                'active_id'   => $value['id'],
                                'active_name' => $value['name'],
                                'status'      => 'pass',
                                'state'       => $value['issue_mode'],
                                'memo'        => $value['name'],
                                'coupon_money'=> $send_money,
                                'apply_time'  => date('Y-m-d H:i:s'),
                            ];
                            \DB::table('active_apply')->insert($model_active);

                            $this->db->getConnection()->commit();
                            $num ++;
                            $this->logger->info('游戏活动'.$value['id'].':用户'.$user['name'].'派送金额:'.($send_money /100).'发放完毕');

                        }catch(\Exception $e){
                            $this->logger->error('游戏分类统计发放：异常，active_id：' . $value['id'] . "userId: {$user_id}" . "  " . $time . "\r\n" . $e->getMessage());
                            $this->db->getConnection()->rollBack();
                        }
                    }

                }
                $this->logger->info("完成，active_id:".$value['id'].',成功派送:'.$num.'名用户');

            }*/
        }
        return true;
    }

    /**
     *
     *推广活动统计发放
     *
     **/
    public function sendSpreadTypeDataTstat()
    {

        $time = date("Y-m-d H:i:s");

        $date=date('Y-m-d');
        $lock_key = $this->redis->setnx(\Logic\Define\CacheKey::$perfix['spreadActivity'] . $date, 1);
        $this->redis->expire(\Logic\Define\CacheKey::$perfix['spreadActivity'] . $date, strtotime('23:59:59') - time());
        if(!$lock_key) {
            $this->logger->debug('数据已跑 ' . $date);
            return false;
        }

        //游戏分类活动
        $act_list = \DB::table('active as a')
            ->join('active_rule as r', 'a.id', '=','r.active_id')
            ->where('a.begin_time', '<=', $time)
            ->where('a.end_time', '>=', $time)
            ->where('a.status', 'enabled')
            ->where('r.template_id', 13)
            ->get(['a.id', 'a.name','a.begin_time','a.end_time', 'r.template_id','r.rule','r.issue_mode', 'r.send_type', 'r.send_max','r.send_bet_max','r.give_condition','r.give_date'])
            ->toArray();
        if(empty($act_list)){
            $this->logger->info('暂无活动');
            return true;
        }
        $this->logger->info("游戏分类：发放开始");
        $wallet = new Wallet($this->ci);
        foreach ($act_list ?? [] as $value)
        {
            $value = (array)$value;
            $dataType = 'day';
            switch($value['give_condition']){

                case 2:
                    //单日累计
                    $startTime=date('Y-m-d 00:00:00',strtotime('-1 day'));
                    $endTime=date('Y-m-d 23:59:59',strtotime('-1 day'));

                    $applyStartTime=date('Y-m-d 00:00:00',time());
                    $applyEndTime=date('Y-m-d 23:59:59',time());
                    break;
                case 3:
                    $dataType = 'week';
                    //周累计
                    if(date('w') == 1){
                        $startTime = date("Y-m-d 00:00:00", strtotime('last monday'));
                    }else{
                        $startTime = date("Y-m-d 00:00:00", strtotime('-1 week last monday'));
                    }

                    $endTime   = date('Y-m-d 23:59:59', strtotime("-1 sunday",time()));
                    $dateStr='周';
                    $applyStartTime=date('Y-m-d 00:00:00', (time() - ((date('w') == 0  ? 7 : date('w')) - 1) * 24 * 3600));
                    $applyEndTime=date('Y-m-d 23:59:59', (time() + (7 - (date('w') == 0 ?  7 : date('w'))) * 24 * 3600));
                    break;
                case  4:
                    $dataType = 'month';
                    //月累计
                    $startTime = date("Y-m-01 00:00:00", strtotime(date('Y-m-01') . " - 1 month"));
                    $endTime   = date('Y-m-d 23:59:59', strtotime("$startTime +1 month -1 day"));
                    $dateStr='月';

                    $applyStartTime=date('Y-m-d 00:00:00', strtotime(date('Y-m', time()) . '-01 00:00:00'));
                    $applyEndTime=date('Y-m-d 23:59:59', strtotime(date('Y-m', time()) . '-' . date('t', time()) . ' 00:00:00'));
                    break;
                case  5:
                    //自定义
                    $time=explode(',',$value['give_date']);
                    $startTime=date('Y-m-d 00:00:00',strtotime($time[0]));
                    $endTime=date('Y-m-d 23:59:59',strtotime($time[1]));

                    $applyStartTime=date('Y-m-d 00:00:00',time());
                    $applyEndTime=date('Y-m-d 23:59:59',time());
                    break;
                default:
                    $startTime = $applyStartTime=date('Y-m-d 00:00:00',time());
                    $endTime  = $applyEndTime =date('Y-m-d 23:59:59',time());
                    break;
            }
            //判断时间
            if(time() <= strtotime($endTime)){
                $this->logger->info("派奖时间未到达，active_id:".$value['id']);
                continue;
            }

            //今天已统计过
            $active_counts = \DB::table('active_apply')
                ->where('active_id','=',$value['id']);
            if(in_array($value['give_condition'], [2,3,4])){
                $active_counts = $active_counts->where('apply_time','>=',$applyStartTime)
                    ->where('apply_time','<=',$applyEndTime);
            }
            $active_counts = $active_counts->count();
            if($active_counts > 0)
            {
                $this->logger->info("重复执行发奖励，active_id:".$value['id']);
                continue;
            }

            $rule_value = json_decode($value['rule'], true) ?? [];

            //找出范围规则
            $rule_scope = [];
            $rule = $rule_value['rule'];
            foreach ($rule ?? [] as $k => $v){
                $rule_scope[$k] = explode(',', $v);
            }
            if(empty($rule_scope)){
                $this->logger->info("发放规则为空，不执行，active_id:".$value['id']);
                continue;
            }
            //找出该活动符合条件的用户ID
            if(!empty($rule_value['game_menu_name']))
            {
                //用户在渠道下的订单总量
                $this->logger->info("活动id:".$value['id']."时间区间:".$startTime.'/'.$endTime);
                $orderStartTime=date('Y-m-d',strtotime($startTime));
                $orderEndTime=date('Y-m-d',strtotime($endTime));
                $userList=$this->getActivityOrderUserIds($orderStartTime,$orderEndTime, $dataType);
                if(empty($userList)){
                    $this->logger->info("没有满足条件的用户1，active_id:".$value['id']);
                    continue;
                }

                $bet_user = [];
                foreach($userList as $u){
                    $user_counts=$this->getGameActivityUserMoney($u->user_id,$orderStartTime,$orderEndTime,$rule_value['game_menu_name'],1, $dataType);
                    if($user_counts <= 0 || $user_counts <= $rule_scope['bet_amount'][0]){
                        continue;
                    }
                    $bet_user[] = $u->user_id;
                }
                unset($bet_user[0]);
                if(empty($bet_user)){
                    $this->logger->info("没有满足条件的用户2，active_id:".$value['id']);
                    continue;
                }
                //获取充值总量
                $recharge_arr = \DB::table('funds_deposit')
                    ->where('created', '>=', $startTime)
                    ->where('created', '<=', $endTime)
                    ->where('status', '=', 'paid')
                    ->groupBy('user_id')->selectRaw("user_id, sum(money) as money")->get()->toArray();
                $reg_user = [];
                foreach ($recharge_arr as $k => $reg_info){
                    $reg_info=(array)$reg_info;
                    if($rule_scope['recharge'][0] > 0 && $reg_info['money'] <= $rule_scope['recharge'][0]){
                        continue;
                    }
                    $reg_user[] = $reg_info['user_id'];
                }
                $user_arr = array_intersect($bet_user,$reg_user);

                //获取满足条件用户的上级代理
                //避免数据过大，循环获取
                $agent_user = [];
                $count_user = [];
                foreach($user_arr as $user_k => $user_v){
                    $count_user[] = $user_v;
                    if(count($count_user) >= 2000){
                        //获取规定时间内注册的用户
                        $count_user2 = [];
                        $info_arr = \DB::table('user')
                            ->where('created', '>=', $startTime)
                            ->where('created', '<=', $endTime)
                            ->whereIn('id', $count_user)
                            ->where('state',1)
                            ->select('id')
                            ->get()->toArray();
                        if(!empty($info_arr)){
                            foreach($info_arr as $count_k => $count_v){
                                $count_v = (array)$count_v;
                                $count_user2[] = $count_v['id'];
                            }
                        }
                        if(count($count_user2) > 0){
                            $agent_arr = \DB::table('user_agent')->whereIn('user_id',$count_user2)
                                ->where('uid_agent','>',0)
                                ->select('user_id','uid_agent')
                                ->get()->toArray();
                            if(!empty($agent_arr)){
                                foreach($agent_arr as $agent_k => $agent_v){
                                    $agent_v = (array)$agent_v;
                                    $agent_user[$agent_v['user_id']] = $agent_v['uid_agent'];
                                }
                            }
                        }
                        $count_user = [];
                    }
                }
                if(count($count_user) > 0){
                    $count_user2 = [];
                    $info_arr = \DB::table('user')
                        ->where('created', '>=', $startTime)
                        ->where('created', '<=', $endTime)
                        ->whereIn('id', $count_user)
                        ->where('state',1)
                        ->select('id')
                        ->get()->toArray();
                    if(!empty($info_arr)){
                        foreach($info_arr as $count_k => $count_v){
                            $count_v = (array)$count_v;
                            $count_user2[] = $count_v['id'];
                        }
                    }
                    $agent_arr = \DB::table('user_agent')->whereIn('user_id',$count_user2)
                        ->where('uid_agent','>',0)
                        ->select('user_id','uid_agent')
                        ->get()->toArray();
                    if(!empty($agent_arr)){
                        foreach($agent_arr as $agent_k => $agent_v){
                            $agent_v = (array)$agent_v;
                            $agent_user[$agent_v['user_id']] = $agent_v['uid_agent'];
                        }
                    }
                }

                if(empty($agent_user)){
                    $this->logger->info("没有满足条件的用户3，active_id:".$value['id']);
                    continue;
                }
                $this->logger->info('全部下级用户集合数据:'.json_encode($user_arr).',全部上级用户集合数据:'.json_encode($agent_user));
                $num=0;

                //获取参与列表
                $active_apply_arr = \DB::table('active_apply')->where('active_id',$value['id'])
                    ->where('apply_time', '>=', $applyStartTime)
                    ->where('apply_time', '<=', $applyEndTime)
                    ->select('coupon_money','user_id','withdraw_require')
                    ->get()->toArray();

                $send_total_user = [];//用户累计派发金额
                foreach ($agent_user as $agent_k => $agent_info)
                {
                    $user_id = $agent_info;
                    $send_money = $rule_value['send_prize'];
                    $send_bet = $rule_value['send_bet'];
                    //获取该用户累计奖励和打码量
                    if(isset($send_total_user[$user_id])){
                        $count_money = $send_total_user[$user_id]['count_money'];
                        $count_bet = $send_total_user[$user_id]['count_bet'];
                    }else{
                        $count_money = 0;
                        $count_bet = 0;
                        if(!empty($active_apply_arr)){
                            foreach($active_apply_arr as $apply_k => $apply_v){
                                if($user_id == $apply_v->user_id){
                                    $count_money = bcadd($count_money,$apply_v->coupon_money);
                                    $count_bet = bcadd($count_bet,$apply_v->withdraw_require);
                                }
                            }
                        }
                        $send_total_user[$user_id] = [
                            'count_money' => $count_money,
                            'count_bet'   => $count_bet,
                        ];
                    }

                    //超过最大累计金额
                    if($value['send_max'] > 0 && bcadd($count_money,$send_money) > $value['send_max']){
                        $send_money = bcsub($value['send_max'], $count_money);
                    }
                    if($value['send_bet_max'] > 0 && bcadd($count_bet,$send_bet) > $value['send_bet_max']){
                        $send_bet = bcsub($value['send_bet_max'], $count_bet);
                    }

                    //无奖励
                    if($send_money == 0 && $send_bet == 0){continue;}

                    $send_total_user[$user_id]['count_money'] = bcadd($count_money,$send_money);
                    $send_total_user[$user_id]['count_bet'] = bcadd($count_bet,$send_bet);
                    $user = \Model\User::where('id',$user_id)->first();
                    //直接开始发放
                    try{
                        $this->db->getConnection()->beginTransaction();

                        // 锁定钱包
                        if($value['issue_mode'] == 'auto'){
                            \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
                            $rand        = rand(10000, 99999);
                            $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);

                            $memo = "推广活动,用户:" . $user['name'] . ",下级用户UID:".$agent_k.",金额:" . ($send_money /100) .",打码量:". ($send_bet /100);
                            $deal_type = \Model\FundsDealLog::TYPE_ACTIVITY;
                            $wallet->addMoney($user, $orderNumber, $send_money, $deal_type, $memo, $send_bet, true);
                            \Model\UserData::where('user_id', $user_id)->increment('rebet_amount', $send_money);
                        }

                        $model_active = [
                            'user_id'     => $user_id,
                            'user_name'   => $user['name'],
                            'active_id'   => $value['id'],
                            'active_name' => $value['name'],
                            'status'      => 'pass',
                            'state'       => $value['issue_mode'],
                            'memo'        => $value['name'],
                            'coupon_money'=> $send_money,
                            'withdraw_require'=> $send_bet,
                            'apply_time'  => date('Y-m-d H:i:s'),
                        ];
                        \DB::table('active_apply')->insert($model_active);

                        $this->db->getConnection()->commit();
                        $num ++;
                        $this->logger->info('推广活动'.$value['id'].':用户'.$user['name'].'派送金额:'.($send_money /100).",打码量:". ($send_bet /100).'发放完毕');

                    }catch(\Exception $e){
                        $this->logger->error('推广活动统计发放：异常，active_id：' . $value['id'] . "userId: {$user_id}" . "  " . $time . "\r\n" . $e->getMessage());
                        $this->db->getConnection()->rollBack();
                    }
                }

                $this->logger->info("完成，active_id:".$value['id'].'共'.count($agent_user).'名用户,成功派送:'.$num.'名用户');
            }
        }
        return true;
    }

    /**
     *
     *更新客服后台用户的充值时间
     *
     **/
    public function updateKefuTime()
    {
        //只修复7天内的数据
        $time = date('Y-m-d H:i:s', (time() - 7*24*60*60));
        //获取未首充的用户
        $users = \DB::connection("slave")->table('kefu_telecom_item')
            ->where('created', '>=', $time)
            ->where('recharge_amount', '<=', 0)
            ->get(['pid','id','user_id'])->toArray();
        //无更新用户
        if(empty($users)){
            return true;
        }

        $userids = array_column($users, 'user_id');
        //获取充值信息
        $rechargeList = \DB::connection("slave")->table("funds_deposit")
            ->whereIn('user_id', $userids)
            ->where('status', 'paid')
            ->whereRaw(\DB::raw("FIND_IN_SET('new',state)"))
            ->where('money', '>', 0)
            ->select(['user_id','money','recharge_time'])
            ->get()->toArray();
        //无充值更新
        if(empty($rechargeList)){
            return true;
        }

        $telecom_ids = [];
        foreach($users as $val){
            foreach($rechargeList as $recharge){
                if($val->user_id == $recharge->user_id){
                    \DB::table('kefu_telecom_item')->where('id', $val->id)->update([
                        'recharge_time' => $recharge->recharge_time,
                        'recharge_amount' => $recharge->money,
                    ]);
                    break;
                }
            }

            $telecom_ids[] = $val->pid;
        }

        //重新统计
        if(!empty($telecom_ids)){
            $telecom_ids = array_unique($telecom_ids);
            foreach($telecom_ids as $pid){
                //统计客服数量
                $recharge_num = \DB::table("kefu_telecom_item")->where('pid', $pid)->where('recharge_amount', '>', 0)->count();
               if($recharge_num == 0){
                   $recharge_amount = 0;
                   $recharge_mean = 0;
               }else{
                   $recharge_amount = \DB::table("kefu_telecom_item")->where('pid', $pid)->where('recharge_amount', '>', 0)->sum('recharge_amount');
                   $recharge_mean = round($recharge_amount/$recharge_num);
               }

                $telecom_update = [
                    'recharge_num'    => $recharge_num,
                    'recharge_amount' => $recharge_amount,
                    'recharge_mean'   => $recharge_mean,
                ];
                \DB::table("kefu_telecom")->where('id', $pid)->update($telecom_update);
            }
        }

        return true;
    }


    /**
     * 充值活动
     */
    public function chargeActivity($type){
        $time = date("Y-m-d H:i:s");

        $date=date('Y-m-d');
        $redisKey=\Logic\Define\CacheKey::$perfix['chargeActivity'].$type . $date;
        $lock_key = $this->redis->setnx($redisKey, 1);
        $this->redis->expire($redisKey, strtotime('23:59:59') - time());
        if(!$lock_key) {
            $this->logger->debug('数据已跑 ' . $date);
            return false;
        }
        if($type =='week'){
            $give_condition=[3];
        }else{
            $give_condition=[2,4];
        }

        //游戏分类活动
        $act_list = \DB::table('active as a')
                       ->join('active_rule as r', 'a.id', '=','r.active_id')
                       ->where('a.begin_time', '<=', $time)
                       ->where('a.end_time', '>=', $time)
                       ->where('a.status', 'enabled')
                       ->where('r.template_id', 11)
                       ->whereIn('r.give_condition', $give_condition)
                       ->get(['a.id', 'a.name','a.begin_time','a.end_time','a.vender_type',
                           'r.template_id','r.rule','r.luckydraw_condition','r.issue_mode',
                           'r.send_type', 'r.send_max','r.give_condition','r.give_date',
                           'r.withdraw_require_val',])
                       ->toArray();
        if(empty($act_list)){
            $this->logger->info('暂无活动');
            return true;
        }
        $this->logger->info("充值活动：发放开始");
        $wallet = new Wallet($this->ci);
        foreach ($act_list ?? [] as $value)
        {
            $value = (array)$value;
            switch($value['give_condition']){

                case 2:
                    //单日累计
                    $startTime=date('Y-m-d 00:00:00',strtotime('-1 day'));
                    $endTime=date('Y-m-d 23:59:59',strtotime('-1 day'));

                    $applyStartTime=date('Y-m-d 00:00:00',time());
                    $applyEndTime=date('Y-m-d 23:59:59',time());
                    break;
                case 3:
                    $dataType = 'week';
                    //周累计
                    if(date('w') == 1){
                        $startTime = date("Y-m-d 00:00:00", strtotime('last monday'));
                    }else{
                        $startTime = date("Y-m-d 00:00:00", strtotime('-1 week last monday'));
                    }

                    $endTime   = date('Y-m-d 23:59:59', strtotime("-1 sunday",time()));
                    $dateStr='周';
                    $applyStartTime=date('Y-m-d 00:00:00', (time() - ((date('w') == 0  ? 7 : date('w')) - 1) * 24 * 3600));
                    $applyEndTime=date('Y-m-d 23:59:59', (time() + (7 - (date('w') == 0 ?  7 : date('w'))) * 24 * 3600));
                    break;
                case  4:
                    $dataType = 'month';
                    //月累计
                    $startTime = date("Y-m-01 00:00:00", strtotime(date('Y-m-01') . " - 1 month"));
                    $endTime   = date('Y-m-d 23:59:59', strtotime("$startTime +1 month -1 day"));
                    $dateStr='月';

                    $applyStartTime=date('Y-m-d 00:00:00', strtotime(date('Y-m', time()) . '-01 00:00:00'));
                    $applyEndTime=date('Y-m-d 23:59:59', strtotime(date('Y-m', time()) . '-' . date('t', time()) . ' 00:00:00'));
                    break;
                case  5:
                    //自定义
                    $time=explode(',',$value['give_date']);
                    $startTime=date('Y-m-d 00:00:00',strtotime($time[0]));
                    $endTime=date('Y-m-d 23:59:59',strtotime($time[1]));

                    $applyStartTime=date('Y-m-d 00:00:00',time());
                    $applyEndTime=date('Y-m-d 23:59:59',time());
                    break;
                default:
                    $startTime = $applyStartTime=date('Y-m-d 00:00:00',time());
                    $endTime  = $applyEndTime =date('Y-m-d 23:59:59',time());
                    break;
            }
            //判断时间
            if(time() <= strtotime($endTime)){
                $this->logger->error("派奖时间未到达，active_id:".$value['id']);
                continue;
            }

            //今天已统计过
            $active_counts = \DB::table('active_apply')
                                ->where('active_id','=',$value['id']);
            if(in_array($value['give_condition'], [2,3,4])){
                $active_counts = $active_counts->where('apply_time','>=',$applyStartTime)
                                               ->where('apply_time','<=',$applyEndTime);
            }
            $active_counts = $active_counts->count();
            if($active_counts > 0)
            {
                $this->logger->info("重复执行发奖励，active_id:".$value['id']);
                continue;
            }

            //找出范围规则
            $rule_scope = [];
            $rule = explode(';', $value['rule']);
            foreach ($rule ?? [] as $v){
                $rule_scope[] = explode(',', $v);
            }


            if(empty($rule_scope)){
                $this->logger->error("发放规则为空，不执行，active_id:".$value['id']);
                continue;
            }
            $num=0;
            $userList=$this->getChargeMoney($startTime,$endTime,$value['vender_type']);
            if(empty($userList)){
                $this->logger->error("没有满足条件的用户，active_id:".$value['id']);
                continue;
            }
            $sendPrize=0;
            foreach($userList as $u){
                $u=(array)$u;
                $user_id=$u['user_id'];
                $rechargeMoney=$u['money'];
                //计算投注和盈亏金额
                $rule                   = $value['rule'];
                $send_type              = $value['send_type'];
                $send_max               = $value['send_max'];
                //解析rule
                $basePrize              = 0;
                $dml                    = 0;
                $ruleArr                = explode(';', $rule);
                //如果只有一条规则，只要充值大于最小值即赠送优惠
                $last                   = array_pop($ruleArr);
                if ($ruleArr) {
                    foreach ($ruleArr as $k => $ruleConfig) {
                        $config_arr = explode(',', $ruleConfig);
                        if ($config_arr[0] < $rechargeMoney && $rechargeMoney <= $config_arr[1]) {
                            $basePrize = $config_arr[2];
                            $dml = $config_arr[3] ?? 0;
                            break;
                        }
                    }
                }
                $config_arr = explode(',', $last);
                if ($config_arr[0] < $rechargeMoney) {
                    $basePrize = $config_arr[2];
                    $dml = $config_arr[3] ?? 0;
                }

                if ($send_type == 1) {
                    //固定金额
                    $sendPrize = $basePrize;
                } else if ($send_type == 2) {
                    //百分比
                    $sendPrize = bcmul($rechargeMoney ,($basePrize / 10000),2);
                }
                //无奖励
                if($sendPrize == 0){continue;}

                //累计金额上限
                if($sendPrize > $send_max){
                    $sendPrize = $send_max;
                }

                //充值活动上限计算
                $user = \Model\User::where('id',$user_id)->select(['wallet_id', 'id', 'name'])->first();
                //直接开始发放
                try{
                    $this->db->getConnection()->beginTransaction();

                    $total_require_bet = bcmul($sendPrize,$dml,2); //应有打码量
                    // 锁定钱包
                    \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
                    $rand        = rand(10000, 99999);
                    $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);

                    $memo = "充值活动,用户:" . $user['name'] . ",金额:" . $sendPrize /100;
                    $wallet->addMoney($user, $orderNumber, $sendPrize, 105, $memo,$total_require_bet,true);
                    \Model\UserData::where('user_id', $user_id)->increment('rebet_amount', $sendPrize);

                    $model_active = [
                        'user_id'     => $user_id,
                        'user_name'   => $user['name'],
                        'active_id'   => $value['id'],
                        'active_name' => $value['name'],
                        'status'      => 'pass',
                        'state'       => $value['issue_mode'],
                        'memo'        => $value['name'],
                        'coupon_money'=> $sendPrize,
                        'deposit_money'=>$rechargeMoney,
                        'withdraw_require'=> $total_require_bet,
                        'apply_time'  => date('Y-m-d H:i:s'),
                    ];
                    \DB::table('active_apply')->insert($model_active);

                    $this->db->getConnection()->commit();
                    $num ++;
                    $this->logger->info('充值活动'.$value['id'].':用户'.$user['name'].'派送金额:'.($sendPrize /100).'发放完毕');

                }catch(\Exception $e){
                    $this->logger->error('游戏分类统计发放：异常，active_id：' . $value['id'] . "userId: {$user_id}" . "  " . $time . "\r\n" . $e->getMessage());
                    $this->db->getConnection()->rollBack();
                }
            }

            $this->logger->info("完成，active_id:".$value['id'].'共'.count($userList).'名用户,成功派送:'.$num.'名用户');
        }
        return 'success';
    }

    public function getChargeMoney($startTime,$endTime,$venderType){
        $query=\DB::table('funds_deposit')
                   ->where('created','>=',$startTime)
                   ->where('created','<=',$endTime)
                   ->where('status','paid')
                    ->where('money', '>', 0);
        if($venderType == 2){
            $query->whereRaw('FIND_IN_SET("online",state)');
        }elseif($venderType == 3){
            //线下充值
            $query->whereRaw('!FIND_IN_SET("online",state)');
        }
        $res = $query->selectRaw("distinct(user_id),sum(money) money")->groupBy('user_id')->get()->toArray();
        return $res;
    }

    /**
     *  更新用户返水提升比例
     **/
    public function updateUserDirectBkgeIncr($userId = '')
    {
        //获取直推返水比例设置
        $bkgeData = \DB::table('direct_bkge')
                       ->get(['register_count','recharge_count','bkge_increase','bkge_increase_week','bkge_increase_month'])
                       ->toArray();

        //比例排序
        $register      = array_column($bkgeData,'register_count');
        array_multisort($register,SORT_ASC,$bkgeData);
        //获取规则最大返水比例
        $max           = end($bkgeData);

        //获取用户当前直推注册人数 和 充值人数
        if(!empty($userId)) {
            $userDirectBkge = \DB::table('user_data')
                            ->where('user_id', '=', $userId)
                            ->first(['direct_deposit','direct_register','direct_bkge_increase','direct_bkge_increase_week','direct_bkge_increase_month']);

            //获取当前阶级 直推返水比例
            $bkgeIncrease = $bkgeIncreaseWeek = $bkgeIncreaseMonth = 0;
            foreach ($bkgeData as $item){
                //超过最大等级，按最高比例
                if ($userDirectBkge->direct_register > $max->register_count && $userDirectBkge->direct_deposit > $max->recharge_count){
                    //比较用户当前返水比例与符合返水规则比例大小，取最大值
                    $bkgeIncrease = max([$userDirectBkge->direct_bkge_increase, $max->bkge_increase]);
                    $bkgeIncreaseWeek = max([$userDirectBkge->direct_bkge_increase_week, $max->bkge_increase_week]);
                    $bkgeIncreaseMonth = max([$userDirectBkge->direct_bkge_increase_month, $max->bkge_increase_month]);
                }

                //注册人数
                if ($userDirectBkge->direct_register >= $item->register_count && $userDirectBkge->direct_deposit >= $item->recharge_count){
                    $bkgeIncrease = max([$userDirectBkge->direct_bkge_increase, $item->bkge_increase]);
                    $bkgeIncreaseWeek = max([$userDirectBkge->direct_bkge_increase_week, $item->bkge_increase_week]);
                    $bkgeIncreaseMonth = max([$userDirectBkge->direct_bkge_increase_month, $item->bkge_increase_month]);
                }
            }

            \DB::table('user_data')
                ->where('user_id', '=', $userId)
                ->update([
                    'direct_bkge_increase' => $bkgeIncrease,
                    'direct_bkge_increase_week' => $bkgeIncreaseWeek,
                    'direct_bkge_increase_month' => $bkgeIncreaseMonth
                ]);
        } else {
            $limit = 1000;
            $offset = 0;
            $data = [];
            while(1) {
                $userDirectBkge = \DB::table('user_data')
                                    ->offset($offset)
                                    ->limit($limit)
                                    ->get(['user_id','direct_deposit','direct_register','direct_bkge_increase','direct_bkge_increase_week','direct_bkge_increase_month'])
                                    ->toArray();

                if(empty($userDirectBkge)) {
                    break;
                }

                //获取当前阶级 直推返水比例
                foreach($userDirectBkge as $value) {
                    $bkgeIncrease = $bkgeIncreaseWeek = $bkgeIncreaseMonth = 0;
                    foreach ($bkgeData as $item){
                        //超过最大等级，按最高比例
                        if ($value->direct_register > $max->register_count && $value->direct_deposit > $max->recharge_count){
                            //比较用户当前返水比例与符合返水规则比例大小，取最大值
                            $bkgeIncrease = max([$value->direct_bkge_increase, $max->bkge_increase]);
                            $bkgeIncreaseWeek = max([$value->direct_bkge_increase_week, $max->bkge_increase_week]);
                            $bkgeIncreaseMonth = max([$value->direct_bkge_increase_month, $max->bkge_increase_month]);
                        }

                        //注册人数
                        if ($value->direct_register >= $item->register_count && $value->direct_deposit >= $item->recharge_count){
                            $bkgeIncrease = max([$value->direct_bkge_increase, $item->bkge_increase]);
                            $bkgeIncreaseWeek = max([$value->direct_bkge_increase_week, $item->bkge_increase_week]);
                            $bkgeIncreaseMonth = max([$value->direct_bkge_increase_month, $item->bkge_increase_month]);
                        }

                        $data[$value->user_id]['user_id'] = $value->user_id;
                        $data[$value->user_id]['direct_bkge_increase'] = $bkgeIncrease;
                        $data[$value->user_id]['direct_bkge_increase_week'] = $bkgeIncreaseWeek;
                        $data[$value->user_id]['direct_bkge_increase_month'] = $bkgeIncreaseMonth;
                    }
                }

                if (!empty($data)) {
                    $updateColumn = ['direct_bkge_increase','direct_bkge_increase_week','direct_bkge_increase_month'];
                    $updateSql = "UPDATE `user_data` SET ";
                    $sets      = [];
                    foreach ($updateColumn as $uColumn) {
                        $setSql = "`" . $uColumn . "` = CASE ";
                        foreach ($data as $key=>$val) {
                            $setSql .= " WHEN `user_id` = '".$key."' THEN ".$val[$uColumn];
                        }
                        $setSql .= " ELSE `" . $uColumn . "` END ";
                        $sets[] = $setSql;
                    }
                    $updateSql .= implode(', ', $sets);
                    $userIdStr = implode(',', array_column($data,'user_id'));
                    $updateSql = rtrim($updateSql, ", ") . " WHERE `user_id` IN (" . $userIdStr . ")";

                    \DB::update($updateSql);
                    $data = [];
                }

                $offset += $limit;
            }
        }
    }

    /**
     * @param string $dateType 活动周期
     * @return string
     */
    public function drivByWeekActivity($dateType, $startTime=null, $endTime=null) {
        switch($dateType) {
            case "month":
                $deal_type = 703;    //701-日回水，702-周回水，703-月回水
                //上个月28号
                !$startTime && $startTime = date("Y-m-28", strtotime(date('Y-m-01')) - 86400);
                !$endTime && $endTime   = date('Y-m-27');
                $activityType=9;
                $dateStr='月';
                $type=3;
                $batchTime=$startTime.'~'.$endTime;
                break;
            case "week":
            default :
                //传入开始时间  为了手动补发返水
                if(!$startTime){
                    //获取周一的时间
                    if(date('w') == 1){
                        $startTime = date("Y-m-d", strtotime('last monday'));
                    }else{
                        $startTime = date("Y-m-d", strtotime('-1 week last monday'));
                    }
                }
                $deal_type = 702;    //701-日回水，702-周回水，703-月回水
                //传入结束时间
                !$endTime && $endTime   = date('Y-m-d', strtotime("-1 sunday",time()));
                $activityType=8;
                $dateStr='周';
                $type=2;
                $batchTime=date('Y-m-d',strtotime($startTime)).'~'.date('Y-m-d',strtotime($endTime));
                break;
        }
        $date     = date('Y-m-d H:i:s', time());
        $activity = \DB::table("active")
            ->where('type_id', '=', $activityType)
            ->where('status', '=', "enabled")
            ->where('begin_time', '<', $date)
            ->where('end_time', '>', $date)
            ->first(['id', 'name', 'type_id']);
        if(empty($activity)) {
            $this->logger->error("暂无{$dateType}返水活动");
            return false;
        }
        $rule = \DB::table("active_rule")->where("template_id", '=', $activity->type_id)->where("active_id", '=', $activity->id)->first(['id', 'issue_time', 'issue_cycle', 'issue_mode', 'rule']);
        if(empty($rule) || empty($rule->rule)) {
            $this->logger->error("{$activity->name}活动暂未配置规则");
            return false;
        }
        $lastEndTime = date('Y-m-d',strtotime($endTime)+3600*24);
//        $activityCnt=\DB::table('active_apply')
//            ->where('active_id','=',$activity->id)
//            ->where('apply_time','>=',$lastEndTime)
//            ->count();
//        if($activityCnt > 0){
//            $this->logger->error('【返水】已经计算过返水数据 ' . $date);
//            return false;
//        }

        /**
         * 取规则的值
         * type:betting 按照当日投注额回水；type:loss按照当日亏损额回水
         * status :fixed 按固定金额,percentage 按百分比
         */
        $ruleData = json_decode($rule->rule, true);
        if(empty($ruleData)) {
            $this->logger->error("{$activity->name}活动暂未配置规则");
            return false;
        }
        $sTime=microtime(true);
        $this->logger->error('游戏返水开始时间'.$sTime);
        $wallet = new Wallet($this->ci);
        $userList = $this->getActivityOrderUserIds($startTime, $endTime, $activityType==9?'day':'week');

        //已参数活动的用户ID集
        /* $activityUsers=(array)\DB::table('active_apply')
             ->where('active_id','=',$activity->id)
             ->where('apply_time','>=',$applyStartTime)
             ->where('apply_time','<=',$applyEndTime)
             ->pluck('user_id')->toArray();*/
        $batchNo=time();

        foreach($userList as $u){
            /*if(!empty($activityUsers) && in_array($u->user_id, $activityUsers)){
                continue;
            }*/
            //检查用户是否已经返水
            $activityCnt=\DB::table('active_apply')
                ->where('active_id','=',$activity->id)
                ->where('user_id',$u->user_id)
                ->where('apply_time','>=',$lastEndTime)
                ->count();
            if($activityCnt > 0){
                continue;
            }

            $this->logger->debug("游戏返水 userId:".$u->user_id);
            $userRebet = $this->getUserActivityBkgeMoney($u->user_id, $startTime, $endTime, $ruleData, $activityType==9?'month':'week');
            // 判断是否返水
            if($userRebet['money'] > 0) {
                $this->logger->info("【活动返水】统计回水金额与应用打码量[元]：" . var_export($userRebet, true));

                $money             = bcmul($userRebet['money'],100,2); //回水金额
                $total_require_bet = bcmul($userRebet['total_require_bet'],100,2); //应有打码量
                $user = (array)\DB::table('user')->where('id', $u->user_id)->first(['id','name','wallet_id','ranting']);

                //直推返水
                $direct_list            = $this->userDirectMoney($money,$total_require_bet,$u->user_id,$dateType);
                $total_require_bet      = $direct_list['total_require_bet'] ?? $total_require_bet;
                $money                  = $direct_list['money'] ?? $money;
                $userRebet['value']     = bcadd($direct_list['rate'],$userRebet['rate'],2);

                try {
                    $this->db->getConnection()->beginTransaction();
                    if($this->db->getConnection()->transactionLevel()) {
                        //反水开始
                        /*$rand        = rand(10000, 99999);
                        $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);
                        \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
                        $memo = "{$dateStr}返水活动: 用户:" . $user['name'] . ",金额:" . $money /100;
//                        $wallet->addMoney($user, $orderNumber, $money, 4, $memo, $total_require_bet);
                        $wallet->addMoney($user, $orderNumber, $money, $deal_type, $memo, $total_require_bet);
                        \Model\UserData::where('user_id', $user['id'])->increment('rebet_amount', $money);*/

                        $loggerTitle = '【返水通过】';

                        $model_active = [
                            'user_id'     => $user['id'],
                            'user_name'   => $user['name'],
                            'active_id'   => $activity->id,
                            'active_name' => $activity->name,
                            'status'      => 'undetermined',
                            'state'       => 'manual',
                            'memo'        => $activity->name,
                            'deposit_money'=> $userRebet['allBetAmount'],
                            'coupon_money'=> $money,
                            'withdraw_require'=> (int)$total_require_bet,
                            'apply_time'  => date('Y-m-d H:i:s'),
                            'batch_no'    => $batchNo
                        ];
                        $apply_id = \DB::table('active_apply')->insertGetId($model_active);

                        //插入日志数据
                        $rebetLog = [
                            'user_id'            => $user['id'],
                            'rebet_user_ranting' => $user['ranting'],
                            'game_id'            => $userRebet['game_id'] ?? 0,
                            'active_apply_id'    => $apply_id,
                            'direct_rate'        => $direct_list['rate'] ?? 0,
                            'desc'               => json_encode($userRebet['rebetLog']),
                        ];
                        \DB::table('rebet_log')->insert($rebetLog);
                        $this->logger->error('【返水】执行反水成功 --- 用户:' . $user['name'] . ",返水成功金额:" . $money);

                        $this->db->getConnection()->commit();
                    }
                } catch(\Exception $e) {
                    $this->logger->error("【返水异常】执行反水失败 ---. userId: {$user['id']}" . "  " . $date . "\r\n" . $e->getMessage());
                    $this->db->getConnection()->rollback();
                }

                /*if($money > 0) {
                    $title = $this->ci->get('settings')['website']['name'] ?? '';//标题
                    //发送回水信息
                    $content  = $this->lang->text("Dear user, you bought %s color amount of %s yuan yesterday. The system will return the return amount of %s to you. Please check and check. If you have any questions about the amount of return water, please consult online customer service in time.", [$activity->name, $userRebet['allBetAmount']/100, $money/100, $title]);
                    $exchange = 'user_message_send';
                    \Utils\MQServer::send($exchange, [
                        'user_id' => $user['id'],
                        'title'   => $this->lang->text("Backwater news"),
                        //'content' => vsprintf($content, [$temp['allBetAmount'] / 100, $temp['money'] / 100]),
                        'content' => $content,
                    ]);
                }*/
                unset($model_active,$rebetLog,$title,$content,$user);
            }else{
                $this->logger->error("游戏返水 userId:".$u->user_id . ' 无返水');
            }
        }
        $activeData=\DB::table('active_apply')
            ->selectRaw('count(1) as cnt,sum(coupon_money) as back_amount')
            ->where('batch_no',$batchNo)
            ->first();
        $backData=array(
            'active_id'=>$activity->id,
            'batch_no'  =>$batchNo,
            'type'      =>$type,
            'batch_time'=>$batchTime,
            'back_cnt'  => $activeData->cnt,
            'back_amount'=>$activeData->back_amount ?? 0,
        );
        if($activeData->cnt ==0){
            $backData['status'] = 2;
            $backData['send_time']=date('Y-m-d H:i:s',time());
        }
        \DB::table('active_backwater')->insert($backData);
        $eTime=microtime(true) - $sTime;
        $this->logger->error('游戏返水结束时间'.$eTime);
        return 'success';
    }
}