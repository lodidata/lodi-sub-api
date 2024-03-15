<?php
//初始化数据，从最大数值降序
$transfer = \DB::table('transfer_config')
                ->orderBy('sort', 'DESC')
                ->orderBy('id', 'DESC')
                ->get(['id'])
                ->toArray();
$transferTotal = \DB::table('transfer_config')->count();
foreach($transfer as $value) {
    if($transferTotal == 0) {
        break;
    }
    \DB::table('transfer_config')->where('id',$value->id)->update(['sort'=>$transferTotal]);
    $transferTotal = $transferTotal-1;
}

$pay = \DB::table('pay_config')
          ->orderBy('sort', 'DESC')
          ->orderBy('id', 'DESC')
          ->get(['id'])
          ->toArray();
$payTotal = \DB::table('pay_config')->count();
foreach($pay as $value) {
    if($payTotal == 0) {
        break;
    }
    \DB::table('pay_config')->where('id',$value->id)->update(['sort'=>$payTotal]);
    $payTotal = $payTotal-1;
}

$game3th = \DB::table('game_3th')
                ->orderBy('sort', 'DESC')
                ->orderBy('id', 'DESC')
                ->get(['id'])
                ->toArray();
$game3thTotal = \DB::table('game_3th')->count();
foreach($game3th as $value) {
    if($game3thTotal == 0) {
        break;
    }
    \DB::table('game_3th')->where('id',$value->id)->update(['sort'=>$game3thTotal]);
    $game3thTotal = $game3thTotal-1;
}

$gameMenu = \DB::table('game_menu')
                ->orderBy('sort', 'DESC')
                ->orderBy('id', 'DESC')
                ->get(['id'])
                ->toArray();
$gameMenuTotal = \DB::table('game_menu')->count();
foreach($gameMenu as $value) {
    if($gameMenuTotal == 0) {
        break;
    }
    \DB::table('game_menu')->where('id',$value->id)->update(['sort'=>$gameMenuTotal,'hot_sort'=>$gameMenuTotal]);
    $gameMenuTotal = $gameMenuTotal-1;
}