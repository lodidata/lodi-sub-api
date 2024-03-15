<?php

namespace Logic\Recharge;
/*
 * 注意，数据库存储的金额都是以分为单位，注意啥时候是分啥时候是元
 */

use Logic\Define\Cache3thGameKey;
use Logic\Logic;
use Logic\Set\SystemConfig;
use Model\Bank;
use Model\FundsDeposit;
use Model\FundsVender;
use Model\BankAccount;
use Illuminate\Database\Capsule\Manager as Capsule;

class Pay extends Logic {

    public $vender;

    public $return = [
        'code' => 0,     //统一 0 为OK
        'msg'  => '',    //统一 SUCCESS为成功
        'way'  => '',  //返回类型 （取值 code:二维码，url:跳转链接，json：）
        'str'  => '',  //
    ];


    /**
     * 获取支付配置
     * @return array
     */
    public function payConfig()
    {
        $pay_site = $this->redis->get(Cache3thGameKey::$perfix['payConfig']);
        $pay_site = json_decode($pay_site,true);
        if(empty($pay_site) || is_null($pay_site)){
            $pay_site = [];
            $list = \DB::table('pay_config')->orderBy('sort', 'asc')->get();
            if($list){
                foreach($list as $value){
                    $value = (array)$value;
                    $value['pay_id'] = $value['id'];
                    $pay_site[$value['type']] = $value;
                }

                unset($list, $value);

                $pay_site && $this->redis->setex(Cache3thGameKey::$perfix['payConfig'], 3600*24, json_encode($pay_site));
            }
       }

        return $pay_site;
    }

    /**
     * 获取所有支付配置
     * @return array
     */
    public function allPayConfig($params=[])
    {
        $pay_site = [];
        $query = \DB::table('pay_config');
        isset($params['status'])  && $query->where('status', $params['status']);
        isset($params['id']) && $query->where('id', $params['id']);
        isset($params['type']) && $query->where('type', $params['type']);
        $list = $query->orderBy('sort', 'asc')->get();
        if($list){
            foreach($list as $value){
                $value = (array)$value;
                $value['pay_id'] = $value['id'];
                $pay_site[$value['type']] = $value;
            }
            unset($list, $value);
        }
        return $pay_site;
    }



    //整理获取依据支付平台获取线上充值渠道
    public function tinyOnlineChannel($userLevel) {
        $channels = $this->payConfig();
        $res = [];

        if ($channels && is_array($channels)) {
            //如果是PC端需要筛选支付渠道，H5的渠道不显示
            if (isset($_SERVER['HTTP_PL']) && $_SERVER['HTTP_PL'] == 'pc') {
                $channels = array_filter($channels, function ($channel) {
                    return $channel['show_type'] == 'code';
                });
            }
            $levelId = \DB::table('user_level')->where('level',$userLevel)->value('id');
            $thirds = \DB::table('level_online')
                ->where('level_id', '=', $levelId)
                ->pluck('pay_plat')
                ->toArray();
            foreach ($channels as $val) {
                if (in_array($val['type'], $thirds)) {
                    $k =$val['id'];
                    if (isset($res[$k])) {
                        if ($res[$k]['min_money'] > $val['min_money']) {
                            $res[$k]['min_money'] = $val['min_money'];
                        }
                        if ($res[$k]['max_money'] < $val['max_money'] && $res[$k]['max_money'] != 0) {
                            $res[$k]['max_money'] = $val['max_money'];
                        }
                    } else {
                        $res[$k] = [
                            'name' => $val['name'],
                            'type' => $val['type'],
                            'min_money' => $val['min_money'],
                            'max_money' => $val['max_money']
                        ];
                        $res[$k]['id'] = $k;
                    }
                }
            }
        }

        return $res;
    }

    //获取依据支付平台获取线上充值渠道
    public function getOnlineChannel(int $userLevel = 1) {
        $payChannels = $this->tinyOnlineChannel($userLevel);
        $round = [
            'max_money' => 0,
            'min_money' => 0,
        ];
        if ($payChannels) {
            // 按分存储的
            $base = SystemConfig::getModuleSystemConfig('recharge')['recharge_money'];

            $base_min = $base['recharge_min'];
            $base_max = $base['recharge_max'];

            $channel = $this->getChannel('online');

            foreach ($payChannels as &$payChannel) {
                $payChannel['imgs'] = $payChannel['img'] ?? '';
                $payChannel['d_title'] = $channel[$payChannel['id']]['title'] ?? '';

                if ($base_min > $payChannel['min_money']) {
                    $payChannel['min_money'] = $base_min;
                }

                if ($base_max < $payChannel['max_money'] && $base_max != 0) {
                    $payChannel['max_money'] = $base_max;
                }
            }

            $round = [
                'max_money' => max(array_map(function ($val) {
                    return $val['max_money'];
                }, $payChannels)),

                'min_money' => min(array_map(function ($val) {
                    return $val['min_money'];
                }, $payChannels)),
            ];
        }

        return [
            'money' => $round,
            'type'  => array_values(array_sort($payChannels, 'id')),
        ];
    }

    //线上获取具体通道
    public function getOnlinePassageway($type=null) {
        $payConfigList = $this->payConfig();
        if($type){
            return $payConfigList[strtolower($type)];
        }
        return $payConfigList;
    }

    //获取线上银联付款时某些第三方支付的银行
    public function decodeOnlineBank($json) {
        $pay_bank = json_decode($json, true);
        $banks = '"' . implode('","', array_keys((array)$pay_bank)) . '"';
        $data = \Model\Bank::select(['code', 'name', 'logo'])
                           ->whereRaw("code IN ({$banks})")
                           ->get()
                           ->toArray();

        foreach ($data as $k => $val) {
            $result[$k] = $val;
            $result[$k]['name'] = $this->lang->text($val['code']);
            $result[$k]['pay_code'] = $pay_bank[$val['code']];
        }

        return $result ?? [];
    }

    //获取充值类型
    public function getType(int $userLevel = 1) {
        $data = $this->offline(null, $userLevel) ?? [];
        $type = [];
        $channel = $this->getChannel('offline');

        foreach ($data as $key => $val) {
            $type[$val['type']]['id'] = $val['type'];
            $type[$val['type']]['name'] = $val['type'] == 1 ? $this->lang->text("Bank card deposit") : $val['name'];
            $type[$val['type']]['imgs'] = $val['img']??'';
            $type[$val['type']]['d_title'] = $channel[$val['type']]['title'] ?? '';
        }

        return array_values(array_sort($type, 'id'));
    }

    public function getChannel(string $t) {
        $result = [];
        $channel = Capsule::table('funds_channel')
                          ->select(['*'])
                          ->where('status', '=', 1)
                          ->where('show', '=', $t)
                          ->get()
                          ->toArray();

        foreach ($channel ?? [] as $v) {
            $v = (array)$v;
            $result[$v['type_id']] = $v;
        }

        return $result;
    }

    /**
     * 获取充值账号
     * @return mixed
     */
    public function getBankAccount(){
        $ranting = \Model\User::where('id', $this->auth->getUserId())->value('ranting');
        $bank_account = BankAccount::select([
            'bank_account.id as id',
            'bank_account.name as name',
            'bank_account.card as card',
            'bank_account.qrcode as qrcode',
           // 'bank.name as bank_name',
            'bank.code as code',
            'bank.h5_logo as bank_img'
        ])
            ->leftJoin('level_bank_account as lba', 'bank_account.id', '=', 'lba.bank_account_id')
            ->leftJoin('bank', 'bank_account.bank_id', '=', 'bank.id')
            ->where('lba.level_id','=',$ranting)
            ->whereRaw('FIND_IN_SET("enabled",bank_account.`state`)')->orderBy('bank_account.sort', 'asc')->get()->toArray();
        foreach ($bank_account as &$v){
            $v['bank_name'] = $this->lang->text($v['code']);
        }
        unset($v);
        return $bank_account;
    }

    //依据线下充值数据获取相应数据
    public function offline(int $type = null, int $userLevel = 1) {
        $levelId = \DB::table('user_level')->where('level',$userLevel)->value('id');
        $payids = \DB::table('level_offline')
                     ->where('level_id', '=', $levelId)
                     ->pluck('pay_id')
                     ->toArray();

        $offline = BankAccount::select([
            'bank_account.*',
            //'bank.name as bank_name',
            'bank.code as code',
            'bank.h5_logo as bank_img'
        ])
                              ->leftJoin('bank', 'bank_account.bank_id', '=', 'bank.id')
                              ->whereRaw('FIND_IN_SET("enabled",bank_account.`state`)');

        $offline->whereIn('bank_account.id', $payids);
        if ($type) {
            $offline->where('bank_account.type', '=', $type);
        }

        $list = $offline->orderBy('bank_account.sort')
                       ->get()
                       ->toArray();
        foreach ($list as &$v){
            $v['bank_name'] = $this->lang->text($v['code']);
        }
        unset($v);
        return $list;
    }

    //存款额度
    public function stateMoney(int $account_id) {
        $day = date('Y-m-d');

        $day_money = FundsDeposit::where('receive_bank_account_id', '=', $account_id)
                                 ->where('status', '=', 'paid')
                                 ->where('recharge_time', '>=', $day)
                                 ->where('recharge_time', '<=', $day . ' 59:59:59')
                                 ->value('money');

        $sum_money = FundsDeposit::where('receive_bank_account_id', '=', $account_id)
                                 ->where('status', '=', 'paid')
                                 ->value('money');

        $re['day_money'] = $day_money ?? 0;
        $re['sum_money'] = $sum_money ?? 0;

        return $re;
    }

    public function limitOffline($type, $userLevel) {
        $levelId = \DB::table('user_level')->where('level',$userLevel)->value('id');
        $payids = \DB::table('level_offline')
                     ->where('level_id', '=', $levelId)
                     ->pluck('pay_id')
                     ->toArray();

        $offline = Capsule::table('bank_account')
                          ->whereIn('id', $payids)
                          ->whereRaw("FIND_IN_SET('enabled',state)")
                          ->select([
                              $this->db->getConnection()
                                       ->raw('MIN(limit_once_min) as min_money'),
                              $this->db->getConnection()
                                       ->raw('MAX(limit_once_max) as max_money'),
                              $this->db->getConnection()
                                       ->raw('MIN(limit_once_max) as max_temp'),
                          ]);

        if ($type) {
            $offline->where('type', '=', $type);
        }

        return (array)$offline->first();
    }

    //线上获取第三方及平台设制金额的综合值，第三方平台的并集最大与平台的差集最小
    public function aroundMoney($type = null, $userLevel = 1) {
        $rand = $this->limitOffline($type, $userLevel);

        // 按分存储的
        $base = SystemConfig::getModuleSystemConfig('recharge')['recharge_money'];

        $result['min_money'] = $base['recharge_min'] ?? 0;
        $result['max_money'] = $base['recharge_max'] ?? 0;

        if ($rand) {
            $min = $rand['min_money'] ?? 0;
            $max = $rand['max_money'] ?? 0;
            $result['min_money'] = $min > $result['min_money'] ? $min : $result['min_money'];

            if ($rand['max_temp'] > 0) {
                $result['max_money'] = $max > $result['max_money'] || $max == 0 ? $result['max_money'] : $max;
            }
        }

        return $result;
    }

    public function getPayChannel($userLevel) {
        $type = $this->getType($userLevel) ?? [];
        $re = [];
        foreach ($type as &$val) {
            //  获取每个渠道金额限制
            $round_money = $this->aroundMoney($val['id'], $userLevel);

            if (is_array($round_money)) {
                $val['min_money'] = $round_money['min_money'];
                $val['max_money'] = $round_money['max_money'];

                if ($val['min_money'] <= $val['max_money']) {
                    $re[] = $val;
                }
            }
        }

        $round = $this->aroundMoney(null, $userLevel);
        $round['min_money'] = isset($round['min_money']) ? $round['min_money'] : 0;
        $round['max_money'] = isset($round['max_money']) ? $round['max_money'] : 0;

        return ['money' => $round, 'type' => $re];
    }

    public function getDepositByOrderId($orderNo) {
        return FundsDeposit::where('trade_no', '=', $orderNo)
                           ->first();
    }

    public function getFundsById($id) {
        return FundsVender::find($id);
    }

    /**
     * 支付交易信息
     *
     * @param string $platform
     * @param string $pay_scene
     * @param string $trade_no
     * @param int $user_id
     * @param string $trans_id
     * @param int $money
     * @param string $pay_time
     */
    public function noticeInfo($platform, $pay_scene, $trade_no, $user_id, $trans_id, $money, $pay_time) {
        global $app;

        $data['platform'] = $platform;
        $data['pay_scene'] = $pay_scene;
        $data['user_id'] = $user_id;
        $data['trade_no'] = $trade_no;
        $data['trans_id'] = $trans_id;
        $data['money'] = $money;
        $data['pay_time'] = $pay_time ?? date('Y-m-d H:i:s');
        $data['created'] = date('Y-m-d H:i:s');

        $app->getContainer()->db->getConnection()
                                ->table('funds_pay_callback')
                                ->insert($data);
    }


    // 获取通道所属渠道
    public function getPaymentByPayID($payids = [])
    {
        $data = \DB::connection('slave')->table('payment_channel as py')
            ->leftJoin('pay_channel as pc', 'pc.id', '=', 'py.pay_channel_id')
            ->where(function($query) use($payids){
                if (!empty($payids)) {
                    $query->whereIn('py.id', $payids);
                }
            })->get(['py.id as payment_id', 'pc.id', 'pc.name', 'pc.type', 'pc.status'])->toArray();

        return $data ? array_column($data, NULL, 'payment_id') : [];    
    }


}