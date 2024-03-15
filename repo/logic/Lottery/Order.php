<?php

namespace Logic\Lottery;


use Logic\Set\SystemConfig;
use Logic\Wallet\Wallet;
use LotteryPlay\Logic;
use Model\FundsTrialDealLog;
use Model\LotteryTrialOrder;
use Model\LotteryTrialOrderTemp;
use Model\TrialFunds;
use Model\TrialUser;
use Model\User;
use Model\Funds;
use Model\Hall;
use Model\Room;
use Model\LotteryPlayStruct;
use Model\LotteryPlayOdds;
use Model\Lottery;
use Model\LotteryOrder;
use Model\LotteryOrderTemp;
use Model\LotteryInfo;
use Model\LotteryPlayLimitOdds;
use Model\FundsDealLog;
use Logic\Lottery\InsertNumber;
use Model\LotteryChase;
use Logic\Define\CacheKey;
use DB;

/**
 * 订单模块
 */
class Order extends \Logic\Logic {

    protected $state;

    protected $stateParams;

    public function addOrder($uid, $data, $origin = 1) {
        $global = SystemConfig::getModuleSystemConfig('lottery');

        // 校验下单开关11
        if ($global['stop_bet']) {
            return $this->lang->set(6);
        }

        $user = User::where('id', $uid)
                    ->first();

        if (empty($user)) {
            return $this->lang->set(86);
        }

        if (!isset($data['lottery_id']) || !isset($data['lottery_number']) || !isset($data['play'])) {
            return $this->lang->set(10);
        }

        // 校验平台异常下单开关
        if ($global['unusual_period_auto'] && $this->redisCommon->get(CacheKey::$perfix['commonLotterySaleStatus'] . $data['lottery_id']) == 'close') {
            return $this->lang->set(8);
        }

        $data['room_id'] = isset($data['room_id']) ? intval($data['room_id']) : 0;
        $data['origin'] = $origin;
        $data['lottery_id'] = intval($data['lottery_id']);

        // 彩期校验
        if (!$lotteryInfo = $this->validLottery($data['lottery_id'], $data['lottery_number'], $user)) {
            return $this->lang->set($this->state, $this->stateParams);
        }
        //判定是否开盘
        $start_delay = Lottery::where('id',$data['lottery_id'])->value('start_delay');
        if(isset($lotteryInfo['start_time']) && $lotteryInfo['start_time'] + $start_delay > time()){
            return $this->lang->set(27);
        }
        // 房间校验
        if (!$hallData = $this->validRoom($data['lottery_id'], $data['room_id'], $data['origin'])) {
            return $this->lang->set(65);
        }

        list($hallId, $hallLevel) = $hallData;

        // 彩种校验
        if (!$lottery = $this->validLotteryOpen($data['lottery_id'], $hallLevel)) {
            return $this->lang->set(66, [], [], ['error' => $hallLevel, 'data' => $data['lottery_id']]);
        }

        $logic = new Logic();
        $money = [];
        $orders = [];
        $logs = [];
        $maxOdds = 0;

        // 生成订单结构数据 & 校验注数
        foreach ($data['play'] as $play) {
            $play['id'] = intval($play['id']);
            $play['times'] = intval($play['times']);
            $play['price'] = intval($play['price']);
            $lottery_ids[$play['id']] = 1;

            if ($play['price'] <= 0 || $play['id'] <= 0 || $play['times'] <= 0) {
                return $this->lang->set(67);
            }

            // 校验玩法数据
            if (!$orderTemp = $this->validPlay($user['id'], $user['name'], $data['lottery_id'], $lottery['pid'], $data['lottery_number'], $data['origin'], $hallId, $data['room_id'], $hallLevel, $logic, $play, $user['tags'])) {
                return $this->lang->set($this->state, [], [], ['h' => $hallId, 'l' => $hallLevel, 'p' => $play]);
            }

            //校验购球限制
            if (!$this->_validBuyBallNum($data['lottery_id'], $play)) {
                return $this->lang->set(894);
            }


            $maxOdds += max(array_values($orderTemp['odds']));
            $money[] = $orderTemp['pay_money'];
            $orders[] = $orderTemp;
        }

        // 计算总金额
        $totalMoney = array_sum($money);
        if ($totalMoney == 0) {
            return $this->lang->set(68);
        }

        $chaseStruct = false;

        // 校验开关
        if (isset($data['chase']) && $global['stop_chasing']) {
            return $this->lang->set(7);
        }

        // 校验追号
        if (isset($data['chase']) && !$chaseStruct = $this->validChase($user['id'], $data['lottery_id'], $data['lottery_number'], $maxOdds, $totalMoney, $data['chase'])) {
            return $this->lang->set($this->state);
        }

        // 替换总金额
        if ($chaseStruct) {
            $totalMoney = $chaseStruct['total'];
        }

        // 限额校验
        if (!$this->validPayMoney($lottery, $data['lottery_number'], $user['id'], $hallId, $orders)) {
            return $this->lang->set($this->state, $this->stateParams);
        }

        // 扣除钱包 和 生成流水
        try {
            $this->db->getConnection()
                     ->beginTransaction();

            $funds = Funds::where('id', $user['wallet_id'])
                          ->lockForUpdate()
                          ->first();

            if ($funds['balance'] < $totalMoney) {
                $this->db->getConnection()
                         ->rollback();
                return $this->lang->set(69, [], [], ['fu' => $funds['balance'], 'total' => $totalMoney]);
            }

            //流水里面添加打码量可提余额等信息 (投注不对打码量信息做任何修改)
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData($uid);

            // 判断是否有追号数据
            if ($chaseStruct == false) {
                foreach ($orders as $k => $order) {
                    $str = $this->getOrderLogStr($lottery['name'], $order['order_number'], $order['play_group'], $order['play_name'], $order['play_number']);
                    $order['hall_id'] = $hallId;

                    FundsDealLog::create([
                        "user_id"           => $order['user_id'],
                        "username"          => $order['user_name'],
                        "order_number"      => $order['order_number'],
                        "deal_type"         => FundsDealLog::TYPE_LOTTERY_BETTING,
                        "deal_category"     => FundsDealLog::CATEGORY_COST,
                        "deal_money"        => $order['pay_money'],
                        "balance"           => $funds['balance'] - $order['pay_money'],
                        "memo"              => $str,
                        "wallet_type"       => FundsDealLog::WALLET_TYPE_PRIMARY,
                        'withdraw_bet'      => 0,
                        'total_bet'         => $dmlData->total_bet,
                        'total_require_bet' => $dmlData->total_require_bet,
                        'free_money'        => $dmlData->free_money,
                    ]);

                    $order['id'] = LotteryOrder::create($order)->id;

                    LotteryOrderTemp::create($order);

                    $order['pid'] = $lottery['pid'];

                }
            } else {
                // 追号数据生成
                $chaseNumber = LotteryOrder::generateOrderNumber();
                $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
                $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
                //相应需要插入的数据整理
                foreach ($orders as $k => $order) {
                    //$chase  追号总表  $chase_sub  追号子表 $chase_sub_tmp   以方便后期减少联表查询，数据直接存储
                    $chase[$k]['user_id'] = $order['user_id'];
                    $chase[$k]['user_name'] = $order['user_name'];
                    $chase[$k]['tags'] = $order['tags'];
                    $chase[$k]['origin'] = $origins[$origin] ?? 1;
                    $chase[$k]['chase_type'] = $chaseStruct['chase']['chase_type'];
                    $chase[$k]['main_chase_number'] = $chaseNumber;
                    $chase[$k]['chase_number'] = LotteryOrder::generateOrderNumber();

                    if ($order['room_id']) {
                        $room = \DB::table('room')
                                   ->where('id', $order['room_id'])
                                   ->first(['hall_id', 'room_name']);
                        if ($room) {
                            $chase[$k]['hall_id'] = $room->hall_id;
                            $chase[$k]['hall_name'] = \DB::table('hall')
                                                         ->where('id', $room->hall_id)
                                                         ->where('lottery_id', $order['lottery_id'])
                                                         ->value('hall_name');
                            $chase[$k]['room_id'] = $order['room_id'];
                            $chase[$k]['room_name'] = $room->room_name;
                        }
                    }

                    $chase[$k]['lottery_id'] = $order['lottery_id'];
                    $chase[$k]['lottery_name'] = \DB::table('lottery')
                                                    ->where('id', $order['lottery_id'])
                                                    ->value('name');

                    $chase[$k]['play_id'] = $order['play_id'];
                    $chase[$k]['play_group'] = $order['play_group'];
                    $chase[$k]['play_name'] = $order['play_name'];
                    $chase[$k]['times'] = $order['times'];
                    $chase[$k]['one_money'] = $order['one_money'];
                    $chase[$k]['bet_num'] = $order['bet_num'];
                    $chase[$k]['complete_periods'] = 1;
                    $chase[$k]['sum_periods'] = count($chaseStruct['chase']['chase_list']);
                    $chase[$k]['mode'] = $order['state'];
                    $chase[$k]['state'] = 'underway';

                    //追号子表
                    $tmp_paymoney = 0;
                    foreach ($chaseStruct['chase']['chase_list'] as $kk => $sub) {
                        $tmp['chase_number'] = $chase[$k]['chase_number'];
                        $tmp['lottery_number'] = $sub['lottery_number'];
                        $tmp['one_money'] = $order['pay_money'];   //one_money = 初始倍数*初始金额($order['one_money'] * $order['times'])   不能直接取$sub里面的price  ,  多种玩家是多种价格总和
                        $tmp['times'] = $sub['times'];
                        $tmp['pay_money'] = $tmp['times'] * $tmp['one_money'];   //单注金额*倍数($order['one_money'] * $order['times'])   不能直接取$sub里面的price  ,  多种玩家是多种价格总和
                        $tmp['play_number'] = $order['play_number'];
                        $tmp['odds'] = json_encode($order['odds'], true);
                        $tmp['state'] = $kk == 0 ? 'created' : 'default';
                        $tmp['odds_ids'] = json_encode($order['odds_ids'], true);
                        $tmp['settle_odds'] = json_encode($order['settle_odds'], true);
                        $chase_sub[] = $tmp;
                        $tmp_paymoney += $tmp['pay_money'];
                        if ($tmp['state'] == 'created') {
                            $chase_sub_tmp[] = $tmp;
                        }
                    }

                    $chase[$k]['increment_bet'] = $tmp_paymoney;
                    //$str = '追号:' . $lottery['name'] . '-追单号:' . $chase[$k]['chase_number'] . '-总期数:' . $chase[$k]['sum_periods'];
                    $str = $this->lang->text("Chase number: %s - chase number: %s - total period: %s", [$lottery['name'], $chase[$k]['chase_number'], $chase[$k]['sum_periods']]);
                    $funds['balance'] -= $tmp_paymoney;

                    //追号流水
                    //流水数据整理
                    $logs[] = [
                        "user_id"           => $user['id'],
                        "username"          => $user['name'],
                        "order_number"      => $chase[$k]['chase_number'],
                        "deal_type"         => FundsDealLog::TYPE_LOTTERY_BETTING,
                        "deal_category"     => FundsDealLog::CATEGORY_COST,
                        "deal_money"        => $tmp_paymoney,
                        "balance"           => $funds['balance'],
                        "memo"              => $str,
                        "wallet_type"       => FundsDealLog::WALLET_TYPE_PRIMARY,
                        'withdraw_bet'      => 0,
                        'total_bet'         => $dmlData->total_bet,
                        'total_require_bet' => $dmlData->total_require_bet,
                        'free_money'        => $dmlData->free_money,
                    ];
                }

                if (isset($chase) && isset($chase_sub) && isset($chase_sub_tmp) && $logs) {
                    DB::table('lottery_chase_order')
                      ->insert($chase);
                    DB::table('lottery_chase_order_sub')
                      ->insert($chase_sub);
                    DB::table('lottery_chase_order_sub_temp')
                      ->insert($chase_sub_tmp);
                    DB::table('funds_deal_log')
                      ->insert($logs);
                } else {
                    return $this->lang->set(6);
                }
            }

            // 扣除钱包金额
            (new Wallet($this->ci))->crease($user['wallet_id'], -$totalMoney);

            $this->db->getConnection()
                     ->commit();

        } catch (\Exception $e) {
            $this->db->getConnection()
                     ->rollback();
print_r($e->getMessage());
            //return $this->lang->set(84, [], [], ['error' => $e->getMessage()]);
        }

        return $this->lang->set(145);
    }

    /**
     * 试玩注单
     *
     * @param $uid
     * @param $data
     * @param int $origin
     *
     * @return mixed
     */
    public function addTrialOrder($uid, $data, $origin = 1) {
        $global = SystemConfig::getModuleSystemConfig('lottery');

        // 校验下单开关11
        if ($global['stop_bet']) {
            return $this->lang->set(6);
        }

        $user = TrialUser::where('id', $uid)->first();
        if (empty($user)) {
            return $this->lang->set(86);
        }

        if (!isset($data['lottery_id']) || !isset($data['lottery_number']) || !isset($data['play'])) {
            return $this->lang->set(10);
        }

        // 校验平台异常下单开关
        if ($global['unusual_period_auto'] && $this->redisCommon->get(CacheKey::$perfix['commonLotterySaleStatus'] . $data['lottery_id']) == 'close') {
            return $this->lang->set(8);
        }

        $data['room_id'] = isset($data['room_id']) ? intval($data['room_id']) : 0;
        $data['origin'] = $origin;
        $data['lottery_id'] = intval($data['lottery_id']);

        // 彩期校验
        if (!$lotteryInfo = $this->validLottery($data['lottery_id'], $data['lottery_number'])) {
            return $this->lang->set($this->state, $this->stateParams);
        }

        // 房间校验
        if (!$hallData = $this->validRoom($data['lottery_id'], $data['room_id'], $data['origin'])) {
            return $this->lang->set(65);
        }

        list($hallId, $hallLevel) = $hallData;

        // 彩种校验
        if (!$lottery = $this->validLotteryOpen($data['lottery_id'], $hallLevel)) {
            return $this->lang->set(66, [], [], ['error' => $hallLevel, 'data' => $data['lottery_id']]);
        }

        $logic = new Logic();
        $money = [];
        $orders = [];
        $logs = [];
        $maxOdds = 0;

        // 生成订单结构数据 & 校验注数
        foreach ($data['play'] as $play) {
            $play['id'] = intval($play['id']);
            $play['times'] = intval($play['times']);
            $play['price'] = intval($play['price']);
            $lottery_ids[$play['id']] = 1;

            if ($play['price'] <= 0 || $play['id'] <= 0 || $play['times'] <= 0) {
                return $this->lang->set(67);
            }

            // 校验玩法数据
            if (!$orderTemp = $this->validPlay($user['id'], $user['name'], $data['lottery_id'], $lottery['pid'], $data['lottery_number'], $data['origin'], $hallId, $data['room_id'], $hallLevel, $logic, $play, 7)) {//试玩用户的会员标签
                return $this->lang->set($this->state, [], [], ['h' => $hallId, 'l' => $hallLevel, 'p' => $play]);
            }

            //校验购球限制
            if (!$this->_validBuyBallNum($data['lottery_id'], $play)) {
                return $this->lang->set(894);
            }


            $maxOdds += max(array_values($orderTemp['odds']));
            $money[] = $orderTemp['pay_money'];
            unset($orderTemp['tags']);//试玩用户没有会员标签
            $orders[] = $orderTemp;
        }

        // 计算总金额
        $totalMoney = array_sum($money);

        if ($totalMoney == 0) {
            return $this->lang->set(68);
        }

        $chaseStruct = false;

        // 校验开关
        if (isset($data['chase']) && $global['stop_chasing']) {
            return $this->lang->set(7);
        }

        // 校验追号
        if (isset($data['chase']) && !$chaseStruct = $this->validChase($user['id'], $data['lottery_id'], $data['lottery_number'], $maxOdds, $totalMoney, $data['chase'])) {
            return $this->lang->set($this->state);
        }

        // 替换总金额
        if ($chaseStruct) {
            $totalMoney = $chaseStruct['total'];
        }

        // 限额校验
        if (!$this->validPayMoney($lottery, $data['lottery_number'], $user['id'], $hallId, $orders)) {
            return $this->lang->set($this->state, $this->stateParams);
        }

        // 扣除钱包 和 生成流水
        try {
            $this->db->getConnection()->beginTransaction();

            $funds = TrialFunds::where('id', $user['wallet_id'])->lockForUpdate()->first();
            if ($funds['balance'] < $totalMoney) {
                $this->db->getConnection()->rollback();
                return $this->lang->set(69, [], [], ['fu' => $funds['balance'], 'total' => $totalMoney]);
            }
            // 判断是否有追号数据
            if ($chaseStruct == false) {
                foreach ($orders as $k => $order) {
                    $str = $this->getOrderLogStr($lottery['name'], $order['order_number'], $order['play_group'], $order['play_name'], $order['play_number']);
                    $order['hall_id'] = $hallId;

                    FundsTrialDealLog::create([
                        "user_id"           => $order['user_id'],
                        "username"          => $order['user_name'],
                        "order_number"      => $order['order_number'],
                        "deal_type"         => FundsTrialDealLog::TYPE_LOTTERY_BETTING,
                        "deal_category"     => FundsTrialDealLog::CATEGORY_COST,
                        "deal_money"        => $order['pay_money'],
                        "balance"           => $funds['balance'] - $order['pay_money'],
                        "memo"              => $str,
                    ]);

                    $order['id'] = LotteryTrialOrder::create($order)->id;
                    LotteryTrialOrderTemp::create($order);
                    $order['pid'] = $lottery['pid'];
                }
            } else {  // 追号数据生成
                $chaseNumber = LotteryTrialOrder::generateOrderNumber();
                $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
                $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';

                //相应需要插入的数据整理
                foreach ($orders as $k => $order) {
                    //$chase  追号总表  $chase_sub  追号子表 $chase_sub_tmp   以方便后期减少联表查询，数据直接存储
                    $chase[$k]['user_id'] = $order['user_id'];
                    $chase[$k]['user_name'] = $order['user_name'];
                    $chase[$k]['origin'] = $origins[$origin] ?? 1;
                    $chase[$k]['chase_type'] = $chaseStruct['chase']['chase_type'];
                    $chase[$k]['main_chase_number'] = $chaseNumber;
                    $chase[$k]['chase_number'] = LotteryTrialOrder::generateOrderNumber();
                    if ($order['room_id']) {
                        $room = \DB::table('room')
                                   ->where('id', $order['room_id'])
                                   ->first(['hall_id', 'room_name']);
                        if ($room) {
                            $chase[$k]['hall_id'] = $room->hall_id;
                            $chase[$k]['hall_name'] = \DB::table('hall')
                                                         ->where('id', $room->hall_id)
                                                         ->where('lottery_id', $order['lottery_id'])
                                                         ->value('hall_name');
                            $chase[$k]['room_id'] = $order['room_id'];
                            $chase[$k]['room_name'] = $room->room_name;
                        }
                    }
                    $chase[$k]['lottery_id'] = $order['lottery_id'];
                    $chase[$k]['lottery_name'] = \DB::table('lottery')
                                                    ->where('id', $order['lottery_id'])
                                                    ->value('name');
                    $chase[$k]['play_id'] = $order['play_id'];
                    $chase[$k]['play_group'] = $order['play_group'];
                    $chase[$k]['play_name'] = $order['play_name'];
                    $chase[$k]['times'] = $order['times'];
                    $chase[$k]['one_money'] = $order['one_money'];
                    $chase[$k]['bet_num'] = $order['bet_num'];
                    $chase[$k]['complete_periods'] = 1;
                    $chase[$k]['sum_periods'] = count($chaseStruct['chase']['chase_list']);
                    $chase[$k]['mode'] = $order['state'];
                    $chase[$k]['state'] = 'underway';
                    //追号子表
                    $tmp_paymoney = 0;
                    foreach ($chaseStruct['chase']['chase_list'] as $kk => $sub) {
                        $tmp['chase_number'] = $chase[$k]['chase_number'];
                        $tmp['lottery_number'] = $sub['lottery_number'];
                        $tmp['one_money'] = $order['pay_money'];   //one_money = 初始倍数*初始金额($order['one_money'] * $order['times'])   不能直接取$sub里面的price  ,  多种玩家是多种价格总和
                        $tmp['times'] = $sub['times'];
                        $tmp['pay_money'] = $tmp['times'] * $tmp['one_money'];   //单注金额*倍数($order['one_money'] * $order['times'])   不能直接取$sub里面的price  ,  多种玩家是多种价格总和
                        $tmp['play_number'] = $order['play_number'];
                        $tmp['odds'] = json_encode($order['odds'], true);
                        $tmp['state'] = $kk == 0 ? 'created' : 'default';
                        $tmp['odds_ids'] = json_encode($order['odds_ids'], true);
                        $tmp['settle_odds'] = json_encode($order['settle_odds'], true);
                        $chase_sub[] = $tmp;
                        $tmp_paymoney += $tmp['pay_money'];
                        if ($tmp['state'] == 'created') {
                            $chase_sub_tmp[] = $tmp;
                        }
                    }
                    $chase[$k]['increment_bet'] = $tmp_paymoney;
                    //$str = '追号:' . $lottery['name'] . '-追单号:' . $chase[$k]['chase_number'] . '-总期数:' . $chase[$k]['sum_periods'];
                    $str = $this->lang->text("Chase number: %s - chase number: %s - total period: %s", [$lottery['name'], $chase[$k]['chase_number'], $chase[$k]['sum_periods']]);
                    $funds['balance'] -= $tmp_paymoney;
                    //追号流水
                    //流水数据整理
                    $logs[] = [
                        "user_id"           => $user['id'],
                        "username"          => $user['name'],
                        "order_number"      => $chase[$k]['chase_number'],
                        "deal_type"         => FundsTrialDealLog::TYPE_LOTTERY_BETTING,
                        "deal_category"     => FundsTrialDealLog::CATEGORY_COST,
                        "deal_money"        => $tmp_paymoney,
                        "balance"           => $funds['balance'],
                        "memo"              => $str,
                    ];
                }
                if (isset($chase) && isset($chase_sub) && isset($chase_sub_tmp) && $logs) {
                    DB::table('lottery_trial_chase_order')
                      ->insert($chase);
                    DB::table('lottery_trial_chase_order_sub')
                      ->insert($chase_sub);
                    DB::table('lottery_trial_chase_order_sub_temp')
                      ->insert($chase_sub_tmp);
                } else {
                    return $this->lang->set(6);
                }
            }

            // 扣除钱包金额
            (new Wallet($this->ci))->Trialcrease($user['wallet_id'], -$totalMoney);
            $this->db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            return $this->lang->set(84, [], [], ['error' => $e->getMessage()]);
        }

        return $this->lang->set(145);
    }

    /**
     * 读取彩票开关
     *
     * @param  [type] $lottery_id [description]
     *
     * @return [type]             [description]
     */
    protected function validLotteryOpen($lotteryId, $hallLevel) {
        if ($hallLevel == 4) { // pc
            $result = Lottery::where('id', $lotteryId)
                             ->whereRaw("find_in_set('enabled', state)")
                             ->first();
        } else if ($hallLevel == 5) { // 传统
            $result = Lottery::where('id', $lotteryId)
                             ->whereRaw("find_in_set('enabled', state)")
                             ->whereRaw("find_in_set('standard', state)")
                             ->first();
        } else if ($hallLevel == 6) { // 直播
            $result = Lottery::where('id', $lotteryId)
                             ->whereRaw("find_in_set('enabled', state)")
                             ->whereRaw("find_in_set('video', state)")
                             ->first();
        } else { // 房间
            $result = Lottery::where('id', $lotteryId)
                             ->whereRaw("find_in_set('enabled', state)")
                             ->whereRaw("find_in_set('chat', state)")
                             ->first();
        }
        if (empty($result)) {
            return false;
        }
        return $result;
    }

    /**
     * 验证彩期
     *
     * @param $lotteryId
     * @param $lotteryNumber
     * @param mixed $user
     *
     * @return array|bool]
     */
    protected function validLottery($lotteryId, $lotteryNumber, $user = false) {
        $result = LotteryInfo::getCacheCurrentPeriod($lotteryId);

        if (!empty($result) && $result['end_time'] > time()) {
            // $result = current($result);
        } else {
            $this->state = 70;
            return false;
        }

        //过去的期不能买
        if($lotteryNumber < $result['lottery_number']){
            $this->state = 71;
            return false;
        }

        //验证当前时间彩期与提交彩期是否一致
        //测试用户不校验彩期
        //允许下多期
        /*if ((!$user || $user->tags != 4) && $lotteryNumber != $result['lottery_number']) {
            $this->state = 71;
            $this->stateParams[] = $result['lottery_number'];
            return false;
        }*/

        return $result;
    }

    /**
     * 验证玩法
     *
     * @param  [type] $userId        [description]
     * @param  [type] $userName      [description]
     * @param  [type] $lotteryId     [description]
     * @param  [type] $lotteryPid    [description]
     * @param  [type] $lotteryNumber [description]
     * @param  [type] $origin        [description]
     * @param  [type] $hallId        [description]
     * @param  [type] $roomId        [description]
     * @param  [type] $logic         [description]
     * @param  [type] $play          [description]
     *
     * @return [type]                [description]
     */
    protected function validPlay($userId, $userName, $lotteryId, $lotteryPid, $lotteryNumber, $origin, $hallId, $roomId, $hallLevel, $logic, $play, $user_tags = 0) {
        $model = '';
        $this->db->getConnection()
                 ->enableQueryLog();

        if ($hallLevel == 4 || $hallLevel == 5) {
            // echo 2, PHP_EOL;
            $playData = LotteryPlayStruct::where('open', 1)
                                         ->whereIn('model', ['标准', '快捷'])
                                         ->where('play_id', $play['id'])
                                         ->first();
        } else if ($hallLevel == 6) {
            $playData = LotteryPlayStruct::where('open', 1)
                                         ->whereIn('model', ['直播'])
                                         ->where('play_id', $play['id'])
                                         ->first();
        } else {
            $playData = LotteryPlayStruct::where('open', 1)
                                         ->whereIn('model', ['聊天'])
                                         ->where('play_id', $play['id'])
                                         ->first();
        }


        if (empty($playData)) {
            $this->state = 72;
            return false;
        }

        $oddsData = LotteryPlayOdds::where('hall_id', $hallId)
                                   ->where('play_id', $play['id'])
                                   ->where('lottery_id', $lotteryId)
                                   ->orderBy('play_sub_id', 'asc')
                                   ->get();
        if (empty($oddsData)) {
            $this->state = 73;
            return false;
        }

        $oddsList = [];
        $oddsIdList = [];
        $oddsNameList = [];

        foreach ($oddsData as $val) {
            $oddsList[] = $val['odds'];
            $oddsIdList[] = $val['id'];
            $oddsNameList[$val['name']] = $val;
        }

        $logic->setCate($lotteryPid)
              ->setRules($playId = $play['id'], $odds = $oddsList, $bet = $play['times'], $betMoney = $play['price'], $playNumber = $play['num'])
              ->isValid();

        if (($bet = $logic->getBetCount()) == 0) {
            $this->state = 74;
            return false;
        }

        $r = $logic->getOrderMoney();
        if ($r[0]) {
            // $money[] = $r[1];
        } else {
            $this->state = 75;
            return false;
        }

        $showOdds = $logic->getShowOdds($play['id']);

        $orderTemp = [
            'user_id'        => $userId,
            'user_name'      => $userName,
            'tags'           => $user_tags,
            'order_number'   => LotteryOrder::generateOrderNumber(),
            'lottery_id'     => $lotteryId,
            'lottery_number' => $lotteryNumber,
            'pay_money'      => $r[1],
            'bet_num'        => $bet,
            'one_money'      => $play['price'],
            'room_id'        => $roomId,
            'origin'         => $origin,
            'times'          => $play['times'],
            // 'play_number' => $play['num'],
            'play_number'    => $logic->getPlayNumber(),
            'chase_amount'   => '0',
            'chase_number'   => '0',
            'state'          => $this->getState($playData['model']),
            'play_group'     => $playData['group'],
            'play_name'      => $playData['name'],
            'play_id'        => $play['id'],
            'odds'           => $showOdds,
            'odds_ids'       => $oddsIdList,
            'settle_odds'    => $oddsList,
        ];

        $logic->clear();
        return $orderTemp;
    }

    /**
     * 验证房间参数
     *
     * @param  [type] $lotteryId [description]
     * @param  [type] $roomId    [description]
     *
     * @return [type]            [description]
     */
    protected function validRoom($lotteryId, &$roomId, $origin) {

        if (isset($roomId) && !empty($roomId)) {
            $room = Room::where('id', $roomId)
                        ->where('lottery_id', $lotteryId)
                        ->first();

            if (empty($room)) {
                // $this->state = 87;
                return false;
            }

            $hallId = $room['hall_id'];

            $hall = Hall::where('id', $hallId)
                        ->first();

            $hallLevel = $hall['hall_level'];
        } else {
            // pc端 
            if ($origin == 1) {
                $hall = Hall::where('lottery_id', $lotteryId)
                            ->where('is_pc', 1)
                            ->where('hall_level', 4)
                            ->first();
                $hallLevel = 4;
            } else { // 移动端
                $hall = Hall::where('lottery_id', $lotteryId)
                            ->where('is_pc', 1)
                            ->where('hall_level', 5)
                            ->first();
                $hallLevel = 5;
            }

            if (empty($hall)) {
                // $this->state = 87;
                return false;
            }

            $room = Room::where('hall_id', $hall['id'])
                        ->where('lottery_id', $lotteryId)
                        ->first();
            if (!empty($room)) {
                $roomId = $room['id'];
            }
            $hallId = $hall['id'];
        }

        $this->hall = $hall;
        return [$hallId, $hallLevel];
    }

    /**
     * @param $lotteryId
     * @param $play
     * 校验购球数
     */
    protected function _validBuyBallNum($lotteryId, $play) {

        $lottery = Lottery::where('id', $lotteryId)
                          ->first();
        $struct = LotteryPlayStruct::where('open', 1)
                                   ->where('play_id', $play['id'])
                                   ->where('lottery_pid', $lottery['pid'])
                                   ->first();
        $buy_ball_num = $struct['buy_ball_num'];
        if ($buy_ball_num == 0) {
            return true;
        }
        if (strpos($play['num'], ',') === false) {
            $arr = explode('|', $play['num']);
            $length = count($arr);
            if ($length > $buy_ball_num) {
                return false;
            }
        } else {
            $arr = explode(',', $play['num']); //用,分割数组
            foreach ($arr as $key => $value) {
                $arr1 = explode('|', $value);
                $length = count($arr1);
                if ($length > $buy_ball_num) {
                    return false;
                }
            }
        }

        return true;


    }

    /**
     * 限额校验
     *
     * @param  [type] $lottery [description]
     * @param  [type] $orders  [description]
     *
     * @return [type]          [description]
     */
    protected function validPayMoney($lottery, $lotteryNumber, $userId, $hallId, $orders) {
        $perBetMax = $lottery['per_bet_max'];
        $allBetMax = $lottery['all_bet_max'];
        $allBet = 0;
        // 计算当前订单总额
        // if ($perBetMax > 0 || $allBetMax > 0) {
        $betMoney = 0;//本次投注金额
        $playDatas = [];
        foreach ($orders as $val) {
            if (!isset($playDatas[$val['play_id']])) {
                $playDatas[$val['play_id']] = 0;
            }
            $playDatas[$val['play_id']] += $val['pay_money'];
            $betMoney += $val['pay_money'];
        }
        // }

        // 判断当前彩期限额
        // if ($allBetMax > 0) {

        $allBet = LotteryOrderTemp::where('lottery_id', $lottery['id'])
                                  ->where('lottery_number', $lotteryNumber)
                                  ->sum('pay_money');
        if ($allBet + $betMoney > $allBetMax) {
            $this->state = 80;
            return false;
        }
        // }

        // 判断当前彩期个人限额
        if ($perBetMax > 0) {
            $perBet = LotteryOrderTemp::where('lottery_id', $lottery['id'])
                                      ->where('lottery_number', $lotteryNumber)
                                      ->where('user_id', $userId)
                                      ->sum('pay_money');
            if ($perBet + $betMoney > $perBetMax) {
                $this->state = 81;
                return false;
            }
        }


        // 判断当前期玩法投注限额
        $data = LotteryPlayLimitOdds::where('lottery_id', $lottery['id'])
                                    ->where('hall_id', $hallId)
                                    ->whereIn('play_id', array_keys($playDatas))
                                    ->get();
        $limitData = [];
        foreach ($data as $v) {
            $limitData[$v['play_id']] = $v;
        }


        foreach ($playDatas as $playId => $v) {
            if (isset($limitData[$playId]) && $limitData[$playId]['max_betting'] > 0 && $allBet + $v > $limitData[$playId]['max_betting']) {
                $this->state = 82;
                return false;
            }
        }

        // 判断当前厅最低进入和最高限额
        $hall = $this->hall;
        foreach ($orders as $v) {

            if (isset($_POST['chase']) && $_POST['chase']) {
                foreach ($_POST['chase']['chase_list'] ?? [] as $k1 => $v1) {
                    if ($v['pay_money'] * $v1['times'] < $hall['min_bet']) {
                        $this->state = 83;
                        $this->stateParams[] = $hall['min_bet'] / 100;
                        return false;
                    }

                    if ($v['pay_money'] * $v1['times'] > $hall['max_bet']) {
                        $this->state = 95;
                        $this->stateParams[] = $hall['max_bet'] / 100;
                        return false;
                    }
                }
            } else {
                if ($v['pay_money'] < $hall['min_bet']) {
                    $this->state = 83;
                    $this->stateParams[] = $hall['min_bet'] / 100;
                    return false;
                }

                if ($v['pay_money'] > $hall['max_bet']) {
                    $this->state = 95;
                    $this->stateParams[] = $hall['max_bet'] / 100;
                    return false;
                }
            }
        }

        // $sql = "select sum(pay_money) as pay_money from `lottery_order` where lottery_id = {$lottery['id']} and lottery_number = '{$lotteryNumber}' and  hall_id = {$hallId}";
        // $hallSumData = (int) current($db->row($sql));
        // if ($hallSumData + $betMoney > $hall['max_bet']) {
        //      $this->_error = '厅最高投注限制'.($hall['max_bet']/100).'元';
        //     return false;
        // }
        return true;
    }

    /**
     * 验证注单信息
     * @return boolean
     */
    protected function validChase($userId, $lotteryId, $lotteryNumber, $maxOdds, $orderPayMoney, $chase) {
        //验证追号类型
        if (!isset($chase['chase_type']) || !in_array($chase['chase_type'], [1, 2])) {
            $this->state = 89;
            return false;
        }

        // 从缓存校验追号期数
        $chaseLotteryInfo = LotteryInfo::getCacheNextPeriods($lotteryId, 100);
        if (empty($chaseLotteryInfo) || $chaseLotteryInfo[0]['lottery_number'] != $lotteryNumber) {
            $this->state = 88;
            return false;
        }

        $list1 = array_values(array_column($chase['chase_list'], 'lottery_number'));
        $list2 = array_values(array_column($chaseLotteryInfo, 'lottery_number'));
        $res = array_intersect($list2, $list1);
        if (count($list1) != count(array_unique($list1))) {
            $this->state = 90;
            return false;
        }

        if (count($list1) != count($res)) {
            $this->state = 93;
            return false;
        }

        $total = 0;
        $amount = 0;
        $times = 0;

        // if ($chase['chase_list'][0]['lottery_number'] != $lotteryNumber) {
        //     $this->_error = '追号第一期必须为当前期';
        //     return false;
        // }

        //对比期号
        foreach ($chase['chase_list'] as $k => $v) {
            $beforeLotteryNumber = (int)(isset($chase['chase_list'][$k - 1]) ? $chase['chase_list'][$k - 1]['lottery_number'] : 0);
            $lotteryNumber = (int)$v['lottery_number'];


            if ($beforeLotteryNumber > 0 && $lotteryNumber > 0 && $beforeLotteryNumber >= $lotteryNumber) {
                $this->state = 91; //排序异常
                return false;
            }

            /**
             * 必须设置倍数
             * 倍数只能是整数
             * 倍数只能是正数
             */
            if (!isset($v['times']) || intval($v['times']) != $v['times'] || $v['times'] <= 0) {
                $this->state = 92;  //追号彩期数据倍投异常
                return false;
            }

            //当期投入 = 注单总额度 *　当期倍数
            $count = $orderPayMoney * $v['times'];

            //统计总注数
            $amount += $amount * $v['times'];

            //累计投入 = 当期投入 +　上一期累计投入
            $total += $count;

            //奖金 = 赔率总和
            //盈利 = 最大赔率 * 当期倍数 - 累计投入
            // $profit = ($maxOdds * $v['times']) - $total;

            //盈利率 =  盈利 / 累计投入 * 100
            // $profit_percent = sprintf("%01.2f", ($maxOdds * $v['times']) / $total * 100);

            //当期金额
            $chase['chase_list'][$k]['price'] = $count;

            //当前投入
            $chase['chase_list'][$k]['current_bet'] = $count;

            //累计投入
            $chase['chase_list'][$k]['increment_bet'] = $total;

            //奖金
            // $chase['chase_list'][$k]['reward'] = $maxOdds * $v['times'];
            $chase['chase_list'][$k]['reward'] = 0;

            //盈利
            $chase['chase_list'][$k]['profit'] = 0;

            //盈利率
            // $chase['chase_list'][$k]['profit_percent'] = $profit_percent;

            //追号类型
            $chase['chase_list'][$k]['chase_type'] = $chase['chase_type'];

            //追号名称
            $chase['chase_list'][$k]['chase_name'] = isset($chase['chase_name']) ? $chase['chase_name'] : '';

            //open_status
            $chase['chase_list'][$k]['open_status'] = 0;

            if ($k > 0) {
                $chase['chase_list'][$k]['state'] = 'default';
            } else {
                $chase['chase_list'][$k]['state'] = 'created';
            }

            $chase['chase_list'][$k]['user_id'] = $userId;

            //统计追号总倍数
            $times += $v['times'];

        }
        // $info_list = $list;
        $chaseAmount = count($list1);

        //追号总金额,替换原来总金额
        // $total = $total;

        //追号总注数,替换原来值
        $totalBet = $amount;

        return compact('chaseAmount', 'total', 'totalBet', 'times', 'chase');
    }

    /**
     * 拼接字符串
     *
     * @param  [type] $lotteryName [description]
     * @param  [type] $orderNumber [description]
     * @param  [type] $playName    [description]
     * @param  [type] $playNumber  [description]
     *
     * @return [type]              [description]
     */
    protected function getOrderLogStr($lotteryName, $orderNumber, $group, $playName, $playNumber) {
        //拼接备注
        return $this->lang->text("Bet").':' . $lotteryName . '-' . $orderNumber . '-' . $group . '-' . $playName . '-' . $playNumber;
    }

    protected function getChaseLogStr($lotteryName, $orderNumber, $chaseNumber) {
        //拼接备注
        //return '追号:' . $lotteryName . '-追单号:' . $chaseNumber . '-期号:' . $orderNumber;
        return $this->lang->text("Chase number: %s - chase number: %s - period number: %s", [$lotteryName, $chaseNumber, $orderNumber]);
    }

    public function cancel($id) {
        try {
            $this->db->getConnection()
                     ->beginTransaction();
            $wallet = new Wallet($this->ci);
            $order = LotteryOrder::where('id', $id)
                                 ->lockForUpdate()
                                 ->first();

            if (empty($order)) {
                $this->db->getConnection()
                         ->rollback();
                return $this->lang->set(203, [], [], ['id' => $id]);
            }

            if ($order['chase_number'] > 0) {
                $this->db->getConnection()
                         ->rollback();
                return $this->lang->set(207);
            }

            $user = User::where('id', $order['user_id'])
                        ->first();
            $funds = Funds::where('id', $user['wallet_id'])
                          ->lockForUpdate()
                          ->first();

            $status = explode(',', $order['state']);
            if (count($status) && (in_array('open', $status) || strpos(implode(',', $status), 'canceled') || strpos(implode(',', $status), 'auto_canceled') || strpos(implode(',', $status), 'system_canceled'))) {
                $this->db->getConnection()
                         ->rollback();
                return $this->lang->set(205);
            }

            $lotteryData = LotteryInfo::where('lottery_number', $order['lottery_number'])
                                      ->where('lottery_type', $order['lottery_id'])
                                      ->first();

            $s = 'canceled';
            $time = time();
            if ($lotteryData) {
                if ($time < $lotteryData['end_time']) {
                    $s = 8;   //手动撤单
                } else if ($time >= $lotteryData['end_time'] + 5 * 60 && !$lotteryData['state']) {
                    $s = 8;   //系统撤单  彩期结束，但是官方开奖号码没有 32
                } else if ($time >= $lotteryData['end_time'] + 5 * 60) {
                    $s = 8;   //手动撤单
                } else {
                    $this->db->getConnection()
                             ->rollback();
                    return $this->lang->set(206);
                }
            }

            LotteryOrder::where('id', $id)
                        ->update([
                            'state' => DB::raw("state|$s"),
                        ]);

            // 删除临时表数据
            LotteryOrderTemp::where('id', $id)
                            ->delete();

            $wallet->crease($user['wallet_id'], $order['pay_money']);
            $memo = "注单号：{$order['order_number']}";
            FundsDealLog::create([
                "user_id"       => $order['user_id'],
                "username"      => $order['user_name'],
                "order_number"  => $order['order_number'],
                "deal_type"     => FundsDealLog::TYPE_CANCEL_ORDER,
                "deal_category" => FundsDealLog::CATEGORY_INCOME,
                "deal_money"    => $order['pay_money'],
                "balance"       => $funds['balance'] + $order['pay_money'],
                "memo"          => $memo,
                "wallet_type"   => FundsDealLog::WALLET_TYPE_PRIMARY,
            ]);

            $this->db->getConnection()
                     ->commit();
        } catch (\Exception $e) {
            $this->db->getConnection()
                     ->rollback();
            return $this->lang->set(203, [], [], ['error' => $e->getMessage()]);
        }
    }

    /**
     * 泰国彩票插入数字
     * @param $userId
     * @param $lotteryId
     * @param $lotteryNumber
     * @param $number
     * @return mixed
     */
    public function addLotteryNumber($userId, $user_account, $lotteryId, $lotteryNumber, $number)
    {
        $time = time();
        $redis_key = "insertLotteryNumberLock:{$userId}.{$lotteryId}.{$lotteryNumber}";
        $openCodeLock_key = CacheKey::$perfix['openCodeLock'].$lotteryId.'_'.$lotteryNumber;

        try{
            //禁止频繁操作 限定最快10秒一次
            if($this->redis->get($redis_key)){
                throw new \Exception('The rate is too fast. Please operate later');
            }

            $where = [
                'lottery_number' => $lotteryNumber,
                'lottery_id'     => $lotteryId,
            ];
            $lottery_info = LotteryInfo::getLotteryInfo($where);
            if(!$lottery_info){
                throw new \Exception('Lottery period does not exist');
            }

            //加锁了，不让用户插数字了
            if($this->redis->get($openCodeLock_key)){
                throw new \Exception('The Lottery period has ended !');
            }

            //已超过结束时间+90秒
            if($time - $lottery_info['end_time'] >= 90){
                throw new \Exception('The Lottery period has ended');
            }

            $data = [
                'uid'           => $userId,
                'user_account'  => $user_account,
                'lottery_id'    => $lotteryId,
                'lottery_number'=> $lotteryNumber,
                'number'        => $number,
                'time'          => date('m/d/Y H:i:s', $time),
            ];
            InsertNumber::insertNumber($data);
            $this->redis->setex($redis_key,10,1);
            return $this->lang->set(0);
        }catch (\Exception $e){
            return $this->lang->set(886, [$e->getMessage()]);
        }
    }

    /**
     * 取state状态
     *
     * @param  [type] $text [description]
     *
     * @return [type]       [description]
     */
    protected function getState($text) {
        switch ($text) {
            case '聊天':
                return 'chat';
                break;

            case '直播':
                return 'video';
                break;

            case '快捷':
                return 'fast';
                break;

            case '标准':
            default:
                return 'std';
        }
    }



}