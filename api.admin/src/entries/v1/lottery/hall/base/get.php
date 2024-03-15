<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '厅设置-基础设置';
    const DESCRIPTION = '厅设置-基础设置';
    
    const QUERY       = [
        'id' => 'int(required) #记录ID',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'id'          => 'int    #记录ID',
            'user_name'   => 'string   #用户名',
            'mobile'      => 'string #手机号码',
            'email'       => 'string(email)  #邮箱',
            'active_name' => 'string    #活动名称',
            'content'     => 'string    #申请内容',
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        $lottery = DB::table('lottery')->selectRaw('id,name,pid,type')->where('pid',0)->get()->toArray();
        foreach($lottery as &$value){
            $value->lottery = DB::table('lottery')
                ->selectRaw('id,name,type,open_type, pid,sort,all_bet_max,per_bet_max')
                ->where('pid',$value->id)->orderBy('sort')
                ->get()->toArray();
        }
        return $lottery;

    }

};
