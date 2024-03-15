<?php
$ci = $app->getContainer();
$user_name_list=[];
foreach ($user_name_list as $v){
    $user_id = \DB::table('user')->where('name',$v)->value('id');
    if(!$user_id) continue;

    $uid_agent_id = \DB::table('user_agent')->where('user_id',$user_id)->value('uid_agent');
    if($uid_agent_id >0) continue;
    //if($uid_agent_id ==0) continue;

    $agent = new \Logic\User\Agent($ci);
    $profit = $agent->getProfit($user_id);
    if($profit){
        \DB::table('user_agent')->where('user_id',$user_id)->update(['profit_loss_value'=>$profit]);
    }
}

die('执行完毕');

