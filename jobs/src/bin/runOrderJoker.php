<?php
$ci = $app->getContainer();
$stime = $argv[2];
$etime = $argv[3];
$type = $argv[4];
$zero = $argv[5] ?? 0;
$data = \DB::table('game_order_joker');

$data = $data->where('gameDate','>=',$stime)
        ->where('gameDate','<=',$etime)
        ->get()->toArray();
$platformTypes = [
    'Slot' => ['name'=> 'JOKER电子','game'=> 'GAME','type'=>'JOKER'],
    'ECasino' => ['name'=> 'JOKER真人','game'=> 'LIVE','type'=>'JOKER'],
    'Fishing' => ['name'=> 'JOKER捕鱼','game'=> 'BY','type'=>'JOKER'],
];

$th3Data = \DB::table('game_3th')
    ->whereIn('game_3th.game_id', [59,60,61])
    ->select(['kind_id','type'])->get()->toArray();
$menuList = [];
foreach($th3Data as $val){
    $val = (array)$val;
    $menuList[$val['kind_id']] = $val['type'];
}

foreach ($data as $value){
    $value = (array)$value;
    $order = [
        'user_id' => $value['user_id'] ?? 0,
        'game' => $value['Type'],
        'order_number' => $value['OCode'],
        'game_type' => $platformTypes[$menuList[$value['gameCode']]]['type'],
        'type_name' => $platformTypes[$menuList[$value['gameCode']]]['name'],
        'game_id' => 0,
        'server_id' => 0,
        'account' => $value['Username'],
        'bet' => $value['betAmount'],
        'profit' => $value['income'],
        'date' => $value['gameDate'],
    ];

    $common = new \Logic\GameApi\Common($ci);
    $common->transferOrder($order);

}
echo count($data);
die();

