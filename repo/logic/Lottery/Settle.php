<?php

namespace Logic\Lottery;

use Logic\GameApi\GameApi;
use Logic\Set\SystemConfig;
use \Logic\Wallet\Wallet;
use Model\LotteryChaseOrder;
use Model\LotteryChaseOrderSub;
use Model\LotteryOrder;
use Model\LotteryTrialChaseOrder;
use Model\LotteryTrialChaseOrderSub;
use Model\LotteryTrialOrder;
use Model\User;

/**
 * 结算模块
 */
class Settle extends \Logic\Logic
{

    /**
     * 补结算v2
     * @return [type] [description]
     */
    public function runReopenV2()
    {
        $db = $this->db->getConnection();

        $rs = $db->table('lottery_order_temp')
                 ->leftjoin(
                     'lottery_info', function ($join) {
                     $join->on('lottery_order_temp.lottery_id', '=', 'lottery_info.lottery_type')
                          ->on('lottery_order_temp.lottery_number', '=', 'lottery_info.lottery_number');
                 }
                 )
                 ->whereRaw("!FIND_IN_SET('open', lottery_order_temp.state)")
                 ->whereRaw("!FIND_IN_SET('winning', lottery_order_temp.state)")
                 ->whereRaw("!FIND_IN_SET('canceled', lottery_order_temp.state)")
                 ->whereRaw("(lottery_info.end_time + 300 ) < NOW()")
                 ->where('lottery_info.period_code', '!=', '')
                 ->select(
                     [
                         $db->raw('lottery_order_temp.*'),
                         'lottery_info.lottery_number',
                         'lottery_info.official_time',
                         'lottery_info.lottery_type',
                         'lottery_info.period_code',
                         'lottery_info.pid',
                     ]
                 )
                 ->orderby('lottery_order_temp.id', 'desc')
                 ->take(2000)
                 ->get();

        list($maxSendPrize, $maxOdds) = $this->getConfig();
        // $logic = new \LotteryPlay\Logic;
        $this->logger->debug('【结算准备补】:' . count($rs) . '个');
        $data = [];
        foreach ($rs ?? [] as $v) {
            $data[] = $this->runSingle((array)$v, (array)$v, $maxSendPrize, $maxOdds);
        }

    }

    /**
     * 消息通知结算入口V2
     *
     * @param array ['lottery_number' => $lotteryNumber, 'lottery_type' => $v2['lottery_type'], 'period_code' => $openCode]
     *
     * @return [type] [description]
     */
    public function runByNotifyV2($lotteryId, $lotteryNumber, $runMode = 'sendPrize', $lotteryMsg = null)
    {
        if ($lotteryMsg)
            $lotteryInfo = $lotteryMsg;
        else
            $lotteryInfo = \Model\LotteryInfo::where('lottery_number', $lotteryNumber)
                                             ->where('lottery_type', $lotteryId)
                                             ->first();

        if (empty($lotteryInfo)) {
            $this->logger->debug('彩期不存在', compact('lotteryId', 'lotteryNumber'));
            return false;
        }

        if (empty($lotteryInfo['period_code']) && $runMode == 'sendPrize') {
            $this->logger->debug('还没有开出结果 ', compact('lotteryId', 'lotteryNumber'));
            return false;
        }

        // 增加防重复运算逻辑
        $allowRun = $this->redis->get(\Logic\Define\CacheKey::$perfix['settleNotify'] . $lotteryId);
        list($maxSendPrize, $maxOdds) = $this->getConfig();

        if(empty($allowRun) || $runMode != 'sendPrize') {
            $this->redis->set(\Logic\Define\CacheKey::$perfix['settleNotify'] . $lotteryId,'running');
            $this->redis->expire(\Logic\Define\CacheKey::$perfix['settleNotify'],2);
            //$this->redis->setex(\Logic\Define\CacheKey::$perfix['settleNotify'] . $lotteryId, 2, 'running');
            // 查询该期追号单
           /* $list = LotteryChaseOrder::where('lottery_id', $lotteryId)
                                     ->where('state', '!=', 'complete')
                                     ->get()
                                     ->toArray();
            if($list){
                foreach ($list as $v) {
                    $chase = (array)\DB::table('lottery_chase_order_sub_temp')
                        ->where('chase_number', $v['chase_number'])
                        ->where('lottery_number', $lotteryNumber)
                        ->first();

                    if ($chase) {       //就否有当期未生成的订单，有则插入追号临时表
                        $chase['play_id']       = $v['play_id'];
                        $chase['lottery_id']    = $v['lottery_id'];
                        $chase['hall_id']       = $v['hall_id'];
                        $chase['user_id']       = $v['user_id'];
                        $chase['user_name']     = $v['user_name'];
                        $chase['chase_number']  = $v['chase_number'];
                        $chase['chase_type']    = $v['chase_type'];
                        $chase['one_money']     = $v['one_money'] * $v['times'];         //结算需要的数据与存储的数据有出入
                        $chase['bet_num']       = $v['bet_num'];
                        $chase['chase']         = 1;
                        $chase['chase_state']   = $v['state'];
                        $chase['chase_type']    = $v['chase_type'];
                        $chase['order_number']  = LotteryOrder::generateOrderNumber();;
                        $chase['function']      = 'lotteryChase';
                        //调用指定方法 更新数据
                        $tmp                    = $this->runSingle($lotteryInfo, $chase, $maxSendPrize, $maxOdds, $runMode);
                        $tmp['pay_money']       = $chase['pay_money'];
                        $tmp['user_id']         = $v['user_id'];
                        $data[]                 = $tmp;
                    }
                }
            }*/

            // 查询该期订单 也就取了2千条
            $orderData = \Model\LotteryOrderTemp::where('lottery_id', $lotteryInfo['lottery_type'])
                                                ->where('lottery_number', $lotteryInfo['lottery_number'])
                                                ->take(2000)
                                                ->get();
            $data = [];
            foreach ($orderData ?? [] as $order) {
                $tmp              = $this->runSingle($lotteryInfo, $order, $maxSendPrize, $maxOdds, $runMode);
                $tmp['pay_money'] = $order['pay_money'];
                $tmp['user_id']   = $order['user_id'];
                $data[]           = $tmp;
            }

            $this->redis->del(\Logic\Define\CacheKey::$perfix['settleNotify'] . $lotteryId);
            return $data;
        } else {
            $this->logger->debug('别外的进程正在锁定处理 lottery_type:' . $lotteryId);
        }
    }

    /**
     * 试玩消息通知结算入口V2
     *
     * @param array ['lottery_number' => $lotteryNumber, 'lottery_type' => $v2['lottery_type'], 'period_code' => $openCode]
     *
     * @return [type] [description]
     */
    public function runByTrialNotifyV2($lotteryId, $lotteryNumber)
    {

        $lotteryInfo = \Model\LotteryInfo::where('lottery_number', $lotteryNumber)
                                         ->where('lottery_type', $lotteryId)
                                         ->first();

        if (empty($lotteryInfo)) {
            $this->logger->debug('彩期不存在', compact('lotteryId', 'lotteryNumber'));
            return false;
        }

        if (empty($lotteryInfo['period_code'])) {
            $this->logger->debug('还没有开出奖果 ', compact('lotteryId', 'lotteryNumber'));
            return false;
        }

        // 增加防重复运算逻辑
        $allowRun = $this->redis->get(\Logic\Define\CacheKey::$perfix['settleNotify'] . '_try_play_' . $lotteryId);
        if (empty($allowRun)) {
            $this->redis->set(\Logic\Define\CacheKey::$perfix['settleNotify'] . '_try_play_' . $lotteryId,'running');
            $this->redis->expire(\Logic\Define\CacheKey::$perfix['settleNotify'] . '_try_play_' . $lotteryId,2);
            /*$this->redis->setex(
                \Logic\Define\CacheKey::$perfix['settleNotify'] . '_try_play_' . $lotteryId, 2, 'running'
            );*/
            list($maxSendPrize, $maxOdds) = $this->getConfig();

            // 查询该期追号单
            $list = LotteryTrialChaseOrder::where('lottery_id', $lotteryId)
                                          ->get()
                                          ->toArray();
            foreach ($list as $v) {
                $chase = (array)\DB::table('lottery_trial_chase_order_sub_temp')
                                   ->where('chase_number', $v['chase_number'])
                                   ->where('lottery_number', $lotteryNumber)
                                   ->first();

                if ($chase) {  //就否有当期未生成的订单，有则插入追号临时表
                    $chase['play_id'] = $v['play_id'];
                    $chase['lottery_id'] = $v['lottery_id'];
                    $chase['hall_id'] = $v['hall_id'];
                    $chase['user_id'] = $v['user_id'];
                    $chase['user_name'] = $v['user_name'];
                    $chase['chase_number'] = $v['chase_number'];
                    $chase['chase_type'] = $v['chase_type'];
                    $chase['bet_num'] = $v['bet_num'];
                    $chase['one_money'] = $v['one_money'] * $v['times'];          //结算需要的数据与存储的数据有出入
                    $chase['chase'] = 1;
                    $chase['chase_state'] = $v['state'];
                    $chase['order_number'] = LotteryTrialOrder::generateOrderNumber();;
                    $chase['function'] = 'lotteryTrialChase';        //调用指定方法 更新数据
                    $tmp = $this->runTrialSingle($lotteryInfo, $chase, $maxSendPrize, $maxOdds);
                    $tmp['pay_money'] = $chase['pay_money'];
                    $tmp['user_id'] = $v['user_id'];
                    $data[] = $tmp;
                }
            }

            // 查询该期订单
            $orderData = \Model\LotteryTrialOrderTemp::where('lottery_id', $lotteryInfo['lottery_type'])
                                                     ->where('lottery_number', $lotteryInfo['lottery_number'])
                                                     ->take(2000)
                                                     ->get();

            // $logic = new \LotteryPlay\Logic;
            $data = [];
            foreach ($orderData ?? [] as $order) {
                // $this->logger->debug($order['order_number']);
                $tmp = $this->runTrialSingle($lotteryInfo, $order, $maxSendPrize, $maxOdds);
                $tmp['pay_money'] = $order['pay_money'];
                $tmp['user_id'] = $order['user_id'];
                $data[] = $tmp;
            }

            return $data;
        } else {
            $this->logger->debug('别外的进程正在锁定处理 lottery_type:' . $lotteryId);
        }
    }

    /**
     * 消息通知结算入口
     *
     * @param array ['lottery_number' => $lotteryNumber, 'lottery_type' => $v2['lottery_type'], 'period_code' => $openCode]
     *
     * @return [type] [description]
     */
    public function runByNotify($lotteryId, $lotteryNumber)
    {
        $lotteryInfo = \Model\LotteryInfo::where('lottery_number', $lotteryNumber)
                                         ->where('lottery_type', $lotteryId)
                                         ->first();

        if (empty($lotteryInfo)) {
            $this->logger->info('彩期不存在', compact('lotteryId', 'lotteryNumber'));
            return false;
        }

        if (empty($lotteryInfo['period_code'])) {
            $this->logger->info('还没有开出奖果 ', compact('lotteryId', 'lotteryNumber'));
            return false;
        }

        // 增加防重复运算逻辑
        $allowRun = $this->redis->get(\Logic\Define\CacheKey::$perfix['settleNotify'] . $lotteryId);

        if (empty($allowRun)) {
            $this->redis->setex(\Logic\Define\CacheKey::$perfix['settleNotify'] . $lotteryId, 10, 'running');

            // 查询该期订单
            $orderData = \Model\LotteryOrder::where('lottery_id', $lotteryInfo['lottery_type'])
                                            ->where('lottery_number', $lotteryInfo['lottery_number'])
                                            ->whereRaw("!FIND_IN_SET('open', `state`)")
                                            ->whereRaw("!FIND_IN_SET('canceled', `state`)")
                                            ->whereRaw("!FIND_IN_SET('auto_canceled', `state`)")
                                            ->whereRaw("!FIND_IN_SET('system_canceled', `state`)")
                                            ->get();

            list($maxSendPrize, $maxOdds) = $this->getConfig();
            // $logic = new \LotteryPlay\Logic;
            foreach ($orderData ?? [] as $order) {
                $this->logger->info($order['order_number']);
                $this->runSingle($lotteryInfo, $order, $maxSendPrize, $maxOdds);
            }

            $this->redis->del(\Logic\Define\CacheKey::$perfix['settleNotify'] . $lotteryId);
        } else {
            $this->logger->info('别外的进程正在锁定处理 lottery_type:' . $lotteryId);
        }
    }

    /**
     * 补结算
     * @return [type] [description]
     */
    public function runReopen()
    {
        $db = $this->db->getConnection();

        $rs = $db->table('lottery_order')
                 ->leftjoin(
                     'lottery_info', function ($join) {
                     $join->on('lottery_order.lottery_id', '=', 'lottery_info.lottery_type')
                          ->on('lottery_order.lottery_number', '=', 'lottery_info.lottery_number');
                 }
                 )
                 ->whereRaw("!FIND_IN_SET('open', lottery_order.state)")
                 ->whereRaw("!FIND_IN_SET('winning', lottery_order.state)")
                 ->whereRaw("!FIND_IN_SET('canceled', lottery_order.state)")
                 ->whereRaw("(lottery_info.end_time + 300 ) < NOW()")
                 ->where('lottery_info.period_code', '!=', '')
                 ->select(
                     [
                         $db->raw('lottery_order.*'),
                         'lottery_info.lottery_number',
                         'lottery_info.lottery_type',
                         'lottery_info.period_code',
                         'lottery_info.pid',
                     ]
                 )
                 ->get();

        list($maxSendPrize, $maxOdds) = $this->getConfig();
        // $logic = new \LotteryPlay\Logic;
        $this->logger->info('【结算准备补】:' . count($rs) . '个');
        foreach ($rs ?? [] as $v) {
            $this->runSingle((array)$v, (array)$v, $maxSendPrize, $maxOdds);
        }
    }

    /**
     * 计算订单
     *
     * @param  [type] $lotteryInfo [description]
     *
     * @return [type]              [description]
     */
    public function calOrder($lotteryInfo)
    {
        $data = $this->runByNotifyV2(
            $lotteryInfo['lottery_type'], $lotteryInfo['lottery_number'], 'test', $lotteryInfo
        );
        $earnMoney = 0;
        $payMoney = 0;
        $totalWinCount = 0;
        $totalWinMin = 999999;
        $totalWinMax = 0;
        $totalWinUsers = [];
        foreach ($data as $order) {
            $payMoney += $order['pay_money'];
            $earnMoney += $order['money'];
            if ($order['money'] > 0) {
                $totalWinCount++;
                $totalWinUsers[] = $order['user_id'];
                if ($totalWinMin > $order['money'])
                    $totalWinMin = $order['money'];
                if ($totalWinMax < $order['money'])
                    $totalWinMax = $order['money'];
            }
        }
        return [
            'orderCount'    => count($data),
            'totalTou'      => $payMoney,
            'totalWin'      => $earnMoney,
            'totalWinMax'   => $totalWinMax,
            'totalWinMin'   => $totalWinMin == 999999 ? 0 : $totalWinMin,
            'totalWinCount' => $totalWinCount,
            'totalWinUsers' => count(array_unique($totalWinUsers)),
            'prizeProfit'   => $payMoney ? round($earnMoney / $payMoney * 100, 2) : 0,
            'totalIncome'   => ($payMoney - $earnMoney),
        ];
    }

    /**
     * test order
     *
     * @param  [type] $orderNumber [description]
     * @param  string $lotteryInfo [description]
     *
     * @return [type]              [description]
     */
    public function testOrder($orderNumber, $lotteryInfo = '')
    {
        $earnMoney = 0;
        $payMoney = 0;
        list($maxSendPrize, $maxOdds) = $this->getConfig();
        // $logic = new \LotteryPlay\Logic;
        // 查询该期订单
        $orderData = \Model\LotteryOrder::where('order_number', $orderNumber)
                                        ->get();

        if (empty($lotteryInfo)) {
            $lotteryInfo = \Model\LotteryInfo::where('lottery_number', $orderData[0]['lottery_number'])
                                             ->where('lottery_type', $orderData[0]['lottery_id'])
                                             ->first();
        }

        foreach ($orderData ?? [] as $order) {
            $order = $this->runSingle($lotteryInfo, $order, $maxSendPrize, $maxOdds, $runMode = 'test');
            $payMoney += $order['pay_money'];
            $earnMoney += $order['money'];
            // print_r($order->toArray());
        }
        return [
            'orderCount'  => $orderData->count(),
            'totalTou'    => $payMoney,
            'totalWin'    => $earnMoney,
            'totalIncome' => ($payMoney - $earnMoney),
        ];
    }

    /**
     * 运算单个订单
     *
     * @param $lotteryInfo
     * @param $order
     * @param $maxSendPrize
     * @param $maxOdds
     * @param string $runMode 运行模式 sendPrize 派彩 | test 返回派彩信息，不实际派彩
     *
     * @return mixed
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function runSingle($lotteryInfo, $order, $maxSendPrize, $maxOdds, $runMode = 'sendPrize')
    {
        $logic      = new \LotteryPlay\Logic();
        // $this->logger->error('runSingle', $lotteryInfo);
        $settleOdds = json_decode($order['settle_odds'], true);
        $settleOdds = $this->filterMaxOdds($settleOdds, $maxOdds);

        // $logic->clear();
        //获取 最优自开奖未有该参数
        $lotteryInfo['official_time'] = isset($lotteryInfo['official_time']) ? $lotteryInfo['official_time'] : time();

        $logic->setCate($lotteryInfo['pid'])
              ->setPeriodCode($lotteryInfo['period_code'])
              ->setRules(
                  $playId       = $order['play_id'],
                  $odds         = $settleOdds,
                  $bet          = $order['times'],
                  $betMoney     = $order['one_money'],
                  $playNumber   = $order['play_number'],
                  $maxSendPrize,
                  $openTime     = date('Y-m-d', $lotteryInfo['official_time']) //六合彩开奖结果与开奖时间有关
              );

        $logic->isValid(true);
        $result = $logic->run();

        if (!$result[0]) {
            // $this->logger->debug('Error:');
            echo 'Error', PHP_EOL;
            print_r(
                [
                    $playId     = $order['play_id'],
                    $odds       = $settleOdds,
                    $bet        = $order['times'],
                    $betMoney   = $order['one_money'],
                    $playNumber = $order['play_number'],
                    $maxSendPrize,
                ]
            );
            print_r($logic->getErrors());

            return false;
        }

        $order['period_code'] = $lotteryInfo['period_code'];
        $order['state']       = isset($order['state']) ? $order['state'] : '';
        $order['state']       = $this->addState($order['state'], 'open');
        $order['money']       = 0;

        if ($logic->isWin()) {
            $order['state'] = $this->addState($order['state'], 'winning'); //中奖标志

            // 28类 特殊大小单双 13 14
            if ($lotteryInfo['pid'] == 1 && $order['play_id'] == 151 &&
                in_array($sum = $logic->getPeriodCodeSum(), [13, 14])) {
                $oddsIndex = [];
                $oddsIndex['大或双开14'] = [0, 3];
                $oddsIndex['小或单开13'] = [1, 2];
                $oddsIndex['小单开13'] = [4];
                $oddsIndex['大双开14'] = [7];

                $specialData = \Model\Pc28special::where('lottery_id', $order['lottery_id'])
                                                 ->where('hall_id', $order['hall_id'])
                                                 ->get();

                $payMoney = \Model\LotteryOrder::where('user_id', $order['user_id'])
                                               ->where('lottery_id', $order['lottery_id'])
                                               ->where('lottery_number', $order['lottery_number'])
                                               ->where('play_id', 151)
                                               ->where('hall_id', $order['hall_id'])
                                               ->selectRaw("SUM(pay_money) as pay_money")
                                               ->first();

                $betAmount = $payMoney->pay_money / 100;

                if (!empty($specialData)) {
                    foreach ($specialData as $special) {
                        if (isset($oddsIndex[$special['type']])) {
                            // 覆盖赔率
                            if ($betAmount < $special['step1']) {
                                // echo 'a0', PHP_EOL;
                                foreach ($oddsIndex[$special['type']] as $index) {
                                    $odds[$index] = $special['odds'];
                                }
                            } else if ($betAmount >= $special['step1'] && $betAmount <= $special['step2']) {
                                // echo 'a1', PHP_EOL;
                                foreach ($oddsIndex[$special['type']] as $index) {
                                    $odds[$index] = $special['odds1'];
                                }
                            } else {
                                // echo 'a2', PHP_EOL;
                                foreach ($oddsIndex[$special['type']] as $index) {
                                    $odds[$index] = $special['odds2'];
                                }
                            }
                        }
                    }

                    $odds = $this->filterMaxOdds($odds, $maxOdds);
                    $logic = new \LotteryPlay\Logic;
                    $logic->setCate($lotteryInfo['pid'])
                          ->setPeriodCode($lotteryInfo['period_code'])
                          ->setRules(
                              $playId = $order['play_id'],
                              $odds,
                              $bet = $order['times'],
                              $betMoney = $order['one_money'],
                              $playNumber = $order['play_number'],
                              $maxSendPrize
                          );

                    $logic->isValid(true);
                    $result = $logic->run();
                }
            }

            $order['money'] = $result[1];
        }

        $order['lose_earn'] = $order['money'] - $order['pay_money'];

        if ($logic->isWin()) {
            $order['state'] = $this->addState($order['state'], 'winning'); //中奖标志
        }

        $order['rebet']         = 0;
        $order['odds_id']       = 0;
        $order['valid_bet']     = $order['pay_money'];
        $order['earn_money']    = $order['bet_num'] * $order['one_money'] * max(json_decode($order['odds'], true));
        $order['win_bet_count'] = $logic->showWinBetCount();

        // 判断是否运行派奖模式
        if ($runMode == 'sendPrize') {
            try {
                $this->db->getConnection()
                         ->beginTransaction();

                if ($this->db->getConnection()
                             ->transactionLevel()) {

                    $user = \Model\User::where('id', $order['user_id'])
                                       ->first();

                    \Model\Funds::where('id', $user['wallet_id'])
                                ->lockForUpdate()
                                ->first();

                    if (isset($order['function']) && method_exists($this, $order['function'])) {
                        $function = $order['function'];
                        $rs       = $this->$function($order, $user);
                    } else {
                        $rs = $this->lotteryOrder($order);
                    }

                    if ($rs) {
                        // 中奖派钱
                        if ($logic->isWin()) {
                            $wallet = new Wallet($this->ci);

                            //金额结算，往主钱包里加钱
                            $wallet->addMoney(
                                $user,
                                $order['order_number'],
                                $order['money'],
                                2, //交易类型：派奖
                                $order['order_number'], //备注为订单号
                                $order['pay_money']//投注金额
                            );
                        } else {
                            //未中奖需写打码量流水
                            $wallet = new Wallet($this->ci);

                            //金额结算，往主钱包里加钱
                            $wallet->addMoney(
                                $user,
                                $order['order_number'],
                                0,
                                400, //交易类型：派奖
                                $order['order_number'], //备注为订单号
                                $order['pay_money']//投注金额
                            );
                        }

                        // 写入派奖数据
                        \Model\SendPrize::create(
                            [
                                'user_id'      => $order['user_id'],
                                'user_name'    => $order['user_name'],
                                'bet_number'   => $order['bet_num'],
                                'order_number' => $order['order_number'],
                                'odds_id'      => $order['odds_id'],
                                'bet_type'     => $order['lottery_id'],
                                'pay_money'    => $order['pay_money'],
                                'earn_money'   => $order['earn_money'],
                                'money'        => $order['money'],
                                'rebet'        => $order['rebet'],
                                'lose_earn'    => $order['lose_earn'],
                                'status'       => 'normal',
                            ]
                        );


                        $this->db->getConnection()
                                 ->commit();

                        // 通知用户层级是否变更
                        $level_msg = ['user_id' => $order['user_id']];
                        \Utils\MQServer::send('user_level_upgrade', $level_msg);
                        //拉取订单其它后续 逻辑 处理通知
                        \Utils\MQServer::send(GameApi::$synchronousOrderCallback.'_'.$order['lottery_id'], [
                            'user_id' => $order['user_id'],
                            'game' => 'CP',
                            'order_number' => $order['order_number'],
                            'game_type' => 'ZYCPSTA',
                            'type_name' => $this->lang->text('Self operated lottery'),
                            'game_id' => 27,
                            'server_id' => 0,
                            'account' => $order['user_name'],
                            'bet' => $order['pay_money'],
                            'profit' => $order['lose_earn'],
                            'date' => $order['created'],
                        ]);

                    } else {
                        $this->db->getConnection()
                                 ->rollback();
                    }
                }
            } catch (\Exception $e) {
                $this->logger->debug('Error:');
                $this->logger->debug($e->getMessage());

                print_r(
                    [
                        $playId         = $order['play_id'],
                        $odds           = $settleOdds,
                        $bet            = $order['times'],
                        $betMoney       = $order['one_money'],
                        $playNumber     = $order['play_number'],
                        $maxSendPrize,
                    ]
                );

                print_r($logic->getErrors());
                print_r($e->getMessage());

                $this->db->getConnection()
                         ->rollback();
            }
        }
        return $order;
    }

    /**
     * 取消追号
     *
     * @param $chaseNumber
     * @param $user_id
     *
     * @return bool|void
     * @throws \Exception
     */
    public function cancelChaseOrder($chaseNumber, $user_id)
    {
        $user = User::find($user_id);
        $result = \DB::table('lottery_chase_order_sub')
                     ->where('chase_number', $chaseNumber)
                     ->where('state', '=', 'default')
                     ->first([\DB::raw('SUM(pay_money) AS sum_money'), \DB::raw('COUNT(1) AS total')]);

        if ($result->sum_money == 0 || $result->total == 0) {
            return;
        }
        // 更新追号订单
        $r1 = \DB::table('lottery_chase_order_sub')
                 ->where('chase_number', $chaseNumber)
                 ->where('state', '=', 'default')
                 ->update(['state' => 'cancel']);

        //$memo = "停止追号-追单号:" . $chaseNumber . "-取消总期:" . $result->total;
        $memo = $this->lang->text("Stop chasing No. - Chase No.: %s - cancel total period: %s", [$chaseNumber, $result->total]);
        $wallet = new Wallet($this->ci);
        // 钱包
        $funds = \Model\Funds::where('id', $user->wallet_id)
                             ->first();
        $r3 = $wallet->crease($user->wallet_id, $result->sum_money);
        //流水里面添加打码量可提余额等信息
        $dml = new \Logic\Wallet\Dml($this->ci);
        $dmlData = $dml->getUserDmlData($user->id);
        $r4 = \Model\FundsDealLog::create(
            [
                "user_id"           => $user_id,
                "user_type"         => 1,
                "username"          => $user->name,
                "order_number"      => $chaseNumber,
                "deal_type"         => \Model\FundsDealLog::TYPE_CANCEL_ORDER,
                "deal_category"     => \Model\FundsDealLog::CATEGORY_INCOME,
                "deal_money"        => $result->sum_money,
                "balance"           => intval($funds['balance'] + $result->sum_money),
                "memo"              => $memo,
                "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => $dmlData->total_bet,
                'withdraw_bet'      => 0,
                'total_require_bet' => $dmlData->total_require_bet,
                'free_money'        => $dmlData->free_money,
            ]
        );

        return true;
    }

    /**
     * 取消试玩追号
     *
     * @param $chaseNumber
     * @param $user_id
     */
    public function trialOrderCancel($chaseNumber, $user_id)
    {
        $user = \DB::table('trial_user')
                   ->find($user_id);
        if ($user) {
            $result = \DB::table('lottery_trial_chase_order_sub')
                         ->where('chase_number', $chaseNumber)
                         ->where('state', '=', 'default')
                         ->first([\DB::raw('SUM(pay_money) AS sum_money'), \DB::raw('COUNT(1) AS total')]);

            if ($result->sum_money == 0 || $result->total == 0) {
                return;
            }
            // 更新追号订单
            $r1 = \DB::table('lottery_trial_chase_order_sub')
                     ->where('chase_number', $chaseNumber)
                     ->where('state', '=', 'default')
                     ->update(['state' => 'cancel']);

            //$memo = "停止追号-追单号:" . $chaseNumber . "-取消总期:" . $result->total;
            $memo = $this->lang->text("Stop chasing No. - Chase No.: %s - cancel total period: %s", [$chaseNumber, $result->total]);
            \DB::table('trial_funds')
               ->where('id', $user->wallet_id)
               ->update(
                   [
                       'balance_before' => \DB::raw('balance'),
                       'balance'        => \DB::raw("balance + {$result->sum_money}"),
                   ]
               );
            //            $funds = (array)\DB::table('trial_funds')->where('id', $user->wallet_id)->first();
            //流水里面添加打码量可提余额等信息
            $r4 = \DB::table('funds_trial_deal_log')
                     ->insert(
                         [
                             "user_id"       => $user_id,
                             "user_type"     => 1,
                             "username"      => $user->name,
                             "order_number"  => $chaseNumber,
                             "deal_type"     => \Model\FundsDealLog::TYPE_CANCEL_ORDER,
                             "deal_category" => \Model\FundsDealLog::CATEGORY_INCOME,
                             "deal_money"    => $result->sum_money,
                             //                "balance" => intval($funds['balance']),
                             "memo"          => $memo,
                             "wallet_type"   => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                         ]
                     );
        }
        return;
    }

    /**
     * 修改订单数据
     * @return [type] [description]
     */
    public function lotteryOrder($order)
    {
        \Model\LotteryOrder::where('id', $order['id'])
                           ->lockForUpdate()
                           ->first();
        // 修改订单状态
        $rs = \Model\LotteryOrder::where('id', $order['id'])
                                 ->whereRaw("!FIND_IN_SET('canceled',state)")
                                 ->whereRaw("!FIND_IN_SET('open',state)")
                                 ->whereRaw("!FIND_IN_SET('winning',state)")
                                 ->update(
                                     [
                                         'state'         => $order['state'],
                                         'open_code'     => $order['period_code'],
                                         'send_money'    => $order['money'],
                                         'lose_earn'     => $order['lose_earn'],
                                         'win_bet_count' => json_encode(
                                             $order['win_bet_count'], JSON_UNESCAPED_UNICODE
                                         ),
                                     ]
                                 );
        // 删除临时表数据
        \Model\LotteryOrderTemp::where('id', $order['id'])
                               ->delete();
        return $rs;
    }

    /**
     * 修改订单数据
     * @return [type] [description]
     */
    public function lotteryTrialOrder($order)
    {
        \Model\LotteryTrialOrder::where('id', $order['id'])
                                ->lockForUpdate()
                                ->first();
        // 修改订单状态
        $rs = \Model\LotteryTrialOrder::where('id', $order['id'])
                                      ->whereRaw("!FIND_IN_SET('canceled',state)")
                                      ->whereRaw("!FIND_IN_SET('open',state)")
                                      ->whereRaw("!FIND_IN_SET('winning',state)")
                                      ->update(
                                          [
                                              'state'         => $order['state'],
                                              'open_code'     => $order['period_code'],
                                              'send_money'    => $order['money'],
                                              'lose_earn'     => $order['lose_earn'],
                                              'win_bet_count' => json_encode(
                                                  $order['win_bet_count'], JSON_UNESCAPED_UNICODE
                                              ),
                                          ]
                                      );
        // 删除临时表数据
        \Model\LotteryTrialOrderTemp::where('id', $order['id'])
                                    ->delete();
        return $rs;
    }

    /**
     * 修改追号数据
     * @return [type] [description]
     */
    public function lotteryChase($order)
    {
        // 修改追号相关信息

        $chase_id = LotteryChaseOrderSub::where('chase_number', $order['chase_number'])
                                        ->where('lottery_number', $order['lottery_number'])
                                        ->value('id');
        LotteryChaseOrderSub::where('id', $chase_id)
                            ->lockForUpdate()
                            ->first();
        $re = LotteryChaseOrderSub::where('id', $chase_id)
                                  ->update(
                                      [
                                          'send_money'    => $order['money'],
                                          'order_number'  => $order['order_number'],
                                          'lose_earn'     => $order['lose_earn'],
                                          'open_code'     => $order['period_code'],
                                          'state'         => $order['money'] > 0 ? 'winning' : 'lose',
                                          'win_bet_count' => json_encode(
                                              $order['win_bet_count'], JSON_UNESCAPED_UNICODE
                                          ),
                                      ]
                                  );
        $chase_order = [
            'reward' => \DB::raw("reward + {$order['money']}"),
            'profit' => \DB::raw("profit + {$order['lose_earn']}"),
        ];
        //撤单退钱
        if ($order['chase_type'] == 2 && $order['money'] > 0 && $order['chase_state'] != 'cancel') {
            //撤单退钱
            $chase_order['state'] = 'cancel';
            $this->cancelChaseOrder($order['chase_number'], $order['user_id']);
        }
        if ($order['chase_state'] != 'cancel') {
            $complete = LotteryChaseOrderSub::where('chase_number', $order['chase_number'])
                                            ->whereIn('state', ['default', 'created'])
                                            ->value('id');
            $chase_order['state'] = $complete ? 'underway' : 'complete';
        }
        LotteryChaseOrder::where('chase_number', $order['chase_number'])
                         ->update($chase_order);
        //         删除临时表数据
        \DB::table('lottery_chase_order_sub_temp')
           ->where('id', $order['id'])
           ->delete();
        return $re;
    }

    /**
     * 修改追号数据
     * @return [type] [description]
     */
    public function lotteryTrialChase($order)
    {
        // 修改追号相关信息

        $chase_id = LotteryTrialChaseOrderSub::where('chase_number', $order['chase_number'])
                                             ->where('lottery_number', $order['lottery_number'])
                                             ->value('id');
        LotteryTrialChaseOrderSub::where('id', $chase_id)
                                 ->lockForUpdate()
                                 ->first();
        $re = LotteryTrialChaseOrderSub::where('id', $chase_id)
                                       ->update(
                                           [
                                               'send_money'    => $order['money'],
                                               'order_number'  => $order['order_number'],
                                               'lose_earn'     => $order['lose_earn'],
                                               'open_code'     => $order['period_code'],
                                               'state'         => $order['money'] > 0 ? 'winning' : 'lose',
                                               'win_bet_count' => json_encode(
                                                   $order['win_bet_count'], JSON_UNESCAPED_UNICODE
                                               ),
                                           ]
                                       );
        $chase_order = [
            'reward' => \DB::raw("reward + {$order['money']}"),
            'profit' => \DB::raw("profit + {$order['lose_earn']}"),
        ];
        if ($order['chase_state'] != 'cancel') {
            $complete = LotteryTrialChaseOrderSub::where('chase_number', $order['chase_number'])
                                                 ->where('state', '=', 'default')
                                                 ->value('id');
            $chase_order['state'] = $complete ? 'underway' : 'complete';
        }
        //撤单退钱
        if ($order['chase_type'] == 2 && $order['money'] > 0 && $order['chase_state'] != 'cancel') {
            //撤单退钱
            $chase_order['state'] = 'cancel';
            $this->trialOrderCancel($order['chase_number'], $order['user_id']);
        }
        LotteryTrialChaseOrder::where('chase_number', $order['chase_number'])
                              ->update($chase_order);
        //         删除临时表数据
        \DB::table('lottery_trial_chase_order_sub_temp')
           ->where('id', $order['id'])
           ->delete();
        return $re;
    }


    /**
     * 试玩运算单个订单
     *
     * @param  [type] $order        [description]
     * @param  [type] $maxSendPrize [description]
     * @param  [type] $maxOdds      [description]
     * @param  [type] $runMode      执行模式 sendPrize 派奖 test 测试
     *
     * @return [type]               [description]
     */
    public function runTrialSingle($lotteryInfo, $order, $maxSendPrize, $maxOdds, $runMode = 'sendPrize')
    {
        $logic = new \LotteryPlay\Logic;
        // $this->logger->error('runSingle', $lotteryInfo);
        $settleOdds = json_decode($order['settle_odds'], true);
        $settleOdds = $this->filterMaxOdds($settleOdds, $maxOdds);
        // $logic->clear();
        $logic->setCate($lotteryInfo['pid'])
              ->setPeriodCode($lotteryInfo['period_code'])
              ->setRules(
                  $playId = $order['play_id'],
                  $odds = $settleOdds,
                  $bet = $order['times'],
                  $betMoney = $order['one_money'],
                  $playNumber = $order['play_number'],
                  $maxSendPrize
              );
        $logic->isValid(true);
        $result = $logic->run();
        if (!$result[0]) {
            // $this->logger->debug('Error:');
            echo 'Error', PHP_EOL;
            print_r(
                [
                    $playId = $order['play_id'],
                    $odds = $settleOdds,
                    $bet = $order['times'],
                    $betMoney = $order['one_money'],
                    $playNumber = $order['play_number'],
                    $maxSendPrize,
                ]
            );
            print_r($logic->getErrors());
            return false;
        }

        $order['period_code'] = $lotteryInfo['period_code'];
        $order['state'] = isset($order['state']) ? $order['state'] : '';
        $order['state'] = $this->addState($order['state'], 'open');
        $order['money'] = 0;
        if ($logic->isWin()) {
            $order['state'] = $this->addState($order['state'], 'winning'); //中奖标志

            // 28类 特殊大小单双 13 14
            if ($lotteryInfo['pid'] == 1 && $order['play_id'] == 151 &&
                in_array($sum = $logic->getPeriodCodeSum(), [13, 14])) {
                $oddsIndex = [];
                $oddsIndex['大或双开14'] = [0, 3];
                $oddsIndex['小或单开13'] = [1, 2];
                $oddsIndex['小单开13'] = [4];
                $oddsIndex['大双开14'] = [7];

                $specialData = \Model\Pc28special::where('lottery_id', $order['lottery_id'])
                                                 ->where('hall_id', $order['hall_id'])
                                                 ->get();

                $payMoney = \Model\LotteryTrialOrder::where('user_id', $order['user_id'])
                                                    ->where('lottery_id', $order['lottery_id'])
                                                    ->where('lottery_number', $order['lottery_number'])
                                                    ->where('play_id', 151)
                                                    ->where('hall_id', $order['hall_id'])
                                                    ->selectRaw("SUM(pay_money) as pay_money")
                                                    ->first();
                $betAmount = $payMoney->pay_money / 100;
                // $betAmount = $order['pay_money'] / 100; //该订单投注额
                // print_r($specialData);
                if (!empty($specialData)) {
                    // echo 'a!', PHP_EOL;
                    foreach ($specialData as $special) {
                        if (isset($oddsIndex[$special['type']])) {
                            // 覆盖赔率
                            if ($betAmount < $special['step1']) {
                                // echo 'a0', PHP_EOL;
                                foreach ($oddsIndex[$special['type']] as $index) {
                                    $odds[$index] = $special['odds'];
                                }
                            } else if ($betAmount >= $special['step1'] && $betAmount <= $special['step2']) {
                                // echo 'a1', PHP_EOL;
                                foreach ($oddsIndex[$special['type']] as $index) {
                                    $odds[$index] = $special['odds1'];
                                }
                            } else {
                                // echo 'a2', PHP_EOL;
                                foreach ($oddsIndex[$special['type']] as $index) {
                                    $odds[$index] = $special['odds2'];
                                }
                            }
                        }
                    }

                    $odds = $this->filterMaxOdds($odds, $maxOdds);
                    // $logic->clear();
                    $logic = new \LotteryPlay\Logic;
                    $logic->setCate($lotteryInfo['pid'])
                          ->setPeriodCode($lotteryInfo['period_code'])
                          ->setRules(
                              $playId = $order['play_id'],
                              $odds,
                              $bet = $order['times'],
                              $betMoney = $order['one_money'],
                              $playNumber = $order['play_number'],
                              $maxSendPrize
                          );
                    $logic->isValid(true);
                    $result = $logic->run();
                }

            }
            $order['money'] = $result[1];
        }
        $order['lose_earn'] = $order['money'] - $order['pay_money'];
        if ($logic->isWin()) {
            $order['state'] = $this->addState($order['state'], 'winning'); //中奖标志
        }

        $order['rebet'] = 0;
        $order['odds_id'] = 0;
        $order['valid_bet'] = $order['pay_money'];
        $order['earn_money'] = $order['bet_num'] * $order['one_money'] * max(json_decode($order['odds'], true));
        $order['win_bet_count'] = $logic->showWinBetCount();

        // 判断是否运行派奖模式
        if ($runMode == 'sendPrize') {
            try {
                $this->db->getConnection()
                         ->beginTransaction();

                if ($this->db->getConnection()
                             ->transactionLevel()) {

                    $user = \Model\TrialUser::where('id', $order['user_id'])
                                            ->first();

                    \Model\TrialFunds::where('id', $user['wallet_id'])
                                     ->lockForUpdate()
                                     ->first();

                    if (isset($order['function']) && method_exists($this, $order['function'])) {
                        $function = $order['function'];
                        $rs = $this->$function($order, $user);
                    } else {
                        $rs = $this->lotteryTrialOrder($order);
                    }

                    if ($rs) {

                        // 中奖派钱
                        if ($logic->isWin()) {
                            $wallet = new Wallet($this->ci);
                            //金额结算，往主钱包里加钱
                            $wallet->addTrialMoney(
                                $user,
                                $order['order_number'],
                                $order['money'],
                                2, //交易类型：派奖
                                $order['order_number'], //备注为订单号
                                $order['pay_money']//投注金额
                            );
                        }

                        // 写入派奖数据
                        \Model\SendTrialPrize::create(
                            [
                                'user_id'      => $order['user_id'],
                                'user_name'    => $order['user_name'],
                                'bet_number'   => $order['bet_num'],
                                'order_number' => $order['order_number'],
                                'odds_id'      => $order['odds_id'],
                                'bet_type'     => $order['lottery_id'],
                                'pay_money'    => $order['pay_money'],
                                'earn_money'   => $order['earn_money'],
                                'money'        => $order['money'],
                                'rebet'        => $order['rebet'],
                                'lose_earn'    => $order['lose_earn'],
                                'status'       => 'normal',
                            ]
                        );

                        $this->db->getConnection()
                                 ->commit();
                    } else {
                        $this->db->getConnection()
                                 ->rollback();
                    }
                }
            } catch (\Exception $e) {
                $this->logger->debug('Error:');
                $this->logger->debug($e->getMessage());

                print_r(
                    [
                        $playId = $order['play_id'],
                        $odds = $settleOdds,
                        $bet = $order['times'],
                        $betMoney = $order['one_money'],
                        $playNumber = $order['play_number'],
                        $maxSendPrize,
                    ]
                );

                print_r($logic->getErrors());
                print_r($e->getMessage());

                $this->db->getConnection()
                         ->rollback();
            }
        }

        return $order;
    }


    /**
     * 取系统配置项目
     * @return [type] [description]
     */
    public function getConfig()
    {
        $datas = new SystemConfig($this->ci);
        return $datas->getMaxOdds();
    }

    /**
     * add state value
     *
     * @param array $data 彩期的部分信息数组
     * @param string $value 需要添加的值
     *
     * @return string 更新后的state
     */
    protected function addState($data, $value)
    {
        if (empty($data)) {
            return $value;
        }

        $state = explode(',', $data);
        if (!in_array($value, $state)) {
            array_push($state, $value);
        }
        return implode(',', $state);
    }

    /**
     * 隐藏的最高赔率
     *
     * @param  [type] $settleOdds [description]
     * @param  [type] $maxOdds    [description]
     *
     * @return [type]             [description]
     */
    protected function filterMaxOdds($settleOdds, $maxOdds)
    {
        foreach ($settleOdds as $k => $v) {
            if ($settleOdds[$k] > $maxOdds) {
                $settleOdds[$k] = $maxOdds;
            }
        }
        return $settleOdds;
    }
}

