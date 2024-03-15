<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/4/16
 * Time: 14:06
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController
{
    const TITLE       = '追号订单详情';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'chase_number' => 'string #追号单号'
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        $validate = new BaseValidate([
            'chase_number'  => 'require|number',
        ]);

        $validate->paramsCheck('',$this->request,$this->response);

        $chase_number = $this->request->getParam('chase_number');

        $chase = \Model\LotteryChaseOrder::where('chase_number',$chase_number)->first();
        if($chase) {
            $chase = $chase->toArray();
            $chase_sub = \Model\LotteryChaseOrderSub::where('chase_number', $chase_number)->get()->toArray();
            $states = ['complete' => '已结束', 'cancel' => '已撤单', 'underway' => '进行中'];
            $sub_states = ['default' => '待追号', 'created' => '待开奖', 'winning' => '已中奖', 'lose' => '未中奖', 'cancel' => '已取消'];
            $origins = [1 => 'PC', 2 => 'H5', 3 => 'APP', 4 => 'APP'];
            $re['lottery_id'] = $chase['lottery_id'];
            $re['lottery_name'] = $chase['lottery_name'];
            $re['status'] = $chase['state'];
            $re['status_chinese'] = $states[$chase['state']];

            $re['chase_msg']['chase_number'] = (string)$chase['chase_number'];
            $re['chase_msg']['create'] = $chase['created'];
            $re['chase_msg']['money'] = $chase['increment_bet'];
            $re['chase_msg']['money_lose'] = $chase['profit'];
            $re['chase_msg']['money_reward'] = $chase['reward'];
            $re['chase_msg']['origin'] = $origins[$chase['origin']] ?? '未知';
            $re['chase_msg']['user_name'] = $chase['user_name'];

            $re['chase_desc']['chase_desc'] = $chase['chase_type'] == 1 ? '中奖不停止' : '中奖停止';
            $re['chase_desc']['num'] = $chase['complete_periods'] . '/' . $chase['sum_periods'];
            $re['chase_desc']['num_c'] = $chase['complete_periods'];
            $re['chase_desc']['num_s'] = $chase['sum_periods'];
            $data = [];
            foreach ($chase_sub as $v) {
                $tmp['current_bet'] = $v['pay_money'];
                $tmp['lottery_number'] = $v['lottery_number'];
                $tmp['multiple'] = $v['times'];
                $tmp['period_code'] = $v['open_code'];
                $tmp['state'] = $sub_states[$v['state']];
                $tmp['state_str'] = $v['state'];
                $tmp_play['odds'] = $v['odds'];
                $tmp_play['play_number'] = $v['play_number'];
                $data[] = $tmp;
            }
            $re['chase_desc']['data'] = $data;

            $logic = new \LotteryPlay\Logic();
            $re['play_desc']['total_money'] = $chase['one_money'] * $chase['bet_num'];
            $pid = \Model\Lottery::where('id', $chase['lottery_id'])->value('pid') ?: $chase['lottery_id'];
            $temp_odds = [];
            foreach (json_decode($tmp_play['odds'], true) as $k => $v) {
                $tt['name'] = $k;
                $tt['num'] = $v;
                $temp_odds[] = $tt;
            }
            $play_data[] = [
                'bet_num' => $chase['bet_num'],
                'name' => $chase['play_group'] . '>' . $chase['play_name'],
                'pay_money' => $chase['one_money'] * $chase['bet_num'],
                'room_name' => $chase['room_name'],
                'times' => $chase['times'],
                'odds' => $tmp_play['odds'],
                'odds_array' => $temp_odds,
                'play_number' => $tmp_play['play_number'],
                'play_numbers' => $logic->getPretty($pid, $chase['play_id'], $tmp_play['play_number']),
            ];
            $re['play_desc']['data'] = $play_data;
            return $re;
        }
        return $this->lang->set(-2);
    }


};