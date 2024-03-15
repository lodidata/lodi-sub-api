<?php
//$hallInfo  一级下标为层级，二级下标为game_id 值为回水设置
use Logic\Lottery\Rebet;
use Logic\Wallet\Wallet;
use Logic\Lottery\RebetThird;
global $app;
$ci = $app->getContainer();

$game_type = $argv[2];
$date = $argv[3];
$userId = $argv[4]?? 0;

if(empty($game_type) ){
    echo '无游戏类型$game_type' ;die;
}
if(empty($date)){
    echo '无时间';die;
}
$game_type = strtoupper($game_type);

$count_date = date('Y-m-d', strtotime("$date+1 day"));

$obj_RebetThird = new RebetThird($ci);
$hallInfo = $obj_RebetThird->getRebetByUserLevel();//获取层级列表
if(!$hallInfo){
    return false;
}
$wallet            = new Wallet($ci);
$logic_rebet       = new Rebet($ci);
$website           = $ci->get('settings')['website'];

$order_query = \DB::table('order_game_user_day as o')->leftJoin('user as u','o.user_id','=','u.id')
    ->where('o.date', $date)
    ->where('o.game_type', $game_type)
    ->whereRaw(" !find_in_set('refuse_rebate',u.auth_status)");

if ($userId > 0) {
    $order_query->where('o.user_id', $userId);
}

//指定标签会员不返水
if(!empty($website['notInTags'])){
    $notInTags = implode(',', $website['notInTags']);
    $order_query->whereRaw(" !find_in_set(u.tags,'{$notInTags}')");
}

$order_list = $order_query->groupBy('o.user_id','o.game_type')
    ->select([\DB::raw('o.user_id,u.ranting,u.name,u.wallet_id, o.game_type,o.play_id game_id, sum(o.bet) bet, sum(o.profit) profit')])
    ->get()->toArray();
if($order_list){
    $order_num = count($order_list);
    //用for循环可以释放内存
    for($i=0; $i<$order_num; $i++){
        $v = (array)$order_list[$i];
        $v['type_name'] = $ci->lang->text($v['game_type']);
        if(!isset($hallInfo[$v['ranting']])) {
            $ci->logger->error('【游戏返水】 第三方类型:LV' . $v['ranting'] . "等级未设置反水user_id=" . $v['user_id'] . $date);
            continue;
        }
        //判断这个是否返过水
        $rebetInfo = (array)\DB::table('rebet')->where('user_id',$v['user_id'])->where('day', $count_date)->where('type', $game_type)->first();
        if($rebetInfo && isset($rebetInfo['bet_amount']) && $rebetInfo['bet_amount']>0){
            continue;
        }

        //这个game不返水
        if(empty($hallInfo[$v['ranting']][$v['game_id']])) continue;
        $rebet_config = $hallInfo[$v['ranting']][$v['game_id']];

        $temp = $logic_rebet->threbetElse($date, $wallet, [$v['game_id']=>$rebet_config], $v,$rebet_config['type'], 'rebet',true);
        if (!empty($temp)) {
            if($temp['money'] > 0) {
                $title = $website['name']?? '';//标题
                //发送回水信息
                $content  = ["Dear user, congratulations on your daily rebate amount of %s, The more games, the more rebates. If you have any questions, please consult our 24-hour online customer service", $temp['money'] / 100];
                $exchange = 'user_message_send';
                \Utils\MQServer::send($exchange, [
                    'user_id' => $v['user_id'],
                    'title'   => json_encode("Backwater news"),
                    'content' => json_encode($content),
                ]);
            }
            //插入日志数据
            $rebetLog = [
                'user_id'            => $v['user_id'],//用户id
                'rebet_user_ranting' => $v['ranting'],//用户等级
                'game_id'            => $v['game_id'],//第三方游戏id
                'desc'               => json_encode($temp),
            ];
            \DB::table('rebet_log')->insert($rebetLog);
        }
        unset($order_list[$i]);
    }
    unset($v);
}
return 'rebet success';