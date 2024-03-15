<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/18
 * Time: 11:19
 */

namespace Logic\Admin;

use DB;
use lib\exception\BaseException;
use Model\User;

class SettleLottery extends \Logic\Logic
{

    public function __construct($ci)
    {
        parent::__construct($ci);

    }

    /*
     * 手工开奖
     */
    public function manOpen($info, $order)
    {
        $db = db('core');
        $this->getlottery($info['lottery_type']);
        if ($this->lottery == null) {
            //@tudo 错误订单处理
            return;
        }
        $sign = $this->lottery->resultformat($info);

        $orderLotteryId = $order['lottery_id'];
        $pc28ResultSum = $this->lottery->lcode['sum'] ?? ''; //28开奖结果和值

        if (in_array($orderLotteryId, $this->getXy28LotteryIds()) && in_array($pc28ResultSum, [13, 14])) {
            $orderLotteryId = $order['lottery_id'];
            $pc28ResultSum = $this->lottery->lcode['sum']; //28开奖结果和值
            //只有大，小，单，双，大双，小单 6种玩法做特殊处理
            $oddsId = $order['odds_id'];
            $sql = "select result from odds where id = '$oddsId'";
            $resultName = $db->query($sql)[0]['result'];
            $searchType = '';
            if (in_array($resultName, ['大', '双']) && $pc28ResultSum == 14) {
                $searchType = '大或双开14';
            } else if (in_array($resultName, ['小', '单']) && $pc28ResultSum == 13) {
                $searchType = '小或单开13';
            } else if (in_array($resultName, ['小单']) && $pc28ResultSum == 13) {
                $searchType = '小单开13';
            } else if (in_array($resultName, ['大双']) && $pc28ResultSum == 14) {
                $searchType = '大双开14';
            }

            if ($searchType) {
                //获取1314特殊赔率数据
                $sql = "SELECT * FROM pc28special WHERE lottery_id = $orderLotteryId AND hall_id = (SELECT hall_id FROM room WHERE id = {$order['room_id']})  and type = '$searchType'";
                $specialData = $db->query($sql);
                $betAmount = $order['pay_money'] / 100; //该订单投注额
                //根据投注额获取相应的赔率
                $realOdds = '';
                if ($betAmount < $specialData[0]['step1']) {
                    $realOdds = $specialData[0]['odds'];
                } else if ($betAmount >= $specialData[0]['step1'] && $betAmount <= $specialData[0]['step2']) {
                    $realOdds = $specialData[0]['odds1'];
                } else {
                    $realOdds = $specialData[0]['odds2'];
                }
                $order['order_sub'][0]['odds'] = $realOdds;

            }
        }
    }

    public function incomeInfo2($info, $orders) {
        $pay_money = 0;
        $earn_money = 0;
        $setGlobal = json_decode($this->redis->get('system.config.global'), true);
//        print_r($setGlobal);exit;
        // 最高派奖
        $maxSendPrize = isset($setGlobal['max_award']) ? $setGlobal['max_award'] : 50 * 10000 * 100;
        // 最高隐藏赔率
        $maxOdds = isset($setGlobal['max_odds']) ? $setGlobal['max_odds'] : 10000;

        if (!empty($orders)) {

            $logic = new \LotteryPlay\Logic;
            foreach ($orders as $order) {
                $settleOdds = json_decode($order['settle_odds'], true);
                $settleOdds = $this->_filterMaxOdds($settleOdds, $maxOdds);
                $logic->setCate($info['pid'])
                    ->setPeriodCode($info['period_code'])
                    ->setRules($playId = $order['play_id'],
                        $odds = $settleOdds,
                        $bet = $order['times'],
                        $betMoney = $order['one_money'],
                        $playNumber = $order['play_number'],
                        $maxSendPrize);
                $logic->isValid(true);
                $result = $logic->run();


                if ($result[0]) {
                    $order['state'] = isset($order['state']) ? $order['state'] : '';
                    $order['state'] = $this->addState($order['state'], 'open');
                    $order['money'] = 0;
                    if ($logic->isWin()) {
                        $order['state'] = $this->addState($order['state'], 'winning'); //中奖标志

                        // 28类 特殊大小单双 13 14
                        if ($info['pid'] == 1 && $order['play_id'] == 151 && in_array($sum = $logic->getPeriodCodeSum(), [13, 14])) {
                            $oddsIndex = [];
                            $oddsIndex['大或双开14'] = [0, 3];
                            $oddsIndex['小或单开13'] = [1, 2];
                            $oddsIndex['小单开13'] = [4];
                            $oddsIndex['大双开14'] = [7];

                            // if ($order['room_id'] > 0) {
                            //获取1314特殊赔率数据
                            $sql = "SELECT * FROM pc28special WHERE lottery_id = '{$order['lottery_id']}' AND hall_id = (SELECT hall_id FROM room WHERE id = {$order['room_id']})";
                            $specialData = DB::select($sql);
                            $betAmount = $order['pay_money'] / 100; //该订单投注额
                            if (!empty($specialData)) {
                                foreach ($specialData as $special) {
                                    if (isset($oddsIndex[$special['type']])) {
                                        // 覆盖赔率
                                        if ($betAmount < $special['step1']) {
                                            foreach ($oddsIndex[$special['type']] as $index) {
                                                $odds[$index] = $special['odds'];
                                            }
                                        } else if ($betAmount >= $special['step1'] && $betAmount <= $special['step2']) {
                                            foreach ($oddsIndex[$special['type']] as $index) {
                                                $odds[$index] = $special['odds1'];
                                            }
                                        } else {
                                            foreach ($oddsIndex[$special['type']] as $index) {
                                                $odds[$index] = $special['odds2'];
                                            }
                                        }

                                    }
                                }

                                $odds = $this->_filterMaxOdds($odds, $maxOdds);
                                $logic->clear();
                                $logic->setCate($info['pid'])
                                    ->setPeriodCode($info['period_code'])
                                    ->setRules($playId = $order['play_id'],
                                        $odds,
                                        $bet = $order['times'],
                                        $betMoney = $order['one_money'],
                                        $playNumber = $order['play_number'],
                                        $maxSendPrize);
                                $logic->isValid(true);
                                $result = $logic->run();
                                // $logic->clear();
                            }
                            // }
                        }
                        $order['money'] = $result[1];
                    }
                    $order['lose_earn'] = $order['money'] - $order['pay_money'];

                    if (true) {
                        $user = $order['user_id'];
                        // $account = $this->module->account;
//                        $userObj = $this->module->user;
//                        $uinfo = $userObj->info($order['user_id']);
                        //$uinfo = User::find($order['user_id']);
                        //$wid = $uinfo[0]['wallet_id'];
                        // $this->db->query("SELECT * FROM funds WHERE id = {$wid} FOR UPDATE");

                        if ($logic->isWin() && false) {
                            $order['state'] = $this->addState($order['state'], 'winning'); //中奖标志
                            //金额结算，往主钱包里加钱
                            $this->service->addmoney(
                                $order['user_id'],
                                $order['user_name'],
                                $order['order_number'],
                                $order['money'],
                                2, //交易类型：派奖
                                $order['order_number']//备注为订单号
                            );
                        }

                        $order['rebet'] = 0;
                        $order['odds_id'] = 0;
                        $order['valid_bet'] = $order['pay_money'];
                        $order['earn_money'] = $order['bet_num'] * $order['one_money'] * max(json_decode($order['odds'], true));
                        $order['win_bet_count'] = json_encode($logic->showWinBetCount(), JSON_UNESCAPED_UNICODE);
                        $order['win_bet_count'] = empty($order['win_bet_count']) ? '' : $order['win_bet_count'];

                        $pay_money += $order['pay_money'];
                        $earn_money += $order['money'];
                    }
                } else {

                }
                // $logic->clear();
            }
        } else {
            echo 4323;exit;
        }

        return [
            'totalTou' => $pay_money / 100,
            'totalWin' => $earn_money / 100,
            'totalIncome' => ($pay_money-$earn_money) / 100,
        ];
    }

    /**
     * 隐藏的最高赔率
     * @param  [type] $settleOdds [description]
     * @param  [type] $maxOdds    [description]
     * @return [type]             [description]
     */
    protected function _filterMaxOdds($settleOdds, $maxOdds) {
        foreach ($settleOdds as $k => $v) {
            if ($settleOdds[$k] > $maxOdds) {
                $settleOdds[$k] = $maxOdds;
            }
        }
        return $settleOdds;
    }

    /**
     * add state value
     * @param array $data 彩期的部分信息数组
     * @param string $value 需要添加的值
     * @return string 更新后的state
     */
    protected function addState($data, $value) {
        if (empty($data)) {
            return $value;
        }
        $state = explode(',', $data);
        if (!in_array($value, $state)) {
            array_push($state, $value);
        }

        return implode(',', $state);
    }
}