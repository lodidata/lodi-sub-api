<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '支付类型';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'type' => 'int()   #1线下2线上',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
            [
                'id '         => 'int #支付类型ID',
                'name'        => 'string #名称',
            ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    //   无配置表，无相关表，之前取的不对，暂时写死，线下数据与bank_account表中type取值范围对应,线上
    public function run(){
        $type = $this->request->getParam('type');
        $show = $type == 1 ? 'offline' : 'online';
        $data = \DB::table('funds_channel')->where('show', $show)->orderBy('sort')->get(['id','title as name'])->toArray();
        return $data;
    }

};
