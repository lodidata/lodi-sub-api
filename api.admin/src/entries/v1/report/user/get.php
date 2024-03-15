<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '用户报表';
    const DESCRIPTION = '获取报表报表，不包括所有下级的数据';
    const HINT = '统计时间内没有数据变化的不予以展示';
    const QUERY = [
        'page'       => 'int(required) #当前页',
        'page_size'  => 'int(required) #每页数量',
        'start_time' => 'string() #开始日期',
        'end_time'   => 'string() #结束日期',
        'order_by'   => 'string() #排序字段',
        'order_rule' => 'string() #排序规则 desc|asc',
    ];

    const PARAMS = [];
    const SCHEMAS = [
            [
                'user_name'        => 'string #用户名',
                'user_id'          => 'int #用户ID',
                'child_agent'      => 'int #下级人数',
                'first_deposit'    => 'int #首充用户数',
                'deposit'          => 'int #存款金额',
                'withdraw'         => 'int #取款金额',
                'deposit-withdraw' => 'int #存取款差',
                'pay_profit'       => 'int #投注总盈亏',
                'active_money'     => 'int #活动彩金',
                'hold_money'       => 'int #回水金额',
                'bkge_money'       => 'int #返佣总金额',
                'sum_profit'       => 'int #合计盈亏',
                'lottery_pay'      => 'int #彩票投注',
                'lottery_win'      => 'int #彩票中奖',
                'lottery_lose'     => 'int #彩票盈亏',
                'lottery_bkge'     => 'int #彩票返佣',
                'sport_pay'        => 'int #体育投注',
                'sport_win'        => 'int #体育中奖',
                'sport_lose'       => 'int #体育盈亏',
                'sport_bkge'       => 'int #体育返佣',
                'game_pay'         => 'int #电子投注',
                'game_win'         => 'int #电子中奖',
                'game_lose'        => 'int #电子盈亏',
                'game_bkge'        => 'int #电子返佣',
                'live_pay'         => 'int #视讯投注',
                'live_win'         => 'int #视讯中奖',
                'live_lose'        => 'int #视讯盈亏',
                'live_bkge'        => 'int #视讯返佣',
            ]
    ];

    public $url;

    public $param = [
        'proj'        => '',
        'user_id'     => 0,
        'agent_id'    => 0,
        'start'       => 0,
        'end'         => 0,
        'order_field' => 'team_number',
        'order_sort'  => 0,
        'page'        => 1,
        'page_size'   => 20,
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    private $reportDB;

    public function run($id = null) {

        $this->reportDB = \DB::connection('default');

        $user_name = $this->request->getParam('user_name');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $agent_id = $this->request->getParam('agent_id');
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);
        $order_by = $this->request->getParam('order_by', false);
        $order_rule = $this->request->getParam('order_rule', false);

        if (!$stime) {
            $stime = date('Y-m-d');
        }

        if (!$etime) {
            $etime = date('Y-m-d', strtotime('+1 day'));
        }

        if ($id) {
            $agent_id = $id;
        }

        $self = false;  //查询用户名不限级查，判断是否查询用户名

        if (!$agent_id && $user_name) {
            $self = true;

            //DBA 给的SQL直接查只能查顶级代理，所以查用户名以这种方式查
            $agent_id = $this->reportDB->table('user')
                                       ->where('name', '=', $user_name)
                                       ->value('id');

            if (!$agent_id) {
                return [];
            }
        }

        $res = $this->sql((int)$agent_id, (string)$user_name, (string)$stime, (string)$etime, (int)$page, (int)$page_size, $order_by, $order_rule);

        if ($res['data']) {
            $res['heji'][0]->inferisors_all = '--';

            if ($self) {
                //查用户名时只显示自己一条数据，合计为该用户的所有下级，所以要做处理
                $rsdata = $this->tiny($stime, $etime, $res['heji']);
                $tmp = $rsdata;

                $tmp[0]['user_name'] = $user_name;
                $tmp[0]['user_id'] = $agent_id;

                $tmp[0]['inferisors'] = \DB::table('user_agent')
                                           ->where('user_id', '=', $agent_id)
                                           ->value('inferisors_all');

                $rsdata[0]['inferisors'] = '--';
                $rsdata = array_merge($rsdata, $tmp);
            } else {
                $rsdata = array_merge($this->tiny($stime, $etime, $res['heji']), $this->tiny($stime, $etime, $res['data'], $agent_id));
            }

            $rs['number'] = $page;
            $rs['size'] = $page_size;
            $rs['total'] = $res['heji'][0]->count ?? 1;

            return $this->lang->set(0, [], $rsdata, $rs);
        }

        return [];
    }

    public function tiny($stime, $etime, $data, $agent_id = null) {
        $t = [];
        $rsdata = [];
        $user_name = $this->request->getParam('user_name');//查询单个用户名

        foreach ($data as $v) {
            $tmp['user_name'] = $v->user_name;  //用户名
            $tmp['user_id'] = $v->user_id;  //  用户ID

            $tmp['inferisors'] = $v->inferisors_all;  //团队人数

            $tmp['child_agent'] = $v->child_agent;  //下级人数

            $tmp['deposit'] = $v->deposit_money;  //存款
            $tmp['withdraw'] = $v->get_money;  //取款
            $tmp['deposit-withdraw'] = $v->deposit_withdraw;  //存取款差

            $tmp['pay_profit'] = $v->bet_earnlose;  //投注总盈亏
            $tmp['active_money'] = $v->coupon_money;  //活动彩金
            $tmp['hold_money'] = $v->return_money;  //回水金额
            $tmp['bkge_money'] = $v->total_backmoney;  //返佣总金额
            $tmp['sum_profit'] = $v->total_earnlose;  //合计盈亏

            $tmp['lottery_pay'] = $v->lottery_valid_bet_money;  //彩票投注
            $tmp['lottery_win'] = $v->lottery_win_prize;  //彩票中奖
            $tmp['lottery_lose'] = $v->lottery_valid_bet_money - $v->lottery_win_prize;  //彩票盈亏
            $tmp['lottery_bkge'] = $v->lottery_back_moeny;  //彩票返佣

            $tmp['sport_pay'] = $v->sport_valid_bet_money;  //体育投注
            $tmp['sport_win'] = $v->sport_win_prize;  //体育中奖
            $tmp['sport_lose'] = $v->sport_valid_bet_money - $v->sport_win_prize;  //体育盈亏
            $tmp['sport_bkge'] = $v->sport_back_moeny;  //体育返佣

            $tmp['game_pay'] = $v->game_valid_bet_money;  //电子投注
            $tmp['game_win'] = $v->game_win_prize;  //电子中奖
            $tmp['game_lose'] = $v->game_valid_bet_money - $v->game_win_prize;  //电子盈亏
            $tmp['game_bkge'] = $v->game_back_moeny;  //电子返佣

            $tmp['live_pay'] = $v->live_valid_bet_money;  //视讯投注
            $tmp['live_win'] = $v->live_win_prize;  //视讯中奖
            $tmp['live_lose'] = $v->live_valid_bet_money - $v->live_win_prize;  //视讯盈亏
            $tmp['live_bkge'] = $v->live_back_moeny;  //视讯返佣

            $tmp['turn_card'] = $v->turn_card_winnings;  //转卡彩金
            $tmp['promotion'] = $v->promotion_winnings;  //晋升彩金
            if(!empty($user_name)){//搜索用户
                $monthly_award_money = $this->reportDB->table('user_monthly_award')->where('user_name', $user_name)->whereBetween(DB::raw('DATE_ADD(award_date, INTERVAL 1 MONTH)'), [$stime, $etime])->value(DB::raw('SUM(award_money) as monthly_award_money'));
            }else if(empty($v->user_id)){//表示合计
                $monthly_award_money = $this->reportDB->table('user_monthly_award')->whereBetween(DB::raw('DATE_ADD(award_date, INTERVAL 1 MONTH)'), [$stime, $etime])->value(DB::raw('SUM(award_money) as monthly_award_money'));
            }else{//单个用户
                $monthly_award_money = $this->reportDB->table('user_monthly_award')->where('user_id', $v->user_id)->whereBetween(DB::raw('DATE_ADD(award_date, INTERVAL 1 MONTH)'), [$stime, $etime])->value(DB::raw('SUM(award_money) as monthly_award_money'));
            }
            $tmp['monthly_award_money'] = $monthly_award_money ?? 0;

            if ($agent_id == $v->user_id) {
                $t = $tmp;
                continue;
            }

            $rsdata[] = $tmp;
        }

        $t && array_unshift($rsdata, $t);

        return $rsdata;
    }

    public function sql(int $uid, string $search_name, string $stime, string $etime, int $page, int $size, $order_by = false, $order_rule = false) {
        $st = '';
        $sn = '';
        $p = ($page - 1) * $size;

        if ($uid) {
            $j = 'LEFT JOIN';
        } else {
            $j = 'JOIN';
        }

        //排序字段与查询SQL字段对应MAP
        $order_by_list = [
            'first_deposit' => 'first_deposit',
            //首充用户数

            'child_agent'      => 'child_agent',
            //下一级代理
            'inferisors'       => 'inferisors_all',
            //团队人数
            'deposit'          => 'deposit_money',
            //存款
            'withdraw'         => 'get_money',
            //取款
            'deposit-withdraw' => 'deposit_withdraw',
            //存取款差

            'pay_profit'   => 'bet_earnlose',
            //投注总盈亏
            'active_money' => 'coupon_money',
            //活动彩金
            'hold_mongey'  => 'return_money',
            //取款
            'bkge_money'   => 'total_backmoney',
            //取款
            'sum_profit'   => 'total_earnlose',
            //合计盈亏

            'lottery_pay'  => 'lottery_valid_bet_money',
            //彩票投注
            'lottery_win'  => 'lottery_win_prize',
            //彩票中奖
            'lottery_lose' => 'lottery_lose',
            //彩票盈亏
            'lottery_bkge' => 'lottery_back_moeny',
            //彩票返佣

            'live_pay'  => 'live_valid_bet_money',
            //视讯投注
            'live_win'  => 'live_win_prize',
            //视讯中奖
            'live_lose' => 'live_lose',
            //视讯盈亏
            'live_bkge' => 'live_back_moeny',
            //视讯返佣

            'game_pay'  => 'game_valid_bet_money',
            //电子投注
            'game_win'  => 'game_win_prize',
            //电子中奖
            'game_lose' => 'game_lose',
            //电子盈亏
            'game_bkge' => 'game_back_moeny',
            //电子返佣

            'sport_pay'  => 'sport_valid_bet_money',
            //体育投注
            'sport_win'  => 'sport_win_prize',
            //体育中奖
            'sport_lose' => 'sport_lose',
            //体育盈亏
            'sport_bkge' => 'sport_back_moeny',
            //体育返佣
            'turn_card' => 'turn_card_winnings',
            //体育返佣
            'promotion' => 'promotion_winnings',
        ];

        //排序规则
        $order = '';
        if (isset($order_by_list[$order_by]) && in_array($order_rule, [
                'desc',
                'asc',
            ])) {
            $order = 'ORDER BY `' . $order_by_list[$order_by] . '` ' . $order_rule;
        }

        if ($stime && $etime) {
            $st = " AND (t3.count_date >= '{$stime}' AND t3.count_date <= '{$etime}')";
        }

        if ($search_name) {
            $sn = " AND t4.`name`='{$search_name}' ";
            $join = 'LEFT JOIN';
        } else {
            $join = 'INNER JOIN';
        }

        $sql = "SELECT
                    t4.`name` user_name,
                    t1.`user_id`,
                    t1.inferisors_all,
                    t3.`agent_id` agent_id,
                    (SELECT COUNT(*) FROM `user` WHERE `agent_id` = t1.`user_id`) AS `child_agent`, 
                    SUM(`deposit_money`) deposit_money,
                    SUM(`get_money`) get_money,
                    (SUM(`deposit_money`) - SUM(`get_money`)) AS `deposit_withdraw`,
                    -SUM(lottery_earnlose + live_earnlose + game_earnlose+sport_earnlose) bet_earnlose,
                    SUM(`coupon_money`) coupon_money,
                    SUM(`return_money`) return_money,
                    SUM(lottery_back_moeny + live_back_moeny + game_back_moeny + sport_back_moeny) total_backmoney,
                    -SUM(lottery_earnlose + live_earnlose + game_earnlose + sport_earnlose) - SUM(coupon_money + promotion_winnings + turn_card_winnings + return_money + lottery_back_moeny + live_back_moeny + game_back_moeny + sport_back_moeny) total_earnlose,
                    SUM(`lottery_valid_bet_money`) lottery_valid_bet_money,
                    SUM(`lottery_win_prize`) lottery_win_prize,
                    (SUM(`lottery_valid_bet_money`) - SUM(`lottery_win_prize`)) AS lottery_lose,
                    SUM(`lottery_earnlose`) lottery_earnlose,
                    SUM(`lottery_back_moeny`) lottery_back_moeny,
                    SUM(`live_valid_bet_money`) live_valid_bet_money,
                    SUM(`live_win_prize`) live_win_prize,
                    SUM(`live_earnlose`) live_earnlose,
                    SUM(`live_back_moeny`) live_back_moeny,
                    (SUM(`live_valid_bet_money`) - SUM(`live_win_prize`)) AS live_lose,
                    SUM(`game_valid_bet_money`) game_valid_bet_money,
                    SUM(`game_win_prize`) game_win_prize,
                    SUM(`game_earnlose`) game_earnlose,
                    SUM(`game_back_moeny`) game_back_moeny,
                    (SUM(`game_valid_bet_money`) - SUM(`game_win_prize`)) AS game_lose,
                    SUM(`sport_valid_bet_money`) sport_valid_bet_money,
                    SUM(`sport_win_prize`) sport_win_prize,
                    SUM(`sport_earnlose`) sport_earnlose,
                    SUM(`sport_back_moeny`) sport_back_moeny,
                    (SUM(`sport_valid_bet_money`) - SUM(`sport_win_prize`)) AS sport_lose,
                    SUM(`turn_card_winnings`) turn_card_winnings,
                    SUM(`promotion_winnings`) promotion_winnings
                FROM
                    user_agent t1
                {$join} `user` t4 ON t1.user_id = t4.id
                {$j} rpt_userreport t3 ON t1.user_id = t3.user_id {$st}
                WHERE 1 {$sn}  GROUP BY t1.user_id {$order}";

        return [
            'data' => $this->reportDB->select($sql . " LIMIT $p, $size;"),
            'heji' => $this->reportDB->select($this->heji($sql)),
        ];
    }

    //合计总数据，第一条
    public function heji($sql) {
        $s = 'SELECT
                "合计" user_name,
                "" `user_id`,
                COUNT(*) AS count,
                SUM(inferisors_all) inferisors_all,
                SUM(`deposit_money`) deposit_money,
                SUM(`get_money`) get_money,
                SUM(`child_agent`) AS `child_agent`,
                (SUM(`deposit_money`) - SUM(`get_money`)) AS `deposit_withdraw`,
                SUM(bet_earnlose) bet_earnlose,
                SUM(`coupon_money`) coupon_money,
                SUM(`return_money`) return_money,
                SUM(total_backmoney) total_backmoney,
                SUM(total_earnlose) total_earnlose,
                SUM(`lottery_valid_bet_money`) lottery_valid_bet_money,
                SUM(`lottery_win_prize`) lottery_win_prize,
                SUM(`lottery_earnlose`) lottery_earnlose,
                SUM(`lottery_back_moeny`) lottery_back_moeny,
                SUM(`live_valid_bet_money`) live_valid_bet_money,
                SUM(`live_win_prize`) live_win_prize,
                SUM(`live_earnlose`) live_earnlose,
                SUM(`live_back_moeny`) live_back_moeny,
                SUM(`game_valid_bet_money`) game_valid_bet_money,
                SUM(`game_win_prize`) game_win_prize,
                SUM(`game_earnlose`) game_earnlose,
                SUM(`game_back_moeny`) game_back_moeny,
                SUM(`sport_valid_bet_money`) sport_valid_bet_money,
                SUM(`sport_win_prize`) sport_win_prize,
                SUM(`sport_earnlose`) sport_earnlose,
                SUM(`sport_back_moeny`) sport_back_moeny,
                SUM(`turn_card_winnings`) turn_card_winnings,
                SUM(`promotion_winnings`) promotion_winnings
              FROM (' . $sql . ') AS tmp';

        return $s;
    }
};