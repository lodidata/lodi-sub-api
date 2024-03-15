<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '游戏运营报表';
    const QUERY = [
        'start_time' => 'date() #开始日期',
        'end_time'   => 'date() #结束日期',
        'game_type'  => "string() #游戏类型",
        'game_operator' => "string() #运营商",
        'status' => "string() #状态enabled-启用中，disabled-禁用中",
    ];

    const PARAMS = [];
    const SCHEMAS = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $page = $this->request->getParam('page');
        $page_size = $this->request->getParam('page_size');
        $start_time = $this->request->getParam('start_time', '');
        $end_time = $this->request->getParam('end_time', '');
        $game_operator = $this->request->getParam('game_operator', '');    //运营商
        $game_name = $this->request->getParam('game_name', '');            //游戏名称
        $game_id = $this->request->getParam('id', '');                     //游戏大分类id
        $status = $this->request->getParam('status', '');                  //状态：enabled-启用中，disabled-禁用中

        if (empty($game_id)) {
            return $this->lang->set(10010);
        }
        //时间为空时候，查询近31天数据
        if (empty($start_time) || empty($end_time)) {
            $curDay = date("Y-m-d");
            $start_time = date("Y-m-d", strtotime("-30 day", strtotime($curDay)));
            $end_time = date("Y-m-d");
        }
        //查询时间范围不能超过31天
        if ((strtotime($end_time) - strtotime($start_time))/86400 > 31) {
            return $this->lang->set(207);
        }

        //排序字段
        $field_name = $this->request->getParam('field_name', '');
        $sort_way = $this->request->getParam('sort_way', 'asc');
        if (!in_array($sort_way, ['asc', 'desc'])) $sort_way = 'asc';
        //排序字段：rename-游戏名称，game_order_cnt-注单数，game_order_user-投注人数，game_order_user-投注人数，winner_num-赢家人数，winner_rate-赢家占比，
        //game_bet_amount-总投资额，game_prize_amount-总派奖额，yk-盈亏情况，RTP-RTP返还率
        if (!in_array($field_name, ['rename','game_order_cnt','game_order_user','winner_num','winner_rate','game_bet_amount','game_prize_amount','yk','RTP'])) $field_name = 'rename';    //默认根据游戏类型升序

        $result = [];   //返回数据

        //获取查询分类下的所有子游戏列表
        $game_sql = \DB::connection('slave')->table('game_menu')->selectRaw("id,pid,type,`name`,alias,`rename`,status")->whereRaw("pid=?",[$game_id]);
        if ($game_operator) {    //过滤运营商
            $game_sql->where('alias', $game_operator);
        }
        if ($game_name) {    //过滤游戏名称
            $game_sql->where('rename', $game_name);
        }
        if ($status) {
            $game_sql->where('status', $status);
        }
        $total = $game_sql->count();
        $game_list = $game_sql->forPage($page,$page_size)->get()->toArray();

        $all_game_type = [];   //查询大分类下的所有子游戏类型列表
        $res_game_type = [];   //返回给页面的游戏类型下拉框
        $res_operation = [];   //返回给页面的运营商下拉框
        foreach ($game_list as $g) {
            $result[$g->id] = [
                "id" => $g->id,
                "type" => $g->type,
                "rename" => $g->rename,
                "alias" => $g->alias,
                "status" => $g->status,
                'game_order_cnt' => 0,       //注单数
                'game_bet_amount' => 0,      //投注额
                'game_prize_amount' => 0,    //派奖额
                'game_order_user' => 0,      //投注人数
                'winner_num' => 0,           //赢家数
                'winner_rate' => 0,          //赢家占比
                'yk' => 0,                   //盈亏情况
                'RTP' => 0,                  //RTP返还率
            ];
            $res_game_type[$g->type] = $g->rename;
            $res_operation[$g->alias] = $g->alias;
            $all_game_type[] = $g->type;
        }

        //每个游戏类型的注单数、赢家人数、赢家占比、投注总额、派奖总额、盈亏情况、RTP
        $gids = array_column($result, 'id');
        $game_data = DB::connection('slave')->table('order_game_user_middle')->selectRaw('play_id,SUM(num) as game_order_cnt, SUM(bet) as game_bet_amount, SUM(send_money) as game_prize_amount')
            ->whereIn('play_id',$gids)->whereRaw('date>=? and date<=?', [$start_time, $end_time])->groupBy(['play_id','user_id'])->get()->toArray();
        if (!empty($game_data)) {
            foreach ($game_data as $itm) {
                if (isset($result[$itm->play_id])) {
                    $result[$itm->play_id]['game_order_user'] += 1;     //投注人数
                    $result[$itm->play_id]['game_order_cnt'] += intval($itm->game_order_cnt);         //注单数
                    $result[$itm->play_id]['game_bet_amount'] += intval($itm->game_bet_amount);       //投资总额
                    $result[$itm->play_id]['game_prize_amount'] += intval($itm->game_prize_amount);   //派彩总额
                    if (intval($itm->game_prize_amount) > intval($itm->game_bet_amount)) {             //赢家数
                        $result[$itm->play_id]['winner_num'] += 1;
                    }
                }
            }
        }
        //赢家占比、盈亏情况、RTP
        foreach ($result as $k=>$v) {
            $result[$k]['winner_rate'] = empty($result[$k]['game_order_user']) ? 0 : bcdiv(($result[$k]['game_order_user']-$result[$k]['winner_num']), $result[$k]['game_order_user'], 2) * 100; //赢家占比 = (投注人数-赢家数)/投注人数*100
            $result[$k]['yk'] = bcsub($result[$k]['game_bet_amount'], $result[$k]['game_prize_amount'], 2);  //盈亏 = 投资额-派彩额
            $result[$k]['RTP'] = empty($result[$k]['game_bet_amount']) ? 0 : bcdiv($result[$k]['game_prize_amount'], $result[$k]['game_bet_amount'], 2) * 100;  //RTP =  派彩额/下注金额*100
        }

        //结果排序
        $field_column = array_column($result, $field_name);
        if ($sort_way == "asc") {
            array_multisort($field_column, SORT_ASC, $result);
        } else {
            array_multisort($field_column, SORT_DESC, $result);
        }

        $attr = [
            'total' => $total,
            'page' => $page,
            'page_size' => $page_size,
            'game_type' => $res_game_type,    //游戏类型
            'operation' => $res_operation,    //运营商
        ];
        return $this->lang->set(0,[],$result,$attr);
    }

};