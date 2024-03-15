<?php
use \Logic\Recharge\Recharge;
global $app;
$recharge = new Recharge($app->getContainer());
$userList = [
    '264'=>'288'
];
$ip = '0.0.0.0';
foreach($userList as $user_id => $money)
{
    try{
    $result = $recharge->tzHandDecrease(
        $user_id,
        bcmul($money, 100 ,0),
        '批量减少余额288',
        $ip,
        1,
        1,
        false,
        207
    );
    if(!$result){
        throw new \Exception("更新用户".$user_id."余额失败");
    }

    $data = [
        'ip' => $ip,
        'uid' =>  0,
        'uname' => '',
        'uid2' => 1,
        'uname2' => 'admin',
        'module' => '现金管理',
        'module_child' => '手工存提',
        'fun_name' => '减少余额', //这个暂时一样
        'type' => '手动减少余额',
        'status' => 1,
        'remark' => '金额(' . $money . ')'
    ];

    \DB::table('admin_logs')->insert($data);
    } catch (\Exception $e){
        print_r($e->getMessage());
        die;
    }
}
