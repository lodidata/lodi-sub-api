<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '充值用户流水占比';
    const DESCRIPTION = '充值用户流水占比';
    const QUERY = [
        'date_start' => 'string() #开始日期',
        'date_end' => 'string() #结束日期',
        'user_type' => 'int() #用户类型：0-全部，1-新用户，2-老用户'
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'msg_title' => '消息标题',
            'msg_content' => '消息内容',
            'give_away' => '赠送方式',
            'notice_away' => '通知方式',
            'give_num' => '赠送人数',
            'give_amount' => '设置的赠送彩金',
            'dm_num' => '设置的打码量',
            'total_give_amount' => '总赠送彩金',
            'create_time' => '创建时间',
            'give_time' => '赠送时间'
        ],
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    //充值金额区间范围
    public $range = [

    ];

    public function run()
    {
        $date_start = $this->request->getParam('date_start',date('Y-m-d'));
        $date_end = $this->request->getParam('date_end',date('Y-m-d'));
        $user_type = $this->request->getParam('user_type', '');    //用户类型：0-全部，1-新用户，2-老用户

        //限制最多查询31天数据
        if ((strtotime($date_end)-strtotime($date_start))/86400 > 31) {
            return createRsponse($this->response, 200, -2, '时间选择跨度不能大于一个月');
        }

        $fields = 'user_id,deposit_user_amount,withdrawal_user_amount,bet_user_amount';

        if ($user_type == 1) {   //新用户
            $data = DB::connection('slave')->table('rpt_user')
                ->selectRaw('user_id,sum(deposit_user_amount) as deposit_user_amount, sum(withdrawal_user_amount) as withdrawal_user_amount, sum(bet_user_amount) as bet_user_amount')
                ->whereRaw('count_date>=? and count_date<=? and first_deposit=?', [$date_start, $date_end, 1])->groupBy(['user_id'])->get()->toArray();
        } elseif ($user_type == 2) {  //老用户
            $data = DB::connection('slave')->table('rpt_user')
                ->selectRaw('user_id,sum(deposit_user_amount) as deposit_user_amount, sum(withdrawal_user_amount) as withdrawal_user_amount, sum(bet_user_amount) as bet_user_amount')
                ->whereRaw('count_date>=? and count_date<=? and first_deposit=?', [$date_start, $date_end, 0])->groupBy(['user_id'])->get()->toArray();
        } else {   //全部
            $data = DB::connection('slave')->table('rpt_user')
                ->selectRaw('user_id,sum(deposit_user_amount) as deposit_user_amount, sum(withdrawal_user_amount) as withdrawal_user_amount, sum(bet_user_amount) as bet_user_amount')
                ->whereRaw('count_date>=? and count_date<=?', [$date_start, $date_end])->groupBy(['user_id'])->get()->toArray();
        }

        //user_cnt-人数, recharge_cnt-充值总额，withdraw_cnt-提现总额，withdraw_num-提现人数，bet_user_amount-投注总额，profit_diff-充值提款差额，ratio-杀率，recharge_ratio-充值人数占比，coin_ratio-充值金额占比
        $opt = [
            '100000+' => ['user_cnt'=>0,'recharge_cnt'=>0,'withdraw_cnt'=>0,'withdraw_num'=>0,'bet_user_amount'=>0,'profit_diff'=>0,'ratio'=>'','recharge_ratio'=>'','coin_ratio'=>''],
            '50000~99999' => ['user_cnt'=>0,'recharge_cnt'=>0,'withdraw_cnt'=>0,'withdraw_num'=>0,'bet_user_amount'=>0,'profit_diff'=>0,'ratio'=>'','recharge_ratio'=>'','coin_ratio'=>''],
            '10000~49999' => ['user_cnt'=>0,'recharge_cnt'=>0,'withdraw_cnt'=>0,'withdraw_num'=>0,'bet_user_amount'=>0,'profit_diff'=>0,'ratio'=>'','recharge_ratio'=>'','coin_ratio'=>''],
            '5000~9999' => ['user_cnt'=>0,'recharge_cnt'=>0,'withdraw_cnt'=>0,'withdraw_num'=>0,'bet_user_amount'=>0,'profit_diff'=>0,'ratio'=>'','recharge_ratio'=>'','coin_ratio'=>''],
            '1000~4999' => ['user_cnt'=>0,'recharge_cnt'=>0,'withdraw_cnt'=>0,'withdraw_num'=>0,'bet_user_amount'=>0,'profit_diff'=>0,'ratio'=>'','recharge_ratio'=>'','coin_ratio'=>''],
            '500~999' => ['user_cnt'=>0,'recharge_cnt'=>0,'withdraw_cnt'=>0,'withdraw_num'=>0,'bet_user_amount'=>0,'profit_diff'=>0,'ratio'=>'','recharge_ratio'=>'','coin_ratio'=>''],
            '100~499' => ['user_cnt'=>0,'recharge_cnt'=>0,'withdraw_cnt'=>0,'withdraw_num'=>0,'bet_user_amount'=>0,'profit_diff'=>0,'ratio'=>'','recharge_ratio'=>'','coin_ratio'=>''],
            '10~99' => ['user_cnt'=>0,'recharge_cnt'=>0,'withdraw_cnt'=>0,'withdraw_num'=>0,'bet_user_amount'=>0,'profit_diff'=>0,'ratio'=>'','recharge_ratio'=>'','coin_ratio'=>''],
            '0~9' => ['user_cnt'=>0,'recharge_cnt'=>0,'withdraw_cnt'=>0,'withdraw_num'=>0,'bet_user_amount'=>0,'profit_diff'=>0,'ratio'=>'','recharge_ratio'=>'','coin_ratio'=>'']
        ];
        $total_user = 0;    //总充值人数
        $total_coin = 0;   //总充值金额
        if ($data) {
            foreach ($data as $itm) {
                if ($itm->deposit_user_amount <= 0) {
                    continue;
                }
                $total_user += 1;
                //统计总充值金额
                $total_coin += $itm->deposit_user_amount;
                //充值区间判断
                if ($itm->deposit_user_amount >= 100000) {
                    $tag_key = '100000+';
                } elseif ($itm->deposit_user_amount >= 50000 && $itm->deposit_user_amount<=99999.99) {
                    $tag_key = '50000~99999';
                } elseif ($itm->deposit_user_amount >= 10000 && $itm->deposit_user_amount<=49999.99) {
                    $tag_key = '10000~49999';
                } elseif ($itm->deposit_user_amount >= 5000 && $itm->deposit_user_amount<=9999.99) {
                    $tag_key = '5000~9999';
                } elseif ($itm->deposit_user_amount >= 1000 && $itm->deposit_user_amount<=4999.99) {
                    $tag_key = '1000~4999';
                } elseif ($itm->deposit_user_amount >= 500 && $itm->deposit_user_amount<=999.99) {
                    $tag_key = '500~999';
                } elseif ($itm->deposit_user_amount >= 100 && $itm->deposit_user_amount<=499.99) {
                    $tag_key = '100~499';
                } elseif ($itm->deposit_user_amount >= 10 && $itm->deposit_user_amount<=99.99) {
                    $tag_key = '10~99';
                } elseif ($itm->deposit_user_amount > 0 && $itm->deposit_user_amount<=9.99) {
                    $tag_key = '0~9';
                }
                if (isset($tag_key)) {
                    $opt[$tag_key]['user_cnt'] += 1;   //满足该区间充值的人数
                    $opt[$tag_key]['recharge_cnt'] += $itm->deposit_user_amount;   //充值总额
                    $opt[$tag_key]['withdraw_cnt'] += $itm->withdrawal_user_amount;   //提现总额
                    $opt[$tag_key]['bet_user_amount'] += $itm->bet_user_amount;    //投注总额
                    if ($itm->withdrawal_user_amount > 0) {
                        $opt[$tag_key]['withdraw_num'] += 1;    //提现人数
                    }
                    unset($tag_key);
                }
            }
        }
        //计算相关字段
        foreach ($opt as &$v) {
            $v['recharge_cnt'] = bcadd($v['recharge_cnt'], 0, 2);
            $v['withdraw_cnt'] = bcadd($v['withdraw_cnt'], 0, 2);
            $v['bet_user_amount'] = bcadd($v['bet_user_amount'], 0, 2);
            $v['profit_diff'] = bcsub($v['recharge_cnt'], $v['withdraw_cnt'], 2);    //充值提现差额
            if (empty($v['recharge_cnt']) || empty($v['recharge_cnt'] - $v['withdraw_cnt'])) {
                $v['ratio'] = "0%";
            } else {
                $v['ratio'] = bcdiv(($v['recharge_cnt'] - $v['withdraw_cnt']), $v['recharge_cnt'], 4) * 100 . "%";  //杀率
            }
            $v['recharge_ratio'] = empty($total_user) ? "0%" : bcdiv($v['user_cnt'], $total_user, 4) * 100 . "%";    //充值人数占比
            $v['coin_ratio'] = empty($total_coin) ? "0%" : bcdiv($v['recharge_cnt'], $total_coin, 4) * 100 . "%";    //充值金额占比
            $v['bet_user_amount'] = bcadd($v['bet_user_amount'], 0, 2);
        }
        unset($v);
        return $this->lang->set(0,[],$opt,['total_user'=>$total_user,'total_coin'=>bcmul($total_coin,1,2)]);

    }
};
