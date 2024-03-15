<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;

/**
 * 检测用户（检测用户是否存在）
 */
return new class() extends BaseController
{
    //前置方法
   protected $beforeActionList = [
       'verifyToken',
       'authorize'
   ];

    public function run()
    {

        $userName = $this->request->getParam('username', '');
        if (!$userName) {
            return $this->lang->set(886, ['用户名不能为空']);
        }
        $user = DB::table('user')
            ->where('name', '=', $userName)
            ->get()
            ->first();
        if ($user) {
            $wid = $user->wallet_id;
            $balance = \Model\Funds::where('id', '=', $wid)->value('balance');

            $result['uid'] = $user->id;

            $result['wallet'] = [
                [
                    'id' => $wid,
                    'sid' => $wid,
                    'name' => '主钱包',
                    'balance' => $balance,
                    'game_type' => '主钱包'
                ]
            ];
            $wallet = new \Logic\Wallet\Wallet($this->ci);

            $funds = \Model\Funds::where('id',$wid)->first(['balance','freeze_money']);

            $secondaries = $wallet->getInfo($user->id);
            foreach ($secondaries['children'] as $secondary) {
                if ($secondary['game_type'] == '主钱包') {
                    continue;
                }
                array_push($result['wallet'], [
                    'id' => $secondary['id'],
                    'sid' => $secondary['uuid'],
                    'name' => $secondary['name'],
                    'balance' => $funds['balance'],
                    'game_type' => $secondary['game_type']
                ]);
            }
            return $result;
        } else {
            return $this->lang->set(886, ['用户不存在']);
        }
    }
};
