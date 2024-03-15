<?php
use Utils\Www\Action;
return new class extends Action
{
    const TITLE = '会员等级-是否有奖励待领取';
    const TAGS = "会员等级";
    const SCHEMAS = [
        [],
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

        $data = \DB::table('user_level')->orderBy('level','ASC')->get()->toArray();
        $user_id = $this->auth->getUserId();
        $user = \DB::table('user')->where('id',$user_id)->first(['ranting']);

        $have_reward = FALSE;
        foreach ($data as $k => $val){
            //status状态值 1未达标 2 未领取 3 已领取  晋升彩金保留未领取状态和已领取状态
            $level_winnings = \DB::table('user_level_winnings')->where('user_id',$user_id)->where('level',$val->level)->first();
            if($level_winnings && $level_winnings->status == 2) {

            	$have_reward = TRUE;
            	break;
            }
      
            if($user->ranting == $val->level) {
                $user_week_award = \DB::table('user_week_award')->where('user_id',$user_id)->where('level',$val->id)->orderByDesc('id')->first(['status','id']);
                if($user_week_award && $user_week_award->status == 2) {
                	$have_reward = TRUE;
                	break;
                }

                $user_monthly_award = \DB::table('user_monthly_award')->where('user_id',$user_id)->where('level',$val->id)->orderByDesc('id')->first(['status','id']);
                if($user_monthly_award && $user_monthly_award->status == 2) {
      
                	$have_reward = TRUE;
                	break;
                }
            }
        }
        return $this->lang->set(0, [],  ['have_reward' => $have_reward]);
    }

};