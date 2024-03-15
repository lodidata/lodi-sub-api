<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '第三方支付详情';
    const DESCRIPTION = '获取第三方支付详情信息';

    const QUERY       = [
        //        'id'             => 'int(required) #支付接口ID',
        //        'channel_id'     => 'int(required) #渠道ID',
        //        'app_id'         => 'string(required) #应用ID',
        //        'pay_scene'      => 'string(required) #场景',
        //        'levels'         => 'string() #会员等级',
        //        'deposit_times'  => 'int #提款次数',
        //        'money_stop'     => 'int #累计停用金额',
        //        'money_day_stop' => 'int #日停用金额',
        //        'sort'           => 'int #排序',
        //        'status'         => 'bool #状态集合',
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
            [
                'type' => 'enum[rowset, row, dataset]',
                'data' => 'rows[id:int,app_id:string,channel_name:string,name:string,pay_scene:string,levels:string,
                deposit_times:int, money_used:int, money_stop:int,money_day_stop:int,money_day_used:int,url_notify:string,url_return:string,
                created_uname:string,sort:int,status:bool]',
            ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run($id = '')
    {
        $pay = new Logic\Recharge\Pay($this->ci);
        $payList =  $pay->allPayConfig();
        foreach($payList as $val){
            if($id == $val['id']){
                $val['status'] = $val['status'] == 'enabled' ? 1 : 0;
                return $val;
            }
        }
        return [];
    }

};
