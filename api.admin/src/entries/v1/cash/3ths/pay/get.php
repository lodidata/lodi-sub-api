<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE       = '第三方支付列表';
    const DESCRIPTION = '获取第三方支付商户与类型';
    
    const QUERY       = [
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'status' => 'enum[default,enabled,disabled]'
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $pay = new Logic\Recharge\Pay($this->ci);
        $channel =  $pay->allPayConfig();
        $pay_channel=DB::connection('slave')->table('pay_channel')->orderBy('sort')->get(['id as code','name'])->toArray();
        $pay_scene = [];
        foreach ($channel as $key=>$val){
            $n['name'] = $val['type'];
            $n['pay_scene'] = $val['type'];
            $n['third_id']=$val['id'];
            $pay_scene[] = $n;
        }
        return ['pay_channel'=>$pay_channel,'pay_scene'=>$pay_scene];
    }
};
