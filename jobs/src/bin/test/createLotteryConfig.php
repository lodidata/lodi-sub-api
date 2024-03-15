<?php
/**
 * Created by PhpStorm.
 * User: benchan
 * Date: 2019/8/17
 * Time: 14:07
 */
use Model\Hall;
use Model\Room;
use Model\HotLottery;
use Model\RebetConfig;
use Model\LotteryPlayOdds;

if(count($argv) < 4 ){
    exit('缺少参数');
}
//$lottery_pid = $argv[2];
//$similar_lottery_id = $argv[3];
//$lottey_name = $argv[4];
//$lottey_type = $argv[5];
//$interval = $argv[6];

$lottery_id = $argv[2] ;
$similar_lottery_id = $argv[3] ;
$action = $argv[4] ;

$actions = ['copyHotLottery','copyHall','copyRebetConfig','copyLotteryRoom','copyLotteryPlayOdds'];

if($lottery_id && $similar_lottery_id && $action && in_array($action,$actions)){
    $action($lottery_id,$similar_lottery_id);
    exit('success');
}
exit('fail');


function copyHall($lottery_id,$similar_lottery_id){
    $similar_lottery_halls = Hall::selectRaw("hall_level,lottery_id,hall_name,rebet_config,rebet_desc,min_bet,max_bet,min_balance,is_pc,rebot_min,rebot_max,rebot_list,rebet_condition,type,per_max,rebot_way,rebet_ceiling")->where('lottery_id',$similar_lottery_id)->get()->toArray();
    foreach ($similar_lottery_halls as &$similar_lottery_hall){
        $similar_lottery_hall['lottery_id'] = $lottery_id;
    }
    DB::table('hall')->insert($similar_lottery_halls);
}

function copyRebetConfig($lottery_id,$similar_lottery_id){

    $lottery_halls = Hall::where('lottery_id',$lottery_id)->get()->groupBy('hall_level')->toArray();
    $copyRebetConfigs =  DB::table('rebet_config as rc')
        ->leftJoin('hall as h','rc.hall_id','=','h.id')
        ->where('h.lottery_id',$similar_lottery_id)
        ->get()
        ->toArray();
    $row = [];
    $rebetConfig = [];
    foreach ($copyRebetConfigs as $copyRebetConfig){
        $row['user_level_id'] = $copyRebetConfig->user_level_id;
        $row['hall_id'] = $lottery_halls[$copyRebetConfig->hall_level][0]['id'];
        $row['rebet_desc'] = $copyRebetConfig->rebet_desc;
        $row['rebet_condition'] = $copyRebetConfig->rebet_condition;
        $row['rebet_ceiling'] = $copyRebetConfig->rebet_ceiling;
        $row['rebot_way'] = $copyRebetConfig->rebot_way;
        $row['game3th_id'] = $copyRebetConfig->game3th_id;
        $row['status_switch'] = $copyRebetConfig->status_switch;

        array_push($rebetConfig,$row);
    }
    DB::table('rebet_config')->insert($rebetConfig);

}

function copyHotLottery($lottery_id,$similar_lottery_id){
    $copyHotLotterys = HotLottery::where('lottery_id', $similar_lottery_id)->selectRaw('lottery_id, type , state , timeType , sort')->get()->toArray();
    foreach ($copyHotLotterys as &$copyHotLottery){
        $copyHotLottery['lottery_id'] = $lottery_id ;
    }

    DB::table('hot_lottery')->insert($copyHotLotterys);
}

function copyLotteryRoom($lottery_id,$similar_lottery_id){
    if(empty($lottery_id) || empty($similar_lottery_id)){
        return false;
    }
    $lottery_halls = Hall::where('lottery_id',$lottery_id)->get()->groupBy('hall_level')->toArray();
    $similar_lottery_halls = DB::table('hall')->where('lottery_id',$similar_lottery_id)->get()->toArray();
    foreach ($similar_lottery_halls as $similar_lottery_hall){
        $similar_rooms = Room:: selectRaw("`lottery_id`, `hall_id`, `room_name`, `room_level`, `number`")
            ->where('hall_id',$similar_lottery_hall->id)
            ->where('lottery_id',$similar_lottery_id)->get()->toArray();
        foreach ($similar_rooms as &$similar_room){
            $similar_room['hall_id'] = $lottery_halls[$similar_lottery_hall->hall_level][0]['id'];
            $similar_room['lottery_id'] = $lottery_id;

        }
        DB::table('room')->insert($similar_rooms);
    }
}

function copyLotteryPlayOdds($lottery_id,$similar_lottery_id){

    if(empty($lottery_id) || empty($similar_lottery_id)){
        return false;
    }

    $lottery_halls = Hall::where('lottery_id',$lottery_id)->get()->groupBy('hall_level')->toArray();

    $similar_lottery_halls = DB::table('hall')->where('lottery_id',$similar_lottery_id)->get()->toArray();
    foreach ($similar_lottery_halls as $similar_lottery_hall){
        $lottery_play_odds = LotteryPlayOdds:: selectRaw("`name`, `hall_id`, `play_id`, `lottery_id`, `lottery_pid`, `play_sub_id`, `odds`, `max_odds`")
            ->where('hall_id',$similar_lottery_hall->id)
            ->where('lottery_id',$similar_lottery_id)->get()->toArray();
        foreach ($lottery_play_odds as &$lottery_play_odd){
            $lottery_play_odd['hall_id'] = $lottery_halls[$similar_lottery_hall->hall_level][0]['id'];
            $lottery_play_odd['lottery_id'] = $lottery_id;
        }
        DB::table('lottery_play_odds')->insert($lottery_play_odds);

    }

}

