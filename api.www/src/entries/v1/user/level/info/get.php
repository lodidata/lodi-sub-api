<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = '会员等级-当前等级';
    const TAGS = "会员等级";
    const SCHEMAS = [
        "user_name" => "string(required) #用户名称",
        "user_avatar" => "string(required) #用户头像  ：现没有头像前端全部默认头像，这边为空或者NULL",
        "level_name" => "string(required) #当前等级名称",
        "level_icon" => "string(required) #当前等级图标",
        "level_current" => "string(required) #当前等级",
        "level_count" => "string(required) #等级总数",
        "order_amount" => "string(required) #投注金额",
        "deposit_amount" => "string(required) #存款金额、充值金额",
        "next_level_name" => "string(required) #下级等级名称",
        "next_level_icon" => "string(required) #下级等级图标",
        "next_level_order" => "string(required) #距离下级等级需投注金额",
        "next_level_deposit" => "string(required) #距离下级等级需充值金额",
        "next_percent_order" => "string(required) #距离下级投注量百分比",
        "next_percent_deposit" => "string(required) #距离下级充值百分比",
        "privilege" => [     //"array #当前等级特权",
            [
                "name" => "晋升彩金300元",
                "type" => 1   //产品说明写死，图片固定    --  1-晋升彩金,2-转卡彩金，3-回水条件，4-抽奖次数额外增加，
            ],
        ],
        "upgrade" => [     //升级弹窗数据
            [
                "popup" => "boolean(required) #true 升级弹窗  false 不管",
                "level_name" => "LV1",
                "privilege" => [
                    "获得晋升彩金300元",
                    "每笔充值可获0.5%彩金",
                    "回水条件升级",
                    "活动抽奖次数额外增加为0次"
                ]
            ],
        ]
    ];
    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_login = new Logic\User\User($this->ci);
        $user_id = $this->auth->getUserId();

        $user = \DB::table('user')->where('id',$user_id)->first();

        $user_data = \DB::table('user_data')->where('user_id',$user_id)->first();
        $level = \DB::table('user_level')->orderBy('level','ASC')->get()->toArray();
        //判断是否升级，升级则重新获取数据
        if($user_login->upgradeLevelMsg((array)$user)) {
            $user = \DB::table('user')->where('id',$user_id)->first();
            $user_data = \DB::table('user_data')->where('user_id',$user_id)->first();
            $level = \DB::table('user_level')->orderBy('level','ASC')->get()->toArray();
        }
        $current_level = null;
        $next_level = null;
        foreach ($level as $key => $val){
            if($current_level) {
                $next_level = $val;
                break;
            }
            if($val->level == $user->ranting) {
                $current_level = $val;
            }
        }
        //如若没有，默认给0级
        if(!$current_level) {
            $current_level = new \stdClass();
            $current_level->id = 0;
            $current_level->name = 'LV0';
            $current_level->icon = '';
            $current_level->level = 0;
            $current_level->lottery_money = 0;
            $current_level->deposit_money = 0;
        }
        $user_data->order_amount = $user_data->order_amount ? $user_data->order_amount : 0;
        $user_data->deposit_amount = $user_data->deposit_amount ? $user_data->deposit_amount : 0;
        $data['user_name'] = $user->name;
        $data['user_avatar'] =  '/static/user/avatar/default.jpg';
        $data['level_name'] = $current_level->name;
        $data['level_icon'] = showImageUrl($current_level->icon);
        $data['level_current'] = $current_level->level;
        $data['level_count'] = count($level);
        $data['order_amount'] = $user_data->order_amount;
        $data['deposit_amount'] = $user_data->deposit_amount;
        $data['next_level_name'] = $next_level ? $next_level->name : '';
        $data['next_level_icon'] = $next_level ? showImageUrl($next_level->icon) : '';
        $data['next_level_order'] = $next_level ? $next_level->lottery_money - $user_data->order_amount : 0;
        $data['next_level_deposit'] = $next_level ? $next_level->deposit_money - $user_data->deposit_amount : 0;
//        百分比不是用投注总量/（投注总量+距离下级投注金额）
        if($next_level) {
            $temp_order = $next_level->lottery_money - $current_level->lottery_money;
            $temp_deposit = $next_level->deposit_money - $current_level->deposit_money;
            $data['next_percent_order'] = $temp_order ? ($user_data->order_amount - $current_level->lottery_money) / $temp_order : 100;
            $data['next_percent_deposit'] = $temp_deposit ? ($user_data->deposit_amount - $current_level->deposit_money) / $temp_deposit : 100;
        }else {
            $data['next_percent_order'] = 100;
            $data['next_percent_deposit'] = 100;
        }
        $data['next_percent_order'] = $data['next_percent_order'] >= 1 ? 100 : intval($data['next_percent_order'] * 100);
        $data['next_percent_deposit'] = $data['next_percent_deposit'] >= 1 ? 100 : intval($data['next_percent_deposit'] * 100);
        $data['next_level_order'] = $data['next_level_order'] > 0 ? $data['next_level_order'] : 0;
        $data['next_level_deposit'] = $data['next_level_deposit'] > 0 ? $data['next_level_deposit'] : 0;
        $data['privilege'] = $current_level->id ? $user_login->LevelPrivilege((array) $current_level) : [];
//        $data['upgrade'] = $user_login->upgradeLevelWindows($user_id);
        return $this->lang->set(0,[],$data,['upgrade'=>$user_login->upgradeLevelWindows($this->auth->getUserId())]);
    }
};