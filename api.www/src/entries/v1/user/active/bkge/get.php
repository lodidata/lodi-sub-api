<?php
use Utils\Www\Action;
use Model\Active;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "返佣活动";
    const DESCRIPTION = "返佣活动";
    const TAGS = "优惠活动";
    const SCHEMAS = [
       [
           'id'             => "int(required) #ID",
           'name'           => "string(required) #活动名称",
           'game_ids'       => "string(required) #返佣类目，游戏大类ID 如：4,15,16,17,22",
           'condition_opt'  => "string(required) #返佣条件（lottery-有效投注，deposit-充值，winloss-盈亏）",
           'data_opt'       => "string(required) #返佣数据规则（lottery-有效投注，deposit-充值，winloss-盈亏）",
           'game_names'     => [
               [
                   'id'     => "int(required) #游戏大类ID",
                   'name'   => "string(required) #游戏名称",
               ]
           ],
           'rule'           => [
               [
                   'active_bkge_id' => "int(required) #返佣活动ID",
                   'rule_name'      => "string(required) #代理级别",
                   'min_lottery'    => "string() #最小有效投注",
                   'max_lottery'    => "string() #最高有效投注",
                   'min_deposit'    => "string() #最小充值",
                   'max_deposit'    => "string() #最高充值",
                   'min_winloss'    => "string() #最小盈亏",
                   'max_winloss'    => "string() #最高盈亏",
                   'bkge_scale'     => "string() #返佣活动赔率",
               ]
           ]
       ]
   ];

    public function run() {
        $date = date('Y-m-d H:i:s');
        $data = \Model\Admin\ActiveBkge::where('status','enabled')->where('stime','<=',$date)->where('etime','>=',$date)->orderBy('id','desc')->get([
            'id',
            'name',
            'game_ids',
            'condition_opt',
            'data_opt',
        ])->toArray();
        $game = \Model\GameMenu::where('pid',0)->get(['id','name'])->toArray();
        $game = array_column($game,'name','id');
        $rule = \DB::table('active_bkge_rule')->orderBy('min_lottery')->get([
            'active_bkge_id',
            'rule_name',
            'min_lottery',
            'max_lottery',
            'min_deposit',
            'max_deposit',
            'min_winloss',
            'max_winloss',
            'bkge_scale',
        ])->toArray();
        foreach ($data as &$val){
            $val = (array)$val;
            switch ($val['data_opt']){
                case 'lottery' : $val['bkge_way'] = $this->lang->text('effective bet');break;
                case 'deposit' : $val['bkge_way'] = $this->lang->text('recharge');break;
                case 'winloss' : $val['bkge_way'] = $this->lang->text('profit and loss');break;
            }
            $val['game_names'] = implode('、',$this->getValues($game,$val['game_ids']));
            $val['rule'] = array_values($this->getValues($rule,[$val['id']],'active_bkge_id'));
        }
        return $data;
    }

    public function getValues($data,$keys,$column = null){
        if(!$keys) return [];
        $tmp = is_string($keys) ? explode(',',$keys) : $keys;
        $res = [];
        foreach ($data as $k=>$v){
            $v = is_object($v) ? (array)$v : $v;
            if(is_array($v)){
                $v['bkge_value'] = 10000 * $v['bkge_scale'] / 100; // 每W返佣
            }
            if(empty($column) && in_array($k,$tmp) || is_array($v) && in_array($v[$column],$tmp)){
                $res[$k] = $v;
            }
        }
        return $res;
    }
};