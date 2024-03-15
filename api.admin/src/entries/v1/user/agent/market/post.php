<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '修改/新增 代理推广域名';
    const DESCRIPTION = '';
    
    const QUERY       = [];

    const PARAMS      = [
        'user_name' => 'string(require, 30)',
        'h5_url' => 'string(require)',#APP Name,
        'pc_url' => 'string(require)',#APP Name,
    ];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    
    public function run($id = null)
    {
        $data = $this->request->getParams();
        $v = [
            'user_name' => 'require',
            'h5_url' => 'require',
            'pc_url' => 'require',
        ];
        $validate = new \lib\validate\BaseValidate($v);
        $validate->paramsCheck('',$this->request,$this->response);
        try {
            if($id){
                \DB::table('user_agent_market')->where('id',$id)->update($data);
            }else{
                \DB::table('user_agent_market')->insert($data);
            }
            return $this->lang->set(0);
        }catch (\Exception $e) {
            return $this->lang->set(-2);
        }

    }
};
