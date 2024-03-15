<?php
use Logic\Lottery\InsertNumber;

//\Logic\Lottery\OpenPrize::getSuitableOpenCodeViva('99', '201805250973', 10);

/*use Model\LotteryInsertNumber;

$res = LotteryInsertNumber::openCode(113, '202111120469');
var_dump($res);*/

\Logic\Lottery\OpenPrize::randomInsertNumber();
die;


$data = [
    'uid'               => 7,
    'user_account'      => 'web0006',
    'number'            => '01240',
    'lottery_id'        => 113,
    'lottery_number'    => '202111120469',
];
$data = [
    'uid' => 0,
    'user_account' => str_random(6),
    'number'        => mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9),
    'lottery_id'    => 113,
    'lottery_number' => 202111120469,
    'time'           => date('m/d/Y H:i:s', time())
];
InsertNumber::insertNumber($data);
/*$data = [
    'uid' => 0,
    'user_account' => str_random(6),
    'number'        => mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9),
    'lottery_id'    => 113,
    'lottery_number' => 202111120469,
    'time'           => date('m/d/Y H:i:s', time())
];
for($i=0; $i<1000; $i++){
InsertNumber::insertNumber($data);
}
die;*/
/*$res = InsertNumber::openCode(113, '202111120469');
var_dump($res);*/