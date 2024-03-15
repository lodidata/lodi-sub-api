<?php
$ci = $app->getContainer();
$stime = $argv[2];
$now = date('Y-m-d H:i:s',time());
if(!isset($stime) || empty($stime)){
    $stime = $now;
}
$count_date = date('Y-m-d', strtotime($stime));

$data = \DB::table('orders')
    ->where('date','=',$count_date)
    ->groupBy('game_type')
    ->get([
        'user_id',
        'game_type',
        'type_name as game_name',
        \DB::raw('count(id) as game_order_cnt'),
        \DB::raw('sum(bet) as game_bet_amount'),
        \DB::raw('sum(dml) as game_code_amount'),
        \DB::raw('sum(send_money) as game_prize_amount'),
       // \DB::raw('0 as game_deposit_amount'),
       // \DB::raw('0 as game_withdrawal_amount'),
        \DB::raw('count(DISTINCT user_id) as user_count'),
    ])->toArray();

if($data){
    foreach ($data as $value){
        $value = (array)$value;
        $order = [
            'count_date' => $count_date,
            'game_type' => $value['game_type'],
            'game_name' => $value['game_name'],
            'game_order_cnt' => $value['game_order_cnt'],
            'game_bet_amount' => bcdiv($value['game_bet_amount'], 100, 2),
            'game_prize_amount' => bcdiv($value['game_prize_amount'], 100, 2),
            'game_code_amount' => bcdiv($value['game_code_amount'], 100, 2),
            'game_deposit_amount' => 0,
            'game_withdrawal_amount' => 0,
            'create_time' => $now,
            'update_time' => $now,
            'clear_status' => 0
        ];

        $userData = [
            'count_date' => $count_date,
            'game_type' => $value['game_type'],
            'user_id' => $value['user_count'],
            'create_time' => $now,
            'update_time' => $now
        ];
        try{
            \DB::table('rpt_order_amount')->updateOrInsert(['count_date' => $count_date, 'game_type' => $order['game_type']], $order);
            \DB::table('rpt_order_user')->updateOrInsert(['count_date' => $count_date, 'game_type' => $userData['game_type']], $userData);
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        };
    }
}
echo count($data);
die();

