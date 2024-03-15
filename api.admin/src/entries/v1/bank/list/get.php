<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取所有可用银行列表';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
       'verifyToken','authorize'
    ];
    public function run()
    {
        $bank = \DB::table('bank')
            ->where('type','=',1)
            ->whereRaw('FIND_IN_SET("enabled",status)')
            ->orderBy('sort','DESC')
            ->get(['id','code','logo as img','status'])
            ->toArray();
        foreach ($bank as &$v){
            $v->name = $this->lang->text($v->code);
            $v->img = showImageUrl($v->img);
        }
        unset($v);
        return $bank;
    }
};
