<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/23
 * Time: 17:16
 */

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取第三方钱包金额列表";
    const TAGS = '游戏';
    const QUERY = [
        "refresh" => "enum[1,0](,0) #是否手动刷新 1是 0否",
    ];
    const SCHEMAS = [
        'sum_balance' => "float(required) #总金额 1000",
        'available_balance' => "float(required) #可用金额 800",
        'freeze_money' => "float(required) #冻结金额 200",
        'take_balance' => "float(required) #可提余额 100",
        'today_profit' => "float(required) #今天收益 10.01",
        'child' => [
            [
                'uuid'      => 'string() #uuid 游戏唯一标识',
                "game_type" => "string() #游戏类型 KAIYUAN",
                "name"      => "string() #游戏名称",
                'freeMoney' => "float(required) #可下分金额 10.01",
                'balance'   => "float(required) #总金额 100.10"
            ]
        ]
    ];
    

    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();
//        $uid = 4;
        $refresh = $this->request->getParam('refresh');
        $wid     = \Model\User::where('id',$uid)->value('wallet_id');
        $funds   = \Model\Funds::where('id',$wid)->first();
        $list    = \Model\FundsChild::where('pid',$wid)->where('status','enabled')->get()->toArray();

        //拿到所有需要创建钱包的第三方
        $partners = \Model\GameMenu::where('pid','!=',0)
            ->where('type','!=','ZYCPSTA')
            ->where('type','!=','ZYCPCHAT')
            ->where('switch','enabled')
            ->groupBy('alias')
            ->get(['alias','name'])->toArray();

        $tmp1  = is_array($partners) ? array_column($partners,'alias') : [];
        $tmp2  = is_array($list) ? array_column($list,'game_type') : [];
        $tmp   = array_diff($tmp1,$tmp2);

        if($tmp) {
            foreach ($partners as $v) {
                $v = (array)$v;
                if(in_array($v['alias'],$tmp)) {
                    //$fundsChildData = ['pid' => $wid, 'game_type' => $v['alias'], 'name' => $v['name']];
                    $fundsChildData = ['pid' => $wid, 'game_type' => $v['alias'], 'name' => $v['alias']];
                    \Model\FundsChild::create($fundsChildData);
                }
            }
            $list = \Model\FundsChild::where('pid',$wid)->where('status','enabled')->get()->toArray();
        }
        $child = [];
        $total_third_money = 0; //第三方总金额
        // 最后 一次 进入的第三方  需要刷新
        $beforeGame = json_decode($this->redis->get(\Logic\Define\Cache3thGameKey::$perfix['gameUserCacheLast'].$uid),true);

        foreach ($list as $val) {
            if($refresh && $val['balance'] > 0 || $val['game_type'] == $beforeGame['alias']) {
                // 用户手动触发刷新
                try {
                    $gameClass = \Logic\GameApi\GameApi::getApi($val['game_type'], $uid);
                    list($freeMoney, $totalMoney) = $gameClass->getThirdBalance();
                    \Model\FundsChild::where('pid',$wid)->where('game_type',$val['game_type'])->update(['balance' => $totalMoney]);
                }catch (\Exception $e) {
                    $this->logger->error('game.third.list error', ['game_type' => $val['game_type'], 'uid' => $uid, 'error' => $e->getMessage()]);
                    $totalMoney = 0;
                    $freeMoney = 0;
                }
            }else {
                $totalMoney = $val['balance'];
                $freeMoney = $totalMoney;   //一般情况下，一致
            }
            $tmp['uuid']        = $val['uuid'];
            $tmp['game_type']   = $val['game_type'];
            $tmp['name']        = $val['name'];
            $tmp['balance']     = (int)$totalMoney;  // 总金额
            $tmp['freeMoney']   = (int)$freeMoney; // 可下分金额
            $child[] = $tmp;
            // 总金额累计
            $total_third_money += $totalMoney;
        }
        $res = [
            'sum_balance'       => $funds['balance'] + $total_third_money,
            'available_balance' => $funds['balance'],
            'freeze_money'      => $funds['freeze_money'],
            'take_balance'      => \DB::table('user_data')->where('user_id',$uid)->value('free_money'),
        ];
        $res['take_balance'] = $res['take_balance'] > $res['sum_balance'] ? $res['sum_balance'] : $res['take_balance'];
        $res['today_profit'] = (new \Logic\Wallet\Wallet($this->ci))->getUserTodayProfit($uid);
        $res['child']        = $child;
        return $res;
    }
};
