<?php

use Logic\Admin\BaseController;
use Model\Admin\LotteryChase;

return new class() extends BaseController {
    const TITLE       = '获取追号列表';
    const DESCRIPTION = '追号管理';
    const HINT        = '追号依整体考虑';
    const QUERY       = [
        'page'         => 'int #第几页',
        'page_size'    => 'int #每页多少条',
        'user_name'    => 'string #用户名',
        'chase_number' => 'string #追号单号',
        'lottery_id'   => 'int #彩种id',
        'state'        => 'enum[win,lose,created,cancel] #状态(win=已中奖,lose=未中奖,created=追号中,cancel=已撤单)',
        //        'origin' => 'enum[1,2,3] #来源，1:pc, 2:h5, 3:移动端',
        'start_time'   => 'datetime #建立时间',
        'end_time'     => 'datetime #结束时间',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            "user_name" => "用户名",
            "chase_number" => "追期编号",
            "origin" => "来源 pc / h5",
            "created" => "2017-05-01 14:01:47",
            "name" => "彩种",
            "play_type1_name" => "玩法",
            "play_number" => "04,05,06,07",
            "odds" => "赔率",
            "current_bet" => "392.00",
            "prize_stop" => "追号金额",
            "chase_amount" => "追期数",
            "lose_earn" => "输赢",
            "state" => "underway 进行中，open 已经开奖",
            "content" => "内容",
            "chase_type" => "追号类型",
        ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 20);
        $chase_number = $this->request->getParam('chase_number');
        $lottery_id = $this->request->getParam('lottery_id');
        $state = $this->request->getParam('state');
        $start_time = $this->request->getParam('start_time');
        $end_time = $this->request->getParam('end_time');
        $user_name = $this->request->getParam('user_name');
        $query = \Model\LotteryChaseOrder::where('tags', '!=', 7);
        $chase_number && $query->where('chase_number', $chase_number);
        $lottery_id && $query->where('lottery_id', $lottery_id);
        $state && $query->where('state', $state);
        $user_name && $query->where('user_name', $user_name);
        $start_time && $query->where('created', '>=', $start_time);
        $end_time && $query->where('created', '<=', $end_time . ' 23:59:59');
        $attributes['total'] = $query->count();
        $attributes['number'] = $page;
        $attributes['size'] = $page_size;

        $data = $query->orderBy('id', 'DESC')
                      ->forPage($page, $page_size)
                      ->get()
                      ->toArray();

        $re = [];
        $origins = [
            1 => 'PC',
            2 => 'H5',
            3 => 'APP',
            4 => 'APP',
        ];

        $states = [
            'complete' => '已结束',
            'cancel'   => '已撤单',
            'underway' => '进行中',
        ];

        foreach ($data as $v) {
            if (strstr($v['mode'], 'fast')) {
                $t = '快捷';
            } else if (strstr($v['mode'], 'std')) {
                $t = '标准';
            } else {
                $t = '';
            }

            if (strstr($v['hall_name'], '直播')) {
                $mode_str = '直播模式';
            } else if (strstr($v['hall_name'], '传统') || strstr($v['hall_name'], 'PC')) {
                $mode_str = "传统模式 <{$t}>";
            } else {
                $mode_str = "房间模式 <{$v['hall_name']}>";
            }

            $tmp['user_id']=$v['user_id'];
            $tmp['c/s'] = $v['complete_periods'] . '/' . $v['sum_periods'];
            $tmp['chase_number'] = (string)$v['chase_number'];
            $tmp['created'] = $v['created'];
            $tmp['hall_name'] = $v['hall_name'];
            $tmp['increment_bet'] = $v['increment_bet'];
            $tmp['mode_str'] = $mode_str;  //
            $tmp['name'] = $v['lottery_name'];
            $tmp['origin'] = $origins[$v['origin']] ?? '未知';
            $tmp['profit'] = $v['profit'];
            $tmp['send_money'] = $v['reward'];
            $tmp['state'] = $states[$v['state']];
            $tmp['user_name'] = $v['user_name'];
            $re[] = $tmp;
        }

        return $this->lang->set(0, [], $re, $attributes);
    }
};
