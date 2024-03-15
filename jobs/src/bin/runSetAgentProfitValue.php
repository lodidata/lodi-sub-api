<?php

/*$data = [
    'profit_loss_value' => '{"GAME":"30","LIVE":"30","SPORT":"30","QP":"30","ESPORTS":"30","ARCADE":"30","TABLE":"30","BY":"30","CP":0,"HOT":0}'
];
$ids = \DB::table('user_agent')->whereRaw('profit_loss_value is null')->where('uid_agent', 0)->update($data);
echo $ids;
die;*/

//$agent_data = \DB::table('user_agent')->where('profit_loss_value','{"GAME":0,"LIVE":0,"SPORT":0,"QP":0,"ESPORTS":0,"ARCADE":0,"TABLE":0,"BY":0,"CP":0,"HOT":0}')->where('uid_agent', '>',0)->get();
$agent_data = \DB::table('user_agent')->where(function($query){
    $query->whereRaw('profit_loss_value is null')->orWhere('profit_loss_value', '=', '{}');
}
)->where('uid_agent', '>',0)->get();

if($agent_data){
    foreach ($agent_data as $v){
        $v=(array)$v;
        if($v['id'] >0){
            $profit_loss_value = \DB::table('user_agent')->where('user_id',$v['uid_agent'])->value('profit_loss_value');
            $profit_loss_value = json_decode($profit_loss_value,true);
            $new_profit_loss_value = [];
            foreach ($profit_loss_value as $key => $value){
                $new_profit_loss_value[$key] = bcmul($value,0.9,2);
            }
            $new_profit_loss_value = json_encode($new_profit_loss_value);
            \DB::table('user_agent')->where('id',$v['id'])->update(['profit_loss_value'=>$new_profit_loss_value]);
        }
    }
}
die;