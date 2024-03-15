<?php

use Logic\Set\SystemConfig;
use Model\GameMenu;
use Logic\User\Bkge;

global $app;
$ci = $app->getContainer();

$funds_list = \DB::table('funds')->select('id')->where('direct_balance', '>', 0)->get()->toArray();

$all_num = 0;
$all_money = 0;
$db = $app->getContainer()->db;
foreach($funds_list as $key => $val){
    //获取用户id
    $user = \DB::table('user')->where('wallet_id',$val->id)->first();
    if(empty($user)){
        continue;
    }
    $db->getConnection()->beginTransaction();
    try {
        $oldFunds = DB::table('funds')->where('id', $val->id)->lockForUpdate()->first();
        //修改用户直推流水状态
        \DB::table('direct_record')->where('user_id',$user->id)->update(['is_transfer'=>1]);

        (new \Logic\Wallet\Wallet($ci))->crease($val->id, $oldFunds->direct_balance);
        //修改钱包金额
        \DB::table('funds')->whereRaw('id=?',[$val->id])->update(['direct_balance'=>0]);
        //写金额流水
        \Model\FundsDealLog::create([
            'user_id' => $user->id,
            'user_type' => 1,
            'username' => $user->name,
            'deal_type' => \Model\FundsDealLog::TYPE_DIRECT_REWARD_COST,
            'deal_category' => \Model\FundsDealLog::CATEGORY_COST,
            'deal_money' => $oldFunds->direct_balance,
            'balance' => $oldFunds->balance + $oldFunds->direct_balance,
            'memo' => $ci->lang->text('Direct award cost'),
            'wallet_type' => 1
        ]);
    } catch (\Exception $e) {
        echo '用户:'.$user->name.'执行失败,原因:'.$e->getMessage();
        $db->getConnection()->rollback();
        continue;
    }
    $db->getConnection()->commit();

    //统计
    $all_num += 1;
    $all_money += $oldFunds->direct_balance;
}

echo '执行完毕，总人数:'.$all_num.',总金额:'.$all_money;
