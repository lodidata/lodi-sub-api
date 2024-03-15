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
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 20);
        $start_time = $this->request->getParam('start_time', '');
        $end_time = $this->request->getParam('end_time', '');
        $game_type = $this->request->getParam('game_type', '');            //游戏类型
        $game_operator = $this->request->getParam('game_operator', '');    //运营商
        $status = $this->request->getParam('status', '');                  //状态：enabled-启用中，disabled-禁用中

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
        //排序字段：rename-游戏类型，alias-运营商，game_sub_cnt-有效游戏数，game_order_cnt-注单数,game_order_user-投注人数，winner_num-赢家人数，winner_rate-赢家占比，
        //game_bet_amount-总投资额，game_prize_amount-总派奖额，yk-盈亏情况，RTP-RTP返还率
        if (!in_array($field_name, ['rename','alias','game_sub_cnt','game_order_cnt','game_order_user','winner_num','winner_rate','game_bet_amount','game_prize_amount','yk','RTP'])) $field_name = 'rename';    //默认根据游戏类型升序

        $result = [];   //返回数据

        //一级游戏分类
        $ctg_sql = DB::connection('slave')->table('game_menu')->selectRaw('id,pid,type,`name`,alias,`rename`,status')->where("pid",0);
        if ($game_type) {
            $ctg_sql->where('type', $game_type);
        }
        $category = $ctg_sql->orderBy('rename')->get()->toArray();
        $category_arr = [];
        foreach ($category as $c) {
            $category_arr[$c->id] = [
                'id' => $c->id,
                'type' => $c->type,
                'alias' => $c->alias,
                'rename' => $c->rename,
                'status' => $c->status
            ];
        }
        //获取每个游戏类型下的所有运营商分类
        $opt_sql = \DB::connection('slave')->table('game_menu')->selectRaw("id,pid,type,`name`,alias,`rename`,status")->whereIn('pid',array_keys($category_arr));
        if ($game_operator) {
            $opt_sql->where('alias', $game_operator);
        }
        if ($status) {
            $opt_sql->where('status', $status);
        }
        $total = $opt_sql->count();
        $opt_game = $opt_sql->forPage($page,$page_size)->get()->toArray();
        foreach ($opt_game as $g) {
            $p_rename = str_replace($g->type, '', $category_arr[$g->pid]['rename']);    //产品要求去掉字符串中英文运营商部分字符
            $result[$g->id] = [
                "id" => $g->id,
                "type" => $g->type,
                "rename" => $p_rename,      //中文名称字段中去掉了英文字符部分
                "alias" => $g->alias,
                "status" => $g->status,
                'game_sub_cnt' => 0,         //有效游戏数
                'game_order_cnt' => 0,       //注单数
                'game_bet_amount' => 0,      //投注额
                'game_prize_amount' => 0,    //派奖额
                'game_order_user' => 0,      //投注人数
                'winner_num' => 0,           //赢家数
                'winner_rate' => 0,          //赢家占比
                'yk' => 0,                   //盈亏情况
                'RTP' => 0,                  //RTP返还率
            ];
        }

        //每个运营商下每个游戏大分类下的有效游戏数
        $gid = array_keys($result);
        $game_cnt = DB::connection('slave')->table('game_3th')->selectRaw('game_id,count(*) as cnt')->whereIn('game_id',$gid)->groupBy(['game_id'])->get()->toArray();
        if (!empty($game_cnt)) {
            foreach ($game_cnt as $g_cnt) {
                if (isset($result[$g_cnt->game_id])) {
                    $result[$g_cnt->game_id]['game_sub_cnt'] = $g_cnt->cnt;
                }
            }
        }

        //每个运营商下每个游戏大分类下的投注人数、注单数、投注总额、派奖总额、
        $game_data = DB::connection('slave')->table('order_game_user_middle')->selectRaw('play_id,SUM(num) as game_order_cnt, SUM(bet) as game_bet_amount, SUM(send_money) as game_prize_amount')
            ->whereIn('play_id',$gid)->whereRaw('date>=? and date<=?', [$start_time, $end_time])->groupBy(['play_id','user_id'])->get()->toArray();
        if (!empty($game_data)) {
            foreach ($game_data as $gd) {
                if (isset($result[$gd->play_id])) {
                    $result[$gd->play_id]['game_order_user'] += 1;     //投注人数
                    $result[$gd->play_id]['game_order_cnt'] += intval($gd->game_order_cnt);          //注单数
                    $result[$gd->play_id]['game_bet_amount'] += intval($gd->game_bet_amount);        //投资总额
                    $result[$gd->play_id]['game_prize_amount'] += intval($gd->game_prize_amount);    //派彩总额
                    if (intval($gd->game_prize_amount) > intval($gd->game_bet_amount)) {             //赢家数
                        $result[$gd->play_id]['winner_num'] += 1;
                    }
                }
            }
        }

        //赢家占比、盈亏情况、RTP
        foreach ($result as &$v) {
            //投注额、派奖额、盈亏要除以100
            $v['game_bet_amount'] = empty($v['game_bet_amount']) ? 0 : bcdiv($v['game_bet_amount'], 100, 2);
            $v['game_prize_amount'] = empty($v['game_prize_amount']) ? 0 : bcdiv($v['game_prize_amount'], 100, 2);
            $v['winner_rate'] = empty($v['game_order_user']) ? 0 : bcmul(bcdiv(($v['game_order_user']-$v['winner_num']), $v['game_order_user'], 2), 100); //赢家占比 = (投注人数-赢家数)/投注人数*100
            $v['yk'] = bcsub($v['game_bet_amount'], $v['game_prize_amount'], 2);  //盈亏 = 投资额-派彩额
            $v['RTP'] = empty($v['game_bet_amount']) ? 0 : bcmul(bcdiv($v['game_prize_amount'], $v['game_bet_amount'], 2), 100);  //RTP =  派彩额/下注金额*100
        }
        unset($v);

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
        ];
        return $this->lang->set(0,[],$result,$attr);
    }

};