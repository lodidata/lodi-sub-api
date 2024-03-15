<?php
namespace Logic\Lottery;
use \Logic\Wallet\Wallet;
use \Model\LotteryOrder;
use \Model\LotteryOrderTemp;
use \Model\LotteryChase;
/**
 * 追号模块
 */
class Chase extends \Logic\Logic {

    /**
     * 追号消息通知入口
     * @param  [type] $lotteryId     [description]
     * @param  [type] $lotteryNumber [description]
     * @return [type]                [description]
     */
    public function runByNotify($lotteryId, $lotteryNumber) {
        $orderObj = new Order($this->ci);
        $list = $this->getChaseList($lotteryId, $lotteryNumber);
        if (count($list) == 0) {
            $this->logger->debug("追号数据为空: $lotteryId, $lotteryNumber");
            return false;
        }
        $this->logger->debug("追号通知 {$lotteryId} {$lotteryNumber}");
        try {
            $order = [];
            $chaseList = [];
            foreach ($list as $k => $v) {
                $v = (array) $v;
                //根据当前彩期与追单号获取注单状态为default所有列表,过滤已生成与已开奖的追号
                // $this->logger->debug($v['chase_number'], ' ', $lotteryNumber);
                $chaseInfo = (array) $this->getChaseState($v['chase_number'], $lotteryNumber);
                // $this->logger->debug('getChaseState');
                // print_r($chaseInfo);
                if (empty($chaseInfo)) {
                    unset($list[$k]);
                    continue;
                }

                //中奖后停止
                if ($chaseInfo['chase_type'] == 2) {
                    if ($chaseInfo['money'] > 0) {
                        //统计中奖金额是否大于等于中奖停止金额 如果符合则停止当前追号并返回用户余额
                        $result = $this->cancel($chaseInfo['chase_number']);
                        if ($result['state'] == 0) {
                            unset($list[$k]);
                            continue;
                        } else {
                            //输出错误
                            $this->logger->error($result['msg'] . 'Error Code: ' . $result['state']);
                        }
                    }
                }
                // $this->logger->debug('LotteryOrder');
                //根据订单号获取订单内容
                $query = LotteryOrder::whereIn('order_number', explode(',', $v['list']))->get()->toArray();
                // print_r($query);
                if (!empty($query)) {
                    foreach ($query as $key => $value) {
                        $order[$k + $key] = $value;
                    }
                }
                $chaseList[$chaseInfo['chase_number']] = $chaseInfo;
            }

            if (!empty($order)) {
                $arr = $this->formatChaseOrder($order, $lotteryNumber, $chaseList);
                $orders = [];
                foreach ($arr as $val) {
                    unset($val['id']);
                    unset($val['updated']);
                    unset($val['win_bet_count']);
                    // 删除开奖状态
                    if (!empty($val['state'])) {
                        $states = [];
                        foreach (explode(',', $val['state']) as $v) {
                            if (in_array($v, ['open', 'winning'])) {
                                continue;
                            }
                            $states[] = $v;
                        }

                        $val['state'] = join(',', $states);
                        $val['state'] = !empty($val['state']) ? $val['state'] : 'std';
                    }
                    $val['id'] = LotteryOrder::create($val)->id;
                    LotteryOrderTemp::create($val);
                    $user = \Model\User::where('id', $val['user_id'])->first(['tags']);
                    $lottery = \Model\Lottery::where('id', $val['lottery_id'])->first(['pid']);
                    $val['user_tags'] = $user['tags'];
                    $val['pid'] = $lottery['pid'];
                    $orders[] = $val;
                }
                
         

                foreach ($list as $a => $b) {
                    $b = (array) $b;
                    // 更新lottery_chase状态为created
                    LotteryChase::where('chase_number', $b['chase_number'])
                                         ->whereRaw("FIND_IN_SET('default',`state`)")
                                         ->where('lottery_number', $lotteryNumber)
                                         ->update([
                                            'state' => 2
                                         ]);
                }
            }

        } catch (\Exception $e) {
            //@todo 出错??
            $this->logger->debug('Chase Error:' . $e->getMessage());
            return $e->getMessage();
        }
    }
    /**
     * 补追号订单生成失败问题
     * @return [type] [description]
     */
    public function runReopen() {
        $db = $this->db->getConnection();
        $rs = $db->table('lottery_chase')
           ->leftjoin('lottery_info', function ($join) {
               $join->on('lottery_chase.lottery_number', '=', 'lottery_info.lottery_number')
                    ->on('lottery_chase.lottery_id', '=', 'lottery_info.lottery_type');
           })
           ->where('lottery_info.period_code','!=', '')
           ->whereRaw("find_in_set('default', lottery_chase.state)")
           ->orderBy('lottery_info.start_time', 'asc')
           ->get([
            'lottery_chase.id',
            'lottery_chase.chase_number',
            'lottery_chase.lottery_id',
            'lottery_chase.lottery_number',
            'lottery_chase.state',
            'lottery_info.period_code',
            'lottery_info.pid',
            'lottery_chase.state'
           ])
           ->toArray();
        $settle = new \Logic\Lottery\Settle($this->ci);
        $this->logger->info('【追号准备补】:'.count($rs).'个');
        foreach ($rs ?? [] as $v) {
            $v = (array) $v;
            $order = \Model\LotteryOrder::where('lottery_id', $v['lottery_id'])
                        ->where('chase_number', $v['chase_number'])
                        ->where('lottery_number', $v['lottery_number'])
                        ->first();

            if ($v['state'] == 'default') {
                if (!empty($order)) {
                     \Model\LotteryChase::where('lottery_id', $v['lottery_id'])
                        ->where('chase_number', $v['chase_number'])
                        ->where('lottery_number', $v['lottery_number'])
                        ->update(['state' => 'created']);
                } else {
                    $this->runByNotify($v['lottery_id'], $v['lottery_number']);
                }
                $settle->runByNotify($v['lottery_id'], $v['lottery_number']);
            } else if ($v['state'] == 'created') { //处理lottery_chase state=created时，却没有生成订单的问题
                if (empty($order)) {
                    \Model\LotteryChase::where('lottery_id', $v['lottery_id'])
                        ->where('chase_number', $v['chase_number'])
                        ->where('lottery_number', $v['lottery_number'])
                        ->update(['state' => 'default']);
                    $this->runByNotify($v['lottery_id'], $v['lottery_number']);
                    $settle->runByNotify($v['lottery_id'], $v['lottery_number']);
                }
            }
        }
    }

    /**
     * 取消追号
     * @param  integer $chaseNumber [description]
     * @return [type]               [description]
     */
    public function cancel($chaseNumber = 0, $userId = 0) {
        try {
            $this->db->getConnection()->beginTransaction();
            if ($this->db->getConnection()->transactionLevel()) {
                $result = $this->getTotal($chaseNumber, $userId);
                $result = (array) $result;
                if (empty($result)) {
                    throw new \Exception($chaseNumber . " chase number error!!", 2);
                }

                if ((null == $result['total']) || (null == $result['wallet_id'])) {
                    throw new \Exception($chaseNumber . " chase number state error!!", 3);
                }

                // 更新追号订单
                $rs = \Model\LotteryChase::where('chase_number', $chaseNumber)
                                     ->whereRaw("FIND_IN_SET('default',state)")
                                     ->update(['state' => 8]);

                if (!$rs) {
                    throw new \Exception("$chaseNumber update chase list fail !", 4);
                } 
                // 锁定钱包
                \Model\Funds::where('id', $result['wallet_id'])->lockForUpdate()->first();

                //$memo = "停止追号:" . $result['name'] . "-追单号:" . $chaseNumber . "-期号:" . $result['list'];
                $memo = $this->lang->text("Stop tracking number: %s - tracking number: %s - Issue Number: %s", [$result['name'], $chaseNumber, $result['list']]);
                $wallet = new Wallet($this->ci);
                $wallet->crease($result['wallet_id'], $result['total']);
                $funds = \Model\Funds::where('id', $result['wallet_id'])->first();
                //流水里面添加打码量可提余额等信息
                $dml = new \Logic\Wallet\Dml($this->ci);
                $dmlData =$dml->getUserDmlData($result['user_id']);
                \Model\FundsDealLog::create([
                    "user_id" => $result['user_id'],
                    "user_type" => 1,
                    "username" => $result['username'],
                    "order_number" => $chaseNumber,
                    "deal_type" => \Model\FundsDealLog::TYPE_CANCEL_ORDER,
                    "deal_category" => \Model\FundsDealLog::CATEGORY_INCOME,
                    "deal_money" => $result['total'],
                    "balance" => intval($funds['balance']),
                    "memo" => $memo,
                    "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                    'total_bet'=>$dmlData->total_bet,
                    'withdraw_bet'=> 0,
                    'total_require_bet'=>$dmlData->total_require_bet,
                    'free_money'=>$dmlData->free_money
                ]);

                $this->db->getConnection()->commit();
            } else {
                $this->db->getConnection()->rollback();
            }
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
        }
    }

    /**
     * 根据当前彩期获取追号注单状态为default所有列表
     *
     * @param number chaseNumber
     * @param number $lotteryNumber
     * @return array
     */
    protected function getChaseState($chaseNumber = 0, $lotteryNumber = 0) {
        return $this->db->getConnection()->selectOne("SELECT c.*, ( SELECT multiple FROM lottery_chase WHERE chase_number = c.chase_number AND lottery_number < c.lottery_number ORDER BY id, lottery_number ASC LIMIT 1 ) AS base_times,(SELECT IF(ISNULL(sp.money),0,SUM(sp.money)) AS money FROM `lottery_order` AS o LEFT JOIN send_prize AS sp ON o.order_number=sp.order_number WHERE o.chase_number=c.chase_number) AS money FROM lottery_chase c WHERE c.chase_number = '{$chaseNumber}' AND c.lottery_number = '{$lotteryNumber}' AND FIND_IN_SET('default', c.state)");
    }

    protected function getTotal($chaseNumber, $userId = 0) {
        if ($userId > 0) {
            return $this->db->getConnection()->selectOne("SELECT sum(c.current_bet) AS total,u.wallet_id,c.user_id,u.`name` AS username,l.`name`,GROUP_CONCAT(c.lottery_number) AS list FROM lottery_chase AS c LEFT JOIN `user` AS u ON c.user_id = u.id LEFT JOIN lottery AS l ON l.id=c.lottery_id WHERE c.chase_number='{$chaseNumber}' AND c.user_id = '{$userId}' AND FIND_IN_SET('default',c.state)");
        } else {
            return $this->db->getConnection()->selectOne("SELECT sum(c.current_bet) AS total,u.wallet_id,c.user_id,u.`name` AS username,l.`name`,GROUP_CONCAT(c.lottery_number) AS list FROM lottery_chase AS c LEFT JOIN `user` AS u ON c.user_id = u.id LEFT JOIN lottery AS l ON l.id=c.lottery_id WHERE c.chase_number='{$chaseNumber}' AND FIND_IN_SET('default',c.state)");
        }

    }

    /**
     * 根据彩票ID与期号获取追号列表
     *
     * @param number $lotteryId
     * @param number $lotteryNumber
     */
    protected function getChaseList($lotteryId = 0, $lotteryNumber = 0) {
        return $this->db->getConnection()->select("SELECT ( SELECT GROUP_CONCAT(o.order_number SEPARATOR ',') AS list FROM `lottery_order` o WHERE o.lottery_number = c1.lottery_number AND o.chase_number=c1.chase_number) AS list , c1.chase_number , c1.chase_type , c1.state , i.state AS info_state FROM lottery_chase AS c , lottery_chase AS c1 LEFT JOIN lottery_info i ON c1.lottery_number = i.lottery_number AND c1.lottery_id = i.lottery_type WHERE c.chase_number = c1.chase_number AND c.lottery_number = '{$lotteryNumber}' AND c.lottery_id = '{$lotteryId}' GROUP BY c1.chase_number");
    }

    /**
     * 格式化追号注单列表
     *
     * @param array  $data
     * @param number $lotteryNumber
     * @param array  $chaseList
     * @return array|unknown|string
     */
    protected function formatChaseOrder($data = [], $lotteryNumber = 0, $chaseList = []) {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                //更新注单追号倍数等信息
                $chase = $chaseList[$value['chase_number']];
                $base_times = $chase['base_times'];
                $multiple = $chase['multiple'];
                $times = $value['times'] / $base_times * $multiple;
                $pay_money = $value['pay_money'] / $base_times * $multiple;
                $order_number = \Model\LotteryOrder::generateOrderNumber();
                $date = date('Y-m-d H:i:s', time());
                $data[$key]['order_number'] = $order_number;
                $data[$key]['lottery_number'] = (int) $lotteryNumber;
                $data[$key]['pay_money'] = $pay_money;
                $data[$key]['times'] = $times;
                $data[$key]['created'] = $date;
                $data[$key]['updated'] = $date;
            }
        }
        return $data;
    }
}