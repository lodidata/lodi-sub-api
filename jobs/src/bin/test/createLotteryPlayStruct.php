<?php
/**
 * 生成彩票结构
 */
use \LotteryPlay\Struct;

$cid = isset($argv[2]) ? $argv[2] : 10;
$struct = new Struct;
$data = $struct ->sqlData($cid);
$contents = $struct->dataToInsert('lottery_play_struct', $data);
file_put_contents(ROOT_PATH.'/data/sql/lottery_play_struct_'.date('YmdHis').'.sql', $contents);

$data2 = $struct->odds($cid);
$contents = $struct->dataToInsert('lottery_play_base_odds', $data2);
file_put_contents(ROOT_PATH.'/data/sql/lottery_play_base_odds_'.date('YmdHis').'.sql', $contents);

echo 'success';