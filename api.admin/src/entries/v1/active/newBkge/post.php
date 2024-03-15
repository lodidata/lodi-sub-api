<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;
use Logic\Define\CacheKey;

return new class() extends BaseController
{
    const TITLE       = '新增返佣活动';
    const DESCRIPTION = '';

    const QUERY       = [
    ];

    const PARAMS      = [
        "name"                       => "活动名称",
        "status"                     => 'disabled 停用,enabled 启用',
        "condition_opt"              => '返佣条件（lottery-有效投注，deposit-充值，winloss-盈亏）',
        "deposit_withdraw_fee_ratio" => '充值提款手续费比例',
        "winloss_fee_ratio"          => '网站输赢费用比例',
        "valid_user_num"             => '最低有效用户数',
        "valid_user_deposit"         => '有效用户充值',
        "valid_user_bet"             => '有效用户下注',
        "rule"                       => [
                                            [
                                                "min_winloss" => '最小输赢',
                                                "max_winloss" => '最大输赢',
                                                "bkge_scale"  => '退佣比例',
                                            ]
                                        ],
        "desc"                        => '说明',
    ];

    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $count = \Model\Admin\ActiveBkge::count();
        if($count){
            return $this->lang->set(886,['只能添加一个代理退佣活动']);
        }
        $active_bkge     = $this->request->getParams();
        $rule            = $active_bkge['rule'];
        $bkge_ratio_rule = [];

        foreach ($rule as $key => $v){
            $bkge_ratio_rule[$key]['min_winloss'] = $v['min_winloss'];
            $bkge_ratio_rule[$key]['max_winloss'] = $v['max_winloss'];
            $bkge_ratio_rule[$key]['bkge_scale']  = $v['bkge_scale'];
        }

        $new_bkge_set = [
            'deposit_withdraw_fee_ratio' => $active_bkge['deposit_withdraw_fee_ratio'],
            'winloss_fee_ratio'          => $active_bkge['winloss_fee_ratio'],
            'valid_user_num'             => $active_bkge['valid_user_num'],
            'valid_user_deposit'         => $active_bkge['valid_user_deposit'],
            'valid_user_bet'             => $active_bkge['valid_user_bet'],
            'bkge_ratio_rule'            => $bkge_ratio_rule,
        ];
        if(!empty($active_bkge['bkge_date'])){
            $this->redis->set(CacheKey::$perfix['bkge_date'],$active_bkge['bkge_date']);
        }

        $data = [
            'name'          => $active_bkge['name'],
            'status'        => $active_bkge['status'],
            'condition_opt' => $active_bkge['condition_opt'],
            'desc'          => $active_bkge['desc'],
            'bkge_date'     => $active_bkge['bkge_date'],
            'new_bkge_set'  => json_encode($new_bkge_set),
        ];
        $id = \Model\Admin\ActiveBkge::insertGetId($data);

        $code = $id ? 0 : 4001;
        return $this->lang->set($code);
    }
};
