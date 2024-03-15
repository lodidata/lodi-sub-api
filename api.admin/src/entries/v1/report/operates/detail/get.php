<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '经营报表详情';
    const QUERY = [
        'start_time' => 'date() #开始日期',
        'end_time' => 'date() #结束日期',
        'field_id'    => "int() #排序字段  默认id, 1=有效用户 2=注单数 3=投注额 4=派彩金额 5=总打码量 6=盈亏情况",
        'sort_way'    => "string() #排序规则 desc=降序 asc=升序",
    ];

    const PARAMS = [];
    const SCHEMAS = [
            [
                'name' => '棋牌',
                'list' =>
                    [
                        'game_type' => 'int #游戏type',
                        'game_name' => 'int #游戏name',
                        'game_order_user' => 'int #有效用户数',
                        'game_order_cnt' => 'int #注单数',
                        'game_bet_amount' => 'int #注单金额',
                        'game_prize_amount' => 'int #派奖金额',
                        'game_code_amount' => 'int #总打码量',
                        'game_deposit_amount' => 'int #总转入',
                        'game_withdrawal_amount' => 'int #总转出',
                    ],
            ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
 
        $game_types = $this->request->getParam('game_type');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $query = \DB::connection('slave')->table('rpt_order_amount');
        $query2 = \DB::connection('slave')->table('rpt_order_user');
        $games = $game_types ? explode(',',$game_types) : [];
        $games && $query->whereIn('game_type',$games) && $query2->whereIn('game_type',$games);
        $stime && $query->where('count_date','>=',$stime) && $query2->where('count_date','>=',$stime);
        $etime && $query->where('count_date','<=',$etime) && $query2->where('count_date','<=',$etime);
        $data = $query->groupBy('game_type')->orderBy('id','DESC')->get([
            'game_type',
            'game_name',
            \DB::raw('sum(game_order_cnt) as game_order_cnt'),
            \DB::raw('sum(game_bet_amount) as game_bet_amount'),
            \DB::raw('sum(game_prize_amount) as game_prize_amount'),
            \DB::raw('sum(game_code_amount) as game_code_amount'),
            \DB::raw('sum(game_deposit_amount) as game_deposit_amount'),
            \DB::raw('sum(game_withdrawal_amount) as game_withdrawal_amount'),
        ]);
        if(!$data) return $this->lang->set(0);
        $g = \Model\Admin\GameMenu::where('pid','!=',0)->get(['type','pid'])->toArray();
        $g2 = \Model\Admin\GameMenu::where('pid',0)->get(['id','name'])->toArray();
        $g = array_column($g,NULL,'type');
        $g2 = array_column($g2,NULL,'id');
        $res = [];

        //排序1=有效用户 2=注单数 3=投注额 4=派彩金额 5=总打码量 6=盈亏情况
        $field_id = $this->request->getParam('field_id', '');
        $sort_way = $this->request->getParam('sort_way', 'asc');
        if(!in_array($sort_way, ['asc', 'desc'])) $sort_way = 'asc';
        switch ($field_id) {
            case 1:
                $field_id = 'game_order_user';
                break;
            case 2:
                $field_id = 'game_order_cnt';
                break;
            case 3:
                $field_id = 'game_bet_amount';
                break;
            case 4:
                $field_id = 'game_prize_amount';
                break;
            case 5:
                $field_id = 'game_code_amount';
                break;
            case 6:
                $field_id = 'game_order_profit';
                break;
            default:
                $field_id = '';
                break;
        }

        foreach ($data as $val) {
            $val = (array)$val;
            $val['game_name'] = $this->lang->text($val['game_type']);
            if($val['game_type'] == 'ZYCPSTA') {
                $val['game_name'] = 'TG彩票';
            }
            $tmp = clone $query2;
            $val['game_order_user'] = $tmp->where('game_type',$val['game_type'])->distinct()->count('user_id');
            $val['game_order_profit'] = sprintf("%0.2f",$val['game_bet_amount'] - $val['game_prize_amount']);
            $res[$g[$val['game_type']]['pid']]['name'] = $g2[$g[$val['game_type']]['pid']]['name'] ?? '';
            $res[$g[$val['game_type']]['pid']]['list'][] = $val;
        }

        $res = array_values($res);
        foreach ($res as $key => $value) {
            if(!empty($field_id))
            {   
                $volume = array_column($res[$key]['list'], $field_id);
                if($sort_way == 'asc')
                {
                    array_multisort($volume, SORT_ASC, $res[$key]['list']);
                }else{
                    array_multisort($volume, SORT_DESC, $res[$key]['list']);
                }
            }
        }

        return $res;
    }

};