<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '交易流水(记录)/资金流水--类别与类型';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const STATEs      = [];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $w3 = \Model\Admin\GameMenu::where('pid','!=',0)->where('type','!=','ZYCPSTA')->where('type','!=','ZYCPCHAT')->where('switch','enabled')->get(['id','name','type'])->toArray();
        return array_merge([['id' => 0, 'name' => '主钱包','type'=>'主钱包']], $w3??[]);
    }
};
