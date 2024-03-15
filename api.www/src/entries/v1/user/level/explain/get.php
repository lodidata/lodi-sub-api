<?php
use Utils\Www\Action;
return new class extends Action
{
    const TITLE = '会员等级-等级特权展示';
    const TAGS = "会员等级";
    const SCHEMAS = [
        [
            "id" => "int(required) #ID",
            "name" => "string(required) #等级名称",
            "level" => "string(required) #等级",
            "icon" => "string(required) #等级图标",
            "deposit_money" => "int(required) #晋升条件  存款金额",
            "lottery_money" => "int(required) #晋升条件  下注金额",
            "promote_handsel" => "int(required) #赠送彩金",
            "transfer_handsel" => "float(required) #转卡彩金百分比",
            "draw_count" => "int(required) #赠送抽奖次数",
            "upgrade_dml_percent" => "float(required) #打码量",
            "monthly_money" => "int(required) #月俸禄奖金，以分为单位",
            "monthly_percent" => "float(required) #月俸禄晋升条件",
        ],
    ];
    public $rebot_way = [
        'loss',     //按当日亏损额回水
        'betting',  //按当日投注额回水
    ];

    public $rebot_way_type = [
        'percentage',   //返现百分比
        'fixed',        //返现固定金额
    ];
    const STATUS= [
        'unissued'  => 1,   //未发放
        'unclaimed' => 2,   //未领取
        'received'  => 3,   //已领取
    ];
    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $data            = \DB::table('user_level')->orderBy('level','ASC')->get()->toArray();
        $info            = $this->getRebetConfByUserLevel();//获取当前等级日返水 最高返水比例
        $re              = [];
        $user_id         = $this->auth->getUserId();
        $week_award_day  = $this->redis->get(\Logic\Define\CacheKey::$perfix['week_award_day']);//周薪发放时间 天
        $week_award_time = $this->redis->get(\Logic\Define\CacheKey::$perfix['week_award_time']);//周薪发放时间 时分
        $user_data       = \DB::table('user_data')->where('user_id',$user_id)->first(['order_amount','deposit_amount']);
        $user            = \DB::table('user')->where('id',$user_id)->first(['ranting']);
        foreach ($data as $k => $val){
            $tmp['current_level']  = '';
            $tmp['next_level']     = isset($data[$k+1]->name) ? $data[$k+1]->name : '';
            $tmp['id'] = $val->id;
            $tmp['name'] = $val->name;
            $tmp['level'] = $val->level;
            $tmp['icon'] = showImageUrl($val->icon);
            $tmp['background']       = showImageUrl($val->background);
            $tmp['level_background'] = showImageUrl($val->level_background);
//            $tmp['deposit_money'] = $val->deposit_money;
//            $tmp['lottery_money'] = $val->lottery_money;
            $tmp['deposit_money'] = isset($data[$k+1]->deposit_money) ? $data[$k+1]->deposit_money : $val->deposit_money;
            $tmp['lottery_money'] = isset($data[$k+1]->lottery_money) ? $data[$k+1]->lottery_money : $val->lottery_money;
            $tmp['promote_handsel'] = $val->promote_handsel;
            $tmp['transfer_handsel'] = $val->transfer_handsel;
            $tmp['draw_count'] = $val->draw_count;
            $tmp['upgrade_dml_percent'] = $val->upgrade_dml_percent;
            $tmp['monthly_money'] = $val->monthly_money;//月俸禄奖金，以分为单位
            $tmp['monthly_percent'] = $val->monthly_percent;//月俸禄晋升条件
            $tmp['monthly_recharge'] = $val->monthly_recharge;//月俸禄晋升条件
            $tmp['week_money']       = $val->week_money;//周薪奖金，以分为单位
            $tmp['fee']              = $val->fee.'%' ?? 0;
            $tmp['week_award_day']   = 0;//X天后领取
            $tmp['week_time']        = 0;//当天剩余时分
            $tmp['welfare']          = json_decode($val->welfare,true);//福利特权信息
            //status状态值 1未达标 2 未领取 3 已领取  晋升彩金保留未领取状态和已领取状态
            $level_winnings          = \DB::table('user_level_winnings')->where('user_id',$user_id)->where('level',$val->level)->first();//获取晋升彩金
            $tmp['level_money']      = $level_winnings->money ?? 0;
            $tmp['winnings_status']  = $level_winnings->status ?? self::STATUS['unissued'];
            $tmp['order_amount']     = $user_data->order_amount ?? 0;
            $tmp['deposit_amount']   = $user_data->deposit_amount ?? 0;
            $tmp['bet_water']        = isset($info[$val->level]) ? $info[$val->level].'%' : 0;
            $tmp['week_award_id']    = 0;
            $tmp['monthly_award_id'] = 0;
            //如有多条取最新一条发放数据
            $user_week_award             = \DB::table('user_week_award')->where('user_id',$user_id)->where('level',$val->id)->orderByDesc('id')->first(['status','id']);
            $user_monthly_award          = \DB::table('user_monthly_award')->where('user_id',$user_id)->where('level',$val->id)->orderByDesc('id')->first(['status','id']);
            $tmp['week_status']    = $user_week_award    -> status ?? self::STATUS['unissued'];
            $tmp['monthly_status'] = $user_monthly_award -> status ?? self::STATUS['unissued'];
            //当前等级处理周薪、月薪领取状态等。（除当前等级月薪周薪都不保留,未领取过期(周薪过了当天，刷新显示下周领取发放时间)）
            if ($val->level == $user->ranting){
                $tmp['current_level']            = $val->name;
                $tmp['week_award_id']            = $user_week_award -> id ?? 0;
                $tmp['monthly_award_id']         = $user_monthly_award -> id ?? 0;
                //已发放不显示发放日期
               if ($tmp['week_status'] != self::STATUS['unclaimed']){
                   $tmp['week_award_day']        = \Utils\Utils::GetWeekDay($week_award_day);
                   $tmp['week_time']             = bcadd(strtotime($week_award_time),30);
               }
            }else{
                if ($tmp['week_status']   == self::STATUS['unclaimed']) $tmp['week_status']    = self::STATUS['unissued'];
                if ($tmp['monthly_status']== self::STATUS['unclaimed']) $tmp['monthly_status'] = self::STATUS['unissued'];
            }

            // 分割线&文字颜色
            $tmp['split_line']  = showImageUrl($val->split_line);
            $tmp['font_color']  = $val->font_color;

            $re[] = $tmp;
        }
        return $this->lang->set(0, [],  $re);
    }

    /**
     * 依据用户层级拿 整理数据以层级为下标，其它的返设置
     * @return array|bool
     */
    public function getRebetConfByUserLevel($gid = 0){

        $field = [
            'rebet.user_level_id',
            'rebet.rebot_way',
            'rebet.rebet_ceiling',
            'rebet.rebet_desc',
            'rebet.rebet_condition'
        ];

        // 游戏配置
        $data = \DB::table('rebet_config AS rebet')
            ->where('rebet.status_switch', 1)
            ->get($field)->toArray();
        // 查询会员层级
        $user_level = \DB::table('user_level')->get(['id','level'])->toArray();
        if(!$user_level ||!$data) {
            return false;
        }
        $user_level = array_column($user_level, NULL, 'id');
        $res = [];

        foreach ($data as $val) {
            if($val->rebot_way && isset($user_level[$val->user_level_id])) {
                $l = $user_level[$val->user_level_id]->level;
                $res[$l][] = (array)$val;
            }
        }
        foreach ($res as $key => $item) {
            foreach ($item as $val){
                $ruleData = json_decode($val['rebot_way'], true);
                    // 只计算betting
                    if($ruleData['type'] != $this->rebot_way[1]){
                        unset($data[$key]);
                        continue;
                    }
                    // 只计算按流水百分比
                    if($ruleData['data']['status'] != $this->rebot_way_type[0]){
                        unset($data[$key]);
                        continue;
                    }

                foreach ($ruleData['data']['value'] as $v){
                        $bet_value[] = explode(';',$v)[1];
                    }

            }
            $level_bet[$key] = max($bet_value) ?? 0;
        }
        return $level_bet ?? [];
    }

};