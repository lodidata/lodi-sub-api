<?php
echo \DB::table('orders')->where('date','<=','2019-04-08')
    ->whereIn('game_type',['AGIN','AGINTY','AGINDZ','AGINJJ'])->delete();
echo PHP_EOL;
$page = 1;
$size = 2000;
$data = \DB::table('game_order_agin')->forPage($page,$size)->get()->toArray();
$platformTypes = [
    'AGIN' => ['name'=> 'AG视讯','game'=> 'LIVE','type'=>'AGIN'],
    'SLOT' => ['name'=> 'AG电子','game'=> 'GAME','type'=>'AGINDZ'],
    'SPORT' => ['name'=> 'AG体育','game'=> 'SPORT','type'=>'AGINTY'],
    'YPMONEY' => ['name'=> 'AG街机','game'=> 'GAME','type'=>'AGINJJ'],
    'HUNTER' => ['name'=> 'AG捕鱼','game'=> 'BY','type'=>'AGINBY'],
];
while ($data) {
    foreach ($data as $value) {
        $value = (array)$value;
        if($value['flag'] != 1 ) continue;
        $tmp = [];
        $tmp['user_id'] = $value['user_id'];
        $tmp['order_number'] = $value['billno'];
        $tmp['game'] = $platformTypes[$value['platformtype']]['game'];
        $tmp['game_type'] = $platformTypes[$value['platformtype']]['type'];
        $tmp['type_name'] = $platformTypes[$value['platformtype']]['name'];
        $tmp['play_id'] = 0;
        $tmp['bet'] = $value['valid_account'];
        $tmp['profit'] = $value['cus_account'];
        $tmp['send_money'] = $value['valid_account'] + $value['cus_account'];
        $tmp['dml'] = $value['valid_account'];
        $tmp['date'] = $value['beji_prize_time'];
        \DB::table('orders')->updateOrInsert(['order_number'=>(string)$value['billno'], 'game_type'=>$tmp['game_type']],$tmp);
    }
    $page++;
    $data = \DB::table('game_order_agin')->forPage($page,$size)->get()->toArray();
}
die();

