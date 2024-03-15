<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取相关信息';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [
    ];
    const SCHEMAS     = [
    ];


    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    
    public function run()
    {
        $data = $this->request->getParams();
        $validate = new \lib\validate\BaseValidate([
            'qq' => 'require',
            'qq_quan' => 'require',
            'wechat' => 'require',
        ]);
        $validate->paramsCheck('',$this->request,$this->response);

        $re = \DB::table('app_kf')->update($data);
        if($re === false)
            return $this->lang->set(-2);
        return $this->lang->set(0);

    }
};
