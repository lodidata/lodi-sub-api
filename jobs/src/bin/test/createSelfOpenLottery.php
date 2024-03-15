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
use Model\LotteryPlayLimitOdds;

/**
 * shell
 *  php .\runBin.php test/createSelfOpenLottery 10 112 100
 */

/*if(count($argv) < 8 ){
    exit('缺少参数');
}*/
//$lottery_pid = $argv[2];
$lottery_id = $argv[3];
//$lottey_name = $argv[4];
//$lottey_type = $argv[5];
//$interval = $argv[6];
//$similar_id = $argv[7];
$similar_id = $argv[4];
$lottery = DB::table('lottery')->where('id',$lottery_id)->first();
if(!$lottery) exit('彩种未定义');
$data = [
    'pid' => $argv[2],
    'lottery_id' => $argv[3],
    'lottery_name' => $argv[4],
    'type' => $argv[5],
    'lottery_interval' => $argv[6],
    'similar_id' => $argv[7]
];

$validate = new \lib\validate\Validate([
    'pid' => 'require|integer|in:1,5,10,24,39,51',
    'lottery_id' => 'require|integer',
    'lottery_name' => 'require',
    'type' => 'require',
    'lottery_interval' => 'require|integer',
    'similar_id' => 'require|integer',
]);
/*if (!$validate->check($data)) {
    print_r($validate->getError());
    exit;
}*/
unset($data['similar_id']);
$selfOpenLottery = Logic\Lottery\OpenPrize::$tables;
if(!isset($selfOpenLottery[$data['lottery_id']])){
    exit('彩种配置未定义！');
}
//print_r($app->getContainer()->request->getParams());
//print_r($data);

$conf = DB::table('openprize_config')->where('lottery_id',$lottery_id)->first();
if(!$conf){
    $res = DB::table('openprize_config')->insertGetId($data);
}
$conf = DB::table('openprize_config')->where('lottery_id',$lottery_id)->first();
if(!$conf) exit('彩种配置失败！');


try{
    DB::beginTransaction();
    (new \Logic\Lottery\OpenPrize($app->getContainer()))::createLottery((array)$lottery,(array)$conf,date('Y-m-d',strtotime('-1day')));
    (new \Logic\Lottery\OpenPrize($app->getContainer()))::createLottery((array)$lottery,(array)$conf,date('Y-m-d'));
    (new \Logic\Lottery\OpenPrize($app->getContainer()))::createLottery((array)$lottery,(array)$conf,date('Y-m-d',strtotime('1day')));
    copyHotLottery($lottery_id,$similar_id);
    copyHall($lottery_id,$similar_id);
    copyRebetConfig($lottery_id,$similar_id);
    copyLotteryRoom($lottery_id,$similar_id);
    copyLotteryPlayOdds($lottery_id,$similar_id);
    copyLotteryPlayLimtOdds($lottery_id,$similar_id);
    DB::commit();
    echo 'success';
}catch (\Exception $e){
    DB::rollback();
    print_r($e);
    exit;
}



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
function copyLotteryPlayLimtOdds($lottery_id,$similar_lottery_id){

    if(empty($lottery_id) || empty($similar_lottery_id)){
        return false;
    }

    $lottery_halls = Hall::where('lottery_id',$lottery_id)->get()->groupBy('hall_level')->toArray();

    $similar_lottery_halls = DB::table('hall')->where('lottery_id',$similar_lottery_id)->get()->toArray();
    foreach ($similar_lottery_halls as $similar_lottery_hall){
        $lottery_play_odds = LotteryPlayLimitOdds::selectRaw("`name`, `hall_id`, `play_id`, `lottery_id`, `lottery_pid`, `max_betting`")
            ->where('hall_id',$similar_lottery_hall->id)
            ->where('lottery_id',$similar_lottery_id)->get()->toArray();
        foreach ($lottery_play_odds as &$lottery_play_odd){
            $lottery_play_odd['hall_id'] = $lottery_halls[$similar_lottery_hall->hall_level][0]['id'];
            $lottery_play_odd['lottery_id'] = $lottery_id;
        }
        DB::table('lottery_play_limit_odds')->insert($lottery_play_odds);

    }

}
