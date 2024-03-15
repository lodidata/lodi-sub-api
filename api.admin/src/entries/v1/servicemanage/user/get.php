<?php

use Logic\Admin\BaseController;

/*
 * 用户资料查询
 *
 * */
return new class extends BaseController
{

    const TITLE = '客服系统  用户资料查询';
    const DESCRIPTION = '用户资料查询';
    
    const QUERY = [
    ];
    
    const PARAMS = [];
    const SCHEMAS = [];

    protected $beforeActionList = [
//        'verifyToken', 'authorize',
    ];


    public function run($id)
    {
        $id_orgin = $id;
        $this->checkID($id);
        $manage = new \Logic\Service\Manage($this->ci);
        $id = base_convert($id, 10, 36);
//
        $hashids = $manage->getHashids();
        $id = $hashids->decode($id);
        if (empty($id)) {
            $re['base_info'] = [
                'user_name' => '异常用户' . $id_orgin
            ];
            return $this->lang->set(0, [], $re);
        }
        $id = $id[0];
        //判断用户类型

        $user_type = $manage->getUserType($id);
        if ($user_type == 2) {//试玩用户类型
            $id = $id - 10000000000;
            $user = (array)DB::table('trial_user')->where('id', $id)->selectRaw("`name`")
                ->first();
            if (!$user)
                return $this->lang->set(10014);
            $re['base_info'] = [
                'user_name' => '试玩'.$user['name'],
                'user_type' => $user_type,
            ];
        } else if ($user_type == 3) {//游客用户类型
            $re['base_info'] = [
                'user_name' => '游客' . $id,
                'user_type' => $user_type,
            ];
        } else {//正式用户类型
            $re = $this->redis->get('servicemanage_user_' . $id);   //缓存60秒
            if ($re)
                return json_decode($re, true);
            $user = (array)DB::table('user')->where('id', $id)->selectRaw("
            `id`,
            `wallet_id`,
            `name`,
            `online`,
            `tags`,
            `auth_status`,
            `limit_status`,
            `state`,
            `ranting`,
            inet6_ntoa(`ip`) as ip,
            `created`,
            inet6_ntoa(`login_ip`) as login_ip,
            `last_login`")
                ->first();
            if (!$user)
                return $this->lang->set(10014);
            $profile = (array)DB::table('profile')->where('user_id', $id)
                ->first(
                    [
                        'name',
                        'idcard',
                    ]
                );
            $user_auth = '';
            $auth_status = ['refuse_withdraw' => '禁止提款', 'refuse_sale' => '禁止优惠'];
            foreach ($auth_status as $key => $val) {
                if (strpos($user['auth_status'], $key) !== false) {
                    $user_auth .= $val . ',';
                }
            }
            $user_auth = rtrim($user_auth, ',');
            //基本资料
            $re['base_info'] = [
                'user_name' => $user['name'],
                'user_id' => $user['id'],
                'user_tags' => \DB::table('label')->where('id', $user['tags'])->value('title'),
                'user_level' => \DB::table('user_level')->where('level', $user['ranting'])->value('name'),
                'true_name' => $profile['name'],
                'id_card' => \Utils\Utils::RSADecrypt($profile['idcard']),
                'user_auth' => $user_auth,
                'online' => $user['online'],
                'user_type' => $user_type,
            ];
            //更多信息
            $re['base_other'] = [
                'register_time' => $user['created'],
                'register_ip' => $user['ip'],
                'login_time' => date('Y-m-d H:i:s', $user['last_login']),
                'login_ip' => $user['login_ip'],
            ];
            //账户余额
            $wallet = new \Logic\Wallet\Wallet($this->ci);
            $re['wallet_info'] = $wallet->getWallet($id);
            //打码量
            $user_data = \Model\UserData::where('user_id',$id)->first()->toArray();
            $re['dml_info'] = [
                'free_money' => $user_data['free_money'],
                'actual_bet' => $user_data['total_bet'],
                'require_bet' => $user_data['total_require_bet'],
            ];

            //出入款
            $re['deposit_withdraw'] = [
                'deposit_count' => $user_data['deposit_num'], //存款次数
                'deposit_sum' => $user_data['deposit_amount'],//存款总额
                'withdraw_count' => $user_data['withdraw_num'],//出款次数
                'withdraw_sum' => $user_data['withdraw_amount'],//出款总额
            ];
            //盈亏

            $re['earn_loss'] = [
                'earn_loss' => $wallet->getUserTodayProfit($id),
            ];
            //注单
            $re['order'] = [
                'order_count' => $user_data['order_num'],
                'order_sum' => $user_data['order_amount'],
                'prize_sum' => $user_data['send_amount'],
            ];
            //回水活动
            $re['active_return'] = [
                'return_sum' => $user_data['rebet_amount'],
                'active_sum' =>  $user_data['active_amount'],
            ];

            //返佣信息rake-back
            $game = \Model\Admin\GameMenu::where('pid',0)->where('switch','enabled')->get(['type','name'])->toArray();
            $bkge_info = \Model\UserAgent::where('user_id',$id)->first()->toArray();
            $agent  = new \Logic\User\Agent($this->ci);
            $user_agent = $agent->synchroUserBkge($id,$bkge_info)['bkge'];

            //返佣
            $user_rake = \DB::table('bkge')->where('user_id', $id)->groupBy('game')
                ->get([
                    'game',
                    \DB::raw('SUM(bkge) AS back_money')
                ])->toArray();
            $user_rake = array_column($user_rake,null,'game');
            foreach ($game as $val) {
                $re['rake_config'][] = [
                    'name' => $val['name'],
                    'value' => $user_agent[$val['type']] ?? 0,
                ];
                $re['rake_money'][] = [
                    'name' => $val['name'],
                    'value' => $user_rake[$val['type']] ?? 0,
                ];
            }

            $this->redis->setex('servicemanage_user_' . $id, 60, json_encode($re));
        }
        return $this->lang->set(0, [], $re);
    }

};