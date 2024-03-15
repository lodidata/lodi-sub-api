<?php
$ci = $app->getContainer();
$stime = $argv[2];
$etime = $argv[3];
$type = $argv[4];
$zero = $argv[5] ?? 0;
$data = \DB::table('game_order_agin')
    ->where('platformtype','=',$type);
    if($zero){
        $data->where('valid_account','<=',0);
    }

$data = $data->where('beji_prize_time','>=',$stime)
        ->where('beji_prize_time','<=',$etime)
        ->get()->toArray();
$platformTypes = [
    'AGIN' => ['name'=> 'AG视讯','game'=> 'LIVE','type'=>'AGIN'],
    'SLOT' => ['name'=> 'AG电子','game'=> 'GAME','type'=>'AGINDZ'],
    'SPORT' => ['name'=> 'AG体育','game'=> 'SPORT','type'=>'AGINTY'],
    'YPMONEY' => ['name'=> 'AG街机','game'=> 'GAME','type'=>'AGINJJ'],
    'HUNTER' => ['name'=> 'AG捕鱼','game'=> 'BY','type'=>'AGINBY'],
];
foreach ($data as $value){
    $value = (array)$value;
    if($value['flag'] != 1 ) continue;
    $order = [
        'user_id' => $value['user_id'] ?? 0,
        'game' => $platformTypes[$value['platformtype']]['game'],
        'order_number' => $value['billno'],
        'game_type' => $platformTypes[$value['platformtype']]['type'],
        'type_name' => $platformTypes[$value['platformtype']]['name'],
        'game_id' => 0,
        'server_id' => 0,
        'account' => $value['username'],
        'bet' => $value['valid_account'],
        'profit' => $value['cus_account'],
        'date' => $value['beji_prize_time'],
    ];
    $common = new \Logic\GameApi\Common($ci);
    $common->transferOrder($order);

}
echo count($data);
die();

