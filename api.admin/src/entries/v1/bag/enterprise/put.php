<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '修改APP IOS企业包相关信息';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [
        'url' => 'string(require, 150)',#fir 地址
        'upgrade' => 'int()#是否强制升级  0 ：不升级， 1：升级',
    ];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    
    public function run($id)
    {
        $data = $this->request->getParams();
        $validate = new \lib\validate\BaseValidate([
            'url' => 'require',
            'upgrade' => 'in:0,1,9',
        ]);
        $validate->paramsCheck('',$this->request,$this->response);

        $data['update_date'] = date("Y-m-d H:i:s");
        $data['update_uid'] = $this->playLoad['uid'];
        $re = \DB::table('app_bag')->where('id',$id)->update($data);
        if($re)
            return $this->lang->set(0);
        return $this->lang->set(-2);
    }
};
